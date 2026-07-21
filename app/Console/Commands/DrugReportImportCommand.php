<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Distributor;
use App\Models\Drugs\Contrahens;
use App\Models\Drugs\DrugManufacturer;
use App\Models\Drugs\DrugReport;
use App\Models\System\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DrugReportImportCommand extends Command
{
    protected $signature = 'drcp:import {filepath}';
    protected $description = 'Import Drug Reports from Excel without using queue';

    public function __construct()
    {
        parent::__construct();
    }

    //opcache_reset();
    //clearstatcache();
    public function handle()
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $filePath = $this->argument('filepath');
        if (!file_exists($filePath)) {
            $this->error("❌ Fayl topilmadi: $filePath");
            return 1;
        }

        // 🔧 Yordamchi funksiya: ilmiy formatdagi raqamlarni oddiy stringga aylantirish
        $normalizeNumber = function ($value) {
            $value = trim((string)$value);
            if ($value === '') return null;
            $value = str_replace(',', '.', $value); // 3,05E+08 -> 3.05E+08
            if (stripos($value, 'e') !== false) {
                return (string)(int)((float)$value);
            }
            return preg_replace('/\D/', '', $value); // faqat raqam qoldiramiz
        };

        // 🔧 Ma’lumotlarni RAMga oldindan olish
        $drugManufacturers = DrugManufacturer::select('drug_mxik', 'drug_id', 'manufacturer_id')
            ->get()
            ->mapWithKeys(fn($item) => [strtolower(trim($item->drug_mxik)) => [
                'drug_id' => $item->drug_id,
                'manufacturer_id' => $item->manufacturer_id
            ]])->toArray();

        $distributors = Distributor::pluck('id', 'distributor_inn')->toArray();

        $counterparties = Contrahens::select('inn', 'id', 'region_id', 'district_id')
            ->get()
            ->mapWithKeys(fn($item) => [trim((string)$item->inn) => [
                'id' => $item->id,
                'region_id' => $item->region_id,
                'district_id' => $item->district_id
            ]])->toArray();

        $exchangeRates = ExchangeRate::select('usd_price_rate', 'eur_price_rate', 'rub_price_rate', 'rate_date')
            ->get()
            ->mapWithKeys(fn($item) => [$item->rate_date => [
                'usd_price_rate' => $item->usd_price_rate,
                'eur_price_rate' => $item->eur_price_rate,
                'rub_price_rate' => $item->rub_price_rate,
                'uzs_price_rate' => 1,
            ]])->toArray();

        $imported = 0;
        $skipped  = 0;
        $total    = 0;

        $batchData = [];
        $batchSize = 10000; // bir martalik insert hajmi

        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle, 0, ','); // birinchi qatorni tashlaymiz

            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                $total++;
                //\Log::info($row);
                $period_code     = trim($row[0] ?? '');
                $drug_mxik_raw   = $row[1] ?? '';
                $drug_mxik       = strtolower($normalizeNumber($drug_mxik_raw));

                // Sana parse (dd.mm.YYYY)
                $mode_40_date = null;
                if (!empty($row[2])) {
                    try {
                        $mode_40_date = Carbon::createFromFormat('d.m.Y', trim($row[2]));
                    } catch (\Exception $e) {
                        $mode_40_date = null;
                    }
                }

                $distributor_inn = $normalizeNumber($row[3] ?? '');
                $cont_inn        = $normalizeNumber($row[4] ?? '');
                $quantity        = (float)($row[5] ?? 0);
                $price_ccy_rate  = (float)($row[6] ?? 0);
                $price_ccy       = strtoupper(trim($row[7] ?? ''));

                // ❌ Validatsiya
                if (!$period_code || !$drug_mxik || !$mode_40_date) {
                    $this->warn("[$total] Sana yoki MXIK yoki period_code noto‘g‘ri: {$period_code}, {$drug_mxik_raw}, {$row[2]}");
                    $skipped++;
                    continue;
                }

                // 🔍 period_code mavjudligini tekshirish (RAM yemasin, OOM bo‘lmasin)
                $exists = DB::table('drug_reports')
                    ->where('period_code', $period_code)
                    ->exists();
                    
                if ($exists) {
                    $this->warn("[$total] period_code allaqachon mavjud: {$period_code}");
                    $skipped++;
                    continue;
                }


                if (!isset($drugManufacturers[$drug_mxik])) {
                    $this->warn("[$total] MXIK topilmadi: {$drug_mxik}");
                    $skipped++;
                    continue;
                }
                if (!isset($distributors[$distributor_inn])) {
                    $this->warn("[$total] Distributor topilmadi: {$distributor_inn}");
                    $skipped++;
                    continue;
                }
                if (!isset($counterparties[$cont_inn])) {
                    $this->warn("[$total] Contrahens topilmadi: {$cont_inn}");
                    $skipped++;
                    continue;
                }

                $rate_values = $exchangeRates[$mode_40_date->format('Y-m-d')] ?? null;
                if (!$rate_values) {
                    $this->warn("[$total] Kurs topilmadi: {$mode_40_date->format('Y-m-d')}");

                    $skipped++;
                    continue;
                }

                // 💰 Valyuta konvertatsiyasi
                switch ($price_ccy) {
                    case "UZS":
                        $converted = [
                            'usd' => $rate_values['usd_price_rate'] ? $price_ccy_rate / $rate_values['usd_price_rate'] : 0,
                            'eur' => $rate_values['eur_price_rate'] ? $price_ccy_rate / $rate_values['eur_price_rate'] : 0,
                            'rub' => $rate_values['rub_price_rate'] ? $price_ccy_rate / $rate_values['rub_price_rate'] : 0,
                            'uzs' => $price_ccy_rate,
                        ];
                        break;
                    case "USD":
                        $converted = [
                            'usd' => $price_ccy_rate,
                            'eur' => $price_ccy_rate * $rate_values['eur_price_rate'],
                            'rub' => $price_ccy_rate * $rate_values['rub_price_rate'],
                            'uzs' => $price_ccy_rate * $rate_values['uzs_price_rate'],
                        ];
                        break;
                    case "EUR":
                        $converted = [
                            'usd' => $rate_values['eur_price_rate'] ? $price_ccy_rate / $rate_values['eur_price_rate'] : 0,
                            'eur' => $price_ccy_rate,
                            'rub' => $price_ccy_rate * $rate_values['rub_price_rate'],
                            'uzs' => $price_ccy_rate * $rate_values['uzs_price_rate'],
                        ];
                        break;
                    case "RUB":
                        $converted = [
                            'usd' => $rate_values['rub_price_rate'] ? $price_ccy_rate / $rate_values['rub_price_rate'] : 0,
                            'eur' => $rate_values['rub_price_rate'] ? $price_ccy_rate / $rate_values['rub_price_rate'] : 0,
                            'rub' => $price_ccy_rate,
                            'uzs' => $price_ccy_rate * $rate_values['uzs_price_rate'],
                        ];
                        break;
                    default:
                        $skipped++;
                        continue 2; // tashqi while sikliga qaytamiz
                }

                $drugInfo = $drugManufacturers[$drug_mxik];
                $contr    = $counterparties[$cont_inn];

                // ✅ Batch yig‘ish
                $batchData[] = [
                    'user_id' => 1,
                    'data_type' => 2,
                    'period_code' => $period_code,
                    'serial_number' => null,
                    'shelf_life' => null,
                    'is_updated' => false,
                    'sc_id' => null,
                    'mode_40_date' => $mode_40_date,
                    'm40d_id' => $distributors[$distributor_inn],
                    'drug_id' => $drugInfo['drug_id'],
                    'mf_id'   => $drugInfo['manufacturer_id'],
                    'counterparty_id' => $contr['id'],
                    'region_id' => $contr['region_id'],
                    'district_id' => $contr['district_id'],
                    'quantity' => $quantity,
                    'price_ccy_rate' => $price_ccy_rate,
                    'price_ccy' => $price_ccy,
                    'price_usd' => $converted['usd'],
                    'price_rub' => $converted['rub'],
                    'price_uzs' => $converted['uzs'],
                    'price_eur' => $converted['eur'],
                    'sum_price_usd' => round($converted['usd'] * $quantity, 2),
                    'sum_price_rub' => round($converted['rub'] * $quantity, 2),
                    'sum_price_uzs' => round($converted['uzs'] * $quantity, 2),
                    'sum_price_eur' => round($converted['eur'] * $quantity, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batchData) >= $batchSize) {
                    DB::table('drug_reports')->insert($batchData);
                    $imported += count($batchData);
                    $batchData = [];
                }
            }
            fclose($handle);

            // qolgan batchni yozish
            if (!empty($batchData)) {
                DB::table('drug_reports')->insert($batchData);
                $imported += count($batchData);
            }
        }

        $this->info("✔️ Yakunlandi: $imported ta yozuv, $skipped ta o'tkazib yuborildi, jami $total ta");
        return 0;
    }
}
