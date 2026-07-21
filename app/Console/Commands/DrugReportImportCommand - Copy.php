<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

use App\Models\Distributor;
use App\Models\Drugs\Contrahens;
use App\Models\Drugs\DrugManufacturer;
use App\Models\Drugs\DrugReport;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\DB;

class DrugReportImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drcp:import {filepath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Drug Reports from Excel without using queue';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filePath = $this->argument('filepath');
        if (!file_exists($filePath)) {
            $this->error("\n❌ Fayl topilmadi: $filePath");
            return;
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(null, true, true, true);
        $header = array_shift($rows); // Bosh qator (sarlavha)
        $total = count($rows);
        $imported = 0;
        $skipped = 0;

        foreach ($rows as $index => $row) {
            $line = $index + 2; // Exceldagi qatordagi raqam (header +1)

            $period_code = trim($row['A']);
            $drug_mxik = trim($row['B']);
            $mode_40_date = is_numeric($row['C'])
                ? Carbon::parse(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['C']))
                : Carbon::parse($row['C']);;
            $distributor_inn = trim($row['D']);
            $distributor_name = trim($row['E']);
            $cont_inn = trim($row['F']);
            $quantity = trim($row['G']);
            $price_ccy_rate = trim($row['H']);
            $price_ccy = strtoupper(trim($row['I']));

            // 1. period_code unique tekshirish
            if (DrugReport::where('period_code', $period_code)->exists()) {
                $this->warn("\n[$line] SKIPPED: period_code ($period_code) bazada mavjud");
                $skipped++;
                continue;
            }

            // 2. DrugManufacturer dan drug_id va manufacturer_id topish
            $drugManufacturer = DrugManufacturer::where('drug_mxik', $drug_mxik)->first();
            if (!$drugManufacturer) {
                $this->error("\n[$line] ERROR: drug_mxik ($drug_mxik) topilmadi");
                $skipped++;
                continue;
            }

            // 3. Distributorni olish yoki yaratish
            $distributor = Distributor::where('distributor_inn', $distributor_inn)->first();
            if (!$distributor) {
                $distributor = Distributor::create([
                    'name' => $distributor_name,
                    'distributor_inn' => $distributor_inn,
                ]);
            }

            // 4. counterparty qidirish
            $counterparty = Contrahens::where('inn', $cont_inn)->first();
            if (!$counterparty) {
                $this->warn("\n[$line] Contrahens ($cont_inn) topilmadi – null qiymat berildi");
                continue;
            }

            // Price conversion
            $converted = price_converter(
                $mode_40_date->format('Y-m-d'),
                $price_ccy_rate,
                $price_ccy
            );

            DB::beginTransaction();

            // 5. DrugReport yozuvini yaratish
            try {
                DrugReport::create([
                    'user_id' => 1, //Auth::user()->id,
                    'data_type' => 2,
                    'period_code' => $period_code,
                    'serial_number' => null,
                    'shelf_life' => null,
                    'is_updated' => false,
                    'sc_id' => null, // Agar kerak bo'lsa, qo'shiladi
                    'mode_40_date' => $mode_40_date,
                    'm40d_id' => $distributor->id,
                    'drug_id' => $drugManufacturer->drug_id,
                    'mf_id' => $drugManufacturer->manufacturer_id,
                    'counterparty_id' => $counterparty->id,
                    'region_id' => $counterparty->region_id,
                    'district_id' => $counterparty->district_id,
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
                ]);
                DB::commit();

                $imported++;
                $this->info("[$line] ✅ IMPORTED: period_code $period_code");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("\n [$line] ❌ XATOLIK: " . $e->getMessage());
                $skipped++;
            }
        }
        $this->line("----");
        $this->info("✔️ Yakunlandi: $imported ta yozuv, $skipped ta o'tkazib yuborildi, jami $total ta");
    }
}
