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

    public function handle()
    {
        // Xotira va vaqt chegaralarini oshirish
        opcache_reset();
        clearstatcache();

        ini_set('memory_limit', '4069M');
        set_time_limit(0);

        $filePath = $this->argument('filepath');
        if (!file_exists($filePath)) {
            $this->error("\n❌ Fayl topilmadi: $filePath");
            return 1;
        }

        // Oldindan ma'lumotlarni keshlash
        $drugManufacturers = DrugManufacturer::select('drug_mxik', 'drug_id', 'manufacturer_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [trim(strtolower($item->drug_mxik)) => [
                    'drug_id' => $item->drug_id,
                    'manufacturer_id' => $item->manufacturer_id
                ]];
            })->toArray();
        $distributors = Distributor::pluck('id', 'distributor_inn')->toArray();
        $counterparties = Contrahens::select('inn', 'id', 'region_id', 'district_id')
            ->get()
            ->mapWithKeys(function ($item) {
                // inn ni tozalash va indekslash
                return [trim((string)$item->inn) => [
                    'id' => $item->id,
                    'region_id' => $item->region_id,
                    'district_id' => $item->district_id
                ]];
            })->toArray();
        $existingPeriodCodes = DrugReport::pluck('period_code')->toArray() ?: [];

        $exchangeRates = ExchangeRate::select('usd_price_rate', 'eur_price_rate', 'rub_price_rate', 'rate_date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [trim(strtolower($item->rate_date)) => [
                    'usd_price_rate' => $item->usd_price_rate,
                    'eur_price_rate' => $item->eur_price_rate,
                    'rub_price_rate' => $item->rub_price_rate,
                    'uzs_price_rate' => 1, // UZS uchun default qiymat
                ]];
            })->toArray();

        $imported = 0;
        $skipped = 0;
        $total = 0;
        $globalRowNumber = 1; // Umumiy qator hisoblagichi (header qatori hisoblanmaydi)
        $isFirstChunk = true; // Birinchi chunk ekanligini belgilash

        // Excel faylni chunklar bo'yicha o'qish
        Excel::import(new class($this, $imported, $skipped, $total, $distributors, $counterparties, $drugManufacturers, $existingPeriodCodes, $globalRowNumber, $isFirstChunk, $exchangeRates) implements \Maatwebsite\Excel\Concerns\ToCollection, \Maatwebsite\Excel\Concerns\WithChunkReading {
            protected $command;
            protected $imported;
            protected $skipped;
            protected $total;
            protected $distributors;
            protected $counterparties;
            protected $drugManufacturers;
            protected $existingPeriodCodes;
            protected $globalRowNumber;
            protected $isFirstChunk;
            protected $exchangeRates;

            public function __construct($command, &$imported, &$skipped, &$total, &$distributors, &$counterparties, &$drugManufacturers, &$existingPeriodCodes, &$globalRowNumber, $isFirstChunk, $exchangeRates)
            {
                $this->command = $command;
                $this->imported = &$imported;
                $this->skipped = &$skipped;
                $this->total = &$total;
                $this->distributors = &$distributors;
                $this->counterparties = &$counterparties;
                $this->drugManufacturers = &$drugManufacturers;
                $this->existingPeriodCodes = &$existingPeriodCodes;
                $this->globalRowNumber = &$globalRowNumber;
                $this->isFirstChunk = &$isFirstChunk;
                $this->exchangeRates = &$exchangeRates;
            }

            public function collection(\Illuminate\Support\Collection $rows)
            {
                $batchData = [];
                $batchStartRow = $this->globalRowNumber + 1; // Chunkning boshlang'ich qatori (header tashlangan)

                // Faqat birinchi chunkda headerni o'tkazib yuborish
                if ($this->isFirstChunk) {
                    $rows->shift();
                    $this->isFirstChunk = false; // Keyingi chunklar uchun header o'tkazilmaydi
                }
              
                $this->total += $rows->count();
                //$rows->shift(); // Headerni o'tkazib yuborish

                foreach ($rows as $index => $row) {
                    //$line = $index + 2;
                    $line = $this->globalRowNumber + 1;

                    $period_code = trim($row[0] ?? '');
                    $drug_mxik = trim($row[1] ?? '');

                    $cleanedDrugMxik = trim(strtolower($drug_mxik)); // Katta-kichik harf va bo'shliqlarni tozalash

                    $mode_40_date = isset($row[2]) && is_numeric($row[2])
                        ? Carbon::parse(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[2]))
                        : (isset($row[2]) ? Carbon::parse($row[2]) : null);
                    $distributor_inn = trim($row[3] ?? '');
                    //$distributor_name = trim($row[4] ?? '');
                    $cont_inn = trim($row[4] ?? '');
                    $quantity = trim($row[5] ?? 0);
                    $price_ccy_rate = trim($row[6] ?? 0);
                    $price_ccy = strtoupper(trim($row[7] ?? ''));

                    // Ma'lumotlarni validatsiya qilish
                    if (empty($period_code) || empty($drug_mxik) || !$mode_40_date) {
                        $this->command->error("\n[$line] ERROR: Kerakli ma'lumotlar yetishmayapti (period_code, drug_mxik yoki mode_40_date)");
                        $this->skipped++;
                        $this->globalRowNumber++;
                        continue;
                    }

                    // 1. period_code unique tekshirish
                    if (in_array($period_code, $this->existingPeriodCodes)) {
                        $this->command->warn("\n[$line] SKIPPED: period_code ($period_code) bazada mavjud");
                        $this->skipped++;
                        $this->globalRowNumber++;
                        continue;
                    }

                    // 2. DrugManufacturer tekshirish
                    if (!isset($this->drugManufacturers[$cleanedDrugMxik])) {
                        $this->command->error("\n[$line] ERROR: drug_mxik ($drug_mxik) topilmadi");
                        $this->skipped++;
                        $this->globalRowNumber++;
                        continue;
                    }

                    // 3. Distributor topish yoki yaratish
                    if (!isset($this->distributors[$distributor_inn])) {
                        $this->command->warn("\n[$line] Distributor ($distributor_inn) topilmadi – null qiymat berildi");
                        $this->skipped++;
                        $this->globalRowNumber++;
                        continue;
                    }

                    // if (!isset($this->distributors[$distributor_inn])) {
                    //     try {
                    //         $distributor = Distributor::create([
                    //             'name' => $distributor_name,
                    //             'distributor_inn' => $distributor_inn,
                    //         ]);
                    //         $this->distributors[$distributor_inn] = $distributor->id;
                    //     } catch (\Exception $e) {
                    //         $this->command->error("\n[$line] ERROR: Distributor yaratishda xato: " . $e->getMessage());
                    //         $this->skipped++;
                    //         $this->globalRowNumber++;
                    //         continue;
                    //     }
                    // }

                    // 4. Counterparty tekshirish
                    if (!isset($this->counterparties[$cont_inn])) {
                        $this->command->warn("\n[$line] Contrahens ($cont_inn) topilmadi – null qiymat berildi");
                        $this->skipped++;
                        $this->globalRowNumber++;
                        continue;
                    }

                    // Price conversion
                    $rate_values = $this->exchangeRates[$mode_40_date->format('Y-m-d')] ?? null;
                    if ($price_ccy == "UZS") {
                        $converted = [
                            'usd_price_rate' => $price_ccy_rate / ($rate_values['usd_price_rate'] ?? 0),
                            'eur_price_rate' => $price_ccy_rate / ($rate_values['eur_price_rate'] ?? 0),
                            'rub_price_rate' => $price_ccy_rate / ($rate_values['rub_price_rate'] ?? 0),
                            'uzs_price_rate' => $price_ccy_rate,
                        ];
                    } elseif ($price_ccy == "USD") {
                        $converted = [
                            'usd_price_rate' => $price_ccy_rate,
                            'eur_price_rate' => $price_ccy_rate * ($rate_values['eur_price_rate'] ?? 0),
                            'rub_price_rate' => $price_ccy_rate * ($rate_values['rub_price_rate'] ?? 0),
                            'uzs_price_rate' => $price_ccy_rate * ($rate_values['uzs_price_rate'] ?? 0),
                        ];
                    } elseif ($price_ccy == "EUR") {
                        $converted = [
                            'usd_price_rate' => $price_ccy_rate / ($rate_values['eur_price_rate'] ?? 0),
                            'eur_price_rate' => $price_ccy_rate,
                            'rub_price_rate' => $price_ccy_rate * ($rate_values['rub_price_rate'] ?? 0),
                            'uzs_price_rate' => $price_ccy_rate * ($rate_values['uzs_price_rate'] ?? 0),
                        ];
                    } elseif ($price_ccy == "RUB") {
                        $converted = [
                            'usd_price_rate' => $price_ccy_rate / ($rate_values['rub_price_rate'] ?? 0),
                            'eur_price_rate' => $price_ccy_rate / ($rate_values['rub_price_rate'] ?? 0),
                            'rub_price_rate' => $price_ccy_rate,
                            'uzs_price_rate' => $price_ccy_rate * ($rate_values['uzs_price_rate'] ?? 0),
                        ];
                    }

                    // try {
                    //     $converted = price_converter(
                    //         $mode_40_date->format('Y-m-d'),
                    //         $price_ccy_rate,
                    //         $price_ccy
                    //     );
                    // } catch (\Exception $e) {
                    //     $this->command->error("\n[$line] ERROR: Price conversion xatosi: " . $e->getMessage());
                    //     $this->skipped++;
                    //     continue;
                    // }

                    // Batch ma'lumotlarini to'plash
                    $batchData[] = [
                        'user_id' => 1,
                        'data_type' => 2,
                        'period_code' => $period_code,
                        'serial_number' => null,
                        'shelf_life' => null,
                        'is_updated' => false,
                        'sc_id' => null,
                        'mode_40_date' => $mode_40_date,
                        'm40d_id' => $this->distributors[$distributor_inn],
                        'drug_id' => $this->drugManufacturers[$drug_mxik]['drug_id'],
                        'mf_id' => $this->drugManufacturers[$drug_mxik]['manufacturer_id'],
                        'counterparty_id' => $this->counterparties[$cont_inn]['id'],
                        'region_id' => $this->counterparties[$cont_inn]['region_id'],
                        'district_id' => $this->counterparties[$cont_inn]['district_id'],
                        'quantity' => $quantity,
                        'price_ccy_rate' => $price_ccy_rate,
                        'price_ccy' => $price_ccy,
                        'price_usd' => $converted['usd_price_rate'],
                        'price_rub' => $converted['rub_price_rate'],
                        'price_uzs' => $converted['uzs_price_rate'],
                        'price_eur' => $converted['eur_price_rate'],
                        'sum_price_usd' => round($converted['usd_price_rate'] * $quantity, 2),
                        'sum_price_rub' => round($converted['rub_price_rate'] * $quantity, 2),
                        'sum_price_uzs' => round($converted['uzs_price_rate'] * $quantity, 2),
                        'sum_price_eur' => round($converted['eur_price_rate'] * $quantity, 2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $this->globalRowNumber++;
                    //$this->existingPeriodCodes[] = $period_code;
                }

                // Batch insert
                if (!empty($batchData)) {
                    $batchEndRow = $this->globalRowNumber;
                    DB::beginTransaction();
                    try {
                        DrugReport::insert($batchData);
                        DB::commit();
                        $this->imported += count($batchData);
                        $this->command->info("[Rows $batchStartRow-$batchEndRow] ✅ IMPORTED: " . count($batchData) . " yozuvlar");
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->command->error("\n[$line] ❌ XATOLIK: " . $e->getMessage());
                        $this->skipped += count($batchData);
                    }
                }
            }

            public function chunkSize(): int
            {
                return 100; // Har bir chunkda 1000 qator
            }
        }, $filePath);

        $this->line("----");
        $this->info("✔️ Yakunlandi: $imported ta yozuv, $skipped ta o'tkazib yuborildi, jami $total ta");
        return 0;
    }
}
