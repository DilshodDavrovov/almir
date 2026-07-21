<?php

namespace App\Imports;

use App\Models\Distributor;
use App\Models\Drugs\counterparty;
use App\Models\Drugs\DrugManufacturer;
use App\Models\Drugs\DrugReport;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DrugReportImport implements ToCollection, WithHeadingRow, WithChunkReading, ShouldQueue, WithBatchInserts
{
    public $errors = [];
    
    public function chunkSize(): int
    {
        return 1000;
    }
    
    public function batchSize(): int
    {
        return 1000;
    }



    public function collection(Collection $rows)
    {
        $header = $rows->first(); // Header qator
        unset($rows[0]); // Headerni olib tashlaymiz

        foreach ($rows as $index => $row) {
            $line = $index + 2; // Exceldagi qatordagi raqam (header +1)

            $period_code = trim($row[0]);
            $drug_mxik = trim($row[1]);
            $mode_40_date = is_numeric($row[2])
                ? Carbon::parse(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[2]))
                : Carbon::parse($row[2]);;
            $distributor_inn = trim($row[3]);
            $distributor_name = trim($row[4]);
            $cont_inn = trim($row[5]);
            $quantity = trim($row[6]);
            $price_ccy_rate = trim($row[7]);
            $price_ccy = strtoupper(trim($row[8]));

            // 1. period_code unique tekshirish
            if (DrugReport::where('period_code', $period_code)->exists()) {
                $this->errors[] = "[$line] period_code ($period_code) bazada mavjud.";
                continue;
            }

            // 2. DrugManufacturer dan drug_id va manufacturer_id topish
            $drugManufacturer = DrugManufacturer::where('drug_mxik', $drug_mxik)->first();
            if (!$drugManufacturer) {
                $this->errors[] = "[$line] Drug MXIK ($drug_mxik) topilmadi.";
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
            $counterparty = counterparty::where('inn', $cont_inn)->first();
            if (!$counterparty) {
                $this->errors[] = "[$line] Contrahent INN ($cont_inn) topilmadi.";
                continue;
            }

            // Price conversion
            $converted = price_converter(
                $mode_40_date->format('Y-m-d'),
                $price_ccy_rate,
                $price_ccy
            );
            

            // 5. DrugReport yozuvini yaratish
            try {
                DrugReport::create([
                    'user_id' => Auth::user()->id,
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
            } catch (\Exception $e) {
                $this->errors[] = "[$line] Ma'lumotni yozishda xatolik: " . $e->getMessage();
            }
        }
    }


    public function onError(\Throwable $e)
    {
        $this->errors[] = ['message' => 'Ошибка: ' . $e->getMessage(), 'trace' => $e->getTrace()];
    }
}
