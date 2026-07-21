<?php

namespace App\Imports;

use App\Models\Distributor;
use App\Models\Drugs\Contrahens;
use App\Models\Drugs\DrugManufacturer;
use App\Models\Drugs\DrugReport;
use Auth;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DRCPImport implements ToCollection, WithChunkReading, SkipsOnError
{
    use Importable, SkipsErrors;
    public $errList = [];
    public $ddd = [];
    public $res;

    public function __construct()
    {
        // Removed middleware comment as it's not used
    }

    public function batchSize(): int
    {
        return 1000; // Kept same, but can adjust based on server capacity
    }

    public function chunkSize(): int
    {
        return 1000; // Kept same, but can adjust based on server capacity
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
            ],
        ];
    }

    public function collection(Collection $rows)
    {
        set_time_limit(6000);

        // Preload existing data to reduce database queries
        $periodCodes = DrugReport::whereIn('period_code', $rows->pluck(0)->unique()->filter()->values())->pluck('period_code')->toArray();
        $drugManufacturers = DrugManufacturer::whereIn('drug_mxik', $rows->pluck(1)->unique()->filter()->values())->get()->keyBy('drug_mxik');
        $distributors = Distributor::whereIn('distributor_inn', $rows->pluck(3)->unique()->filter()->values())->get()->keyBy('distributor_inn');
        $contrahens = Contrahens::whereIn('inn', $rows->pluck(5)->unique()->filter()->values())->get()->keyBy('inn');

        $inserted = [];
        $errData = [];

        if ($rows->count() < 2) {
            $this->errList[] = ['message' => 'Недостаточно данных для загрузки.'];
            return;
        }

        foreach ($rows->skip(1) as $k => $data) { // Skip header row
            if (empty($data[1])) {
                continue; // Skip empty drug counter rows
            }

            $insert = [
                'user_id' => Auth::user()->id,
                'data_type' => 2,
                'period_code' => $data[0],
                'serial_number' => null,
                'shelf_life' => null,
                'is_updated' => false,
                //'region_id' => $data[10] ?? null,
                //'district_id' => $data[11] ?? null,
            ];

            // Check for existing period code
            if (in_array($data[0], $periodCodes)) {
                $this->errList[] = ['message' => "Данные {$data[0]} юникода уже введены в систему. Мы не загружали эту информацию. Строка данных: " . ($k + 2)];
                continue;
            }

            // Get drug name and manufacturer
            $drugName = $drugManufacturers->get($data[1]);
            if (!$drugName) {
                $this->errList[] = ['message' => "Данные {$data[1]} не найдены в строке " . ($k + 2)];
                continue;
            }

            $insert['drug_id'] = $drugName->drug_id;
            $insert['mf_id'] = $drugName->manufacturer_id;

            // Parse date
            $insert['mode_40_date'] = is_numeric($data[2])
                ? Carbon::parse(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data[2]))
                : Carbon::parse($data[2]);

            // Handle distributor
            $dist40Name = $distributors->get($data[3]) ?? new Distributor;
            if (!$dist40Name->exists) {
                try {
                    $dist40Name->distributor_inn = $data[3];
                    $dist40Name->name = $data[4];
                    $dist40Name->save();
                    $distributors->put($data[3], $dist40Name); // Cache new distributor
                } catch (\Exception $ex) {
                    $this->errList[] = ['message' => "Произошла ошибка при сохранении данных: " . Str::upper($data[4]) . ' ' . $ex->getMessage() . " Строка данных: " . ($k + 2)];
                    continue;
                }
            }
            $insert['m40d_id'] = $dist40Name->id ?? null;

            // Handle contrahens
            $contrName = $contrahens->get($data[5]);
            if ($contrName->exists) {
                $insert['counterparty_id'] = $contrName->id;
                $insert['region_id'] = $contrName->region_id;
                $insert['district_id'] = $contrName->district_id;
            }
            else {
                $this->errList[] = ['message' => "Произошла ошибка при сохранении данных: " . Str::upper($data[4]) . " Строка данных: " . ($k + 2)];
                continue;
            }

            $insert['sc_id'] = null;
            $insert['price_ccy'] = $data[8];
            $insert['price_ccy_rate'] = $data[7];
            $insert['quantity'] = $data[6];

            // Price conversion
            $converted = price_converter(
                $insert['mode_40_date']->format('Y-m-d'),
                $data[7],
                $data[8]
            );

            $insert['price_usd'] = $converted['usd_price_rate'];
            $insert['price_uzs'] = $converted['uzs_price_rate'];
            $insert['price_eur'] = $converted['eur_price_rate'];
            $insert['price_rub'] = $converted['rub_price_rate'];
            $insert['sum_price_usd'] = round($converted['usd_price_rate'] * $data[6], 2);
            $insert['sum_price_uzs'] = round($converted['uzs_price_rate'] * $data[6], 2);
            $insert['sum_price_eur'] = round($converted['eur_price_rate'] * $data[6], 2);
            $insert['sum_price_rub'] = round($converted['rub_price_rate'] * $data[6], 2);

            // Validate required fields
            if ($insert['drug_id'] && $insert['m40d_id'] && $insert['counterparty_id']) {
                $inserted[] = $insert;
                $this->ddd[] = $insert;
            } else {
                $this->errList[] = ['message' => 'Ошибка в данных в строке ' . ($k + 2) . '. Пожалуйста, проверьте эти строки.'];
            }
        }

        // Bulk insert
        if (!empty($inserted)) {
            try {
                DrugReport::insert($inserted);
            } catch (\Exception $ex) {
                $this->errList[] = ['message' => 'Ошибка при массовой вставке данных: ' . $ex->getMessage()];
            }
        }
    }

    public function onError(\Throwable $e)
    {
        $this->errList[] = ['message' => 'Ошибка: ' . $e->getMessage(), 'trace' => $e->getTrace()];
    }
}
