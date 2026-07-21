<?php

namespace App\Imports;

use App\Models\Distributor;
use App\Models\Drug;
use App\Models\Drugs\Company;
use App\Models\Drugs\DrugManufacturer;
use App\Models\Drugs\DrugReport;
use Auth;
//use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DRCImport implements ToCollection, WithChunkReading, SkipsOnError
{
    use Importable, SkipsErrors;
    public $errList = [];
    public $ddd = [];
    public $res;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth:sanctum');
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
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


    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        set_time_limit(6000);
        $inserted = [];
        $errData = [];
        if (!empty($rows) && $rows->count() >= 2) {
            foreach ($rows as $k => $data) {
                if ($k >= 1 && !empty($data[1])) {
                    $insert = [];
                    $uniCode = DrugReport::select('period_code')->where('period_code', $data[0])->first();
                    $insert['period_code'] = $data[0];
                    if ($uniCode) {
                        $errData['message'] = "Данные " . $data[0] . "  юникода уже введены в систему. Мы не загружали эту информацию. Строка данных: " . $k + 1;
                        $this->errList[] = $errData;
                    }
                    //Get drug name and mf
                    $drugName = DrugManufacturer::where('counter', $data[1])->first();
                    if ($drugName && !$uniCode) {
                        $insert['drug_id'] = $drugName->drug_id;
                        $insert['mf_id'] = $drugName->manufacturer_id;

                        $insert['serial_number'] = $data[2];
                        $insert['shelf_life'] = is_numeric($data[3]) ? Carbon::parse(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data[3])) :  Carbon::parse($data[3]);
                        $insert['mode_70_date'] = is_numeric($data[4]) ?  Carbon::parse(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data[4])) : Carbon::parse($data[4]);
                        $dist70Name = Distributor::where('name', $data[5])->first();
                        if (!$dist70Name) {
                            $errData['message'] = "Данные " . Str::upper($data[5]) . "  не найден. Мы не загружали эту информацию. Строка данных: " . $k + 1;
                            $this->errList[] = $errData;
                            // $dist70N = new Distributor;
                            // $dist70N->name = Str::upper($data[5]);
                            // $dist70N->user_id = Auth::user()->id;
                            // $dist70N->is_active = true;
                            // $dist70N->save();
                            // $dist70Name = $dist70N;
                        }

                        $insert['m70d_id'] = $dist70Name->id ?? null;
                        $insert['mode_40_date'] = is_numeric($data[6]) ? Carbon::parse(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data[6])) : Carbon::parse($data[6]);
                        $dist40Name = Distributor::where('name', $data[7])->first();
                        if (!$dist40Name) {
                            $errData['message'] = "Данные " . Str::upper($data[7]) . "  не найден. Мы не загружали эту информацию. Строка данных: " . $k + 1;
                            $this->errList[] = $errData;

                            // $dist40N = new Distributor;
                            // $dist40N->name = Str::upper($data[7]);
                            // $dist40N->user_id = Auth::user()->id;
                            // $dist40N->is_active = true;
                            // $dist40N->save();
                            // $dist40Name = $dist40N;
                        }
                        $insert['m40d_id'] = $dist40Name->id ?? null;

                        $SenderCName = Company::where('name', $data[8])->first();
                        if (!$SenderCName) {
                            $errData['message'] = "Данные " . Str::upper($data[7]) . "  не найден. Мы не загружали эту информацию. Строка данных: " . $k + 1;
                            $this->errList[] = $errData;

                            // $SenderCN = new Company;
                            // $SenderCN->name = Str::upper($data[8]);
                            // $SenderCN->user_id = Auth::user()->id;
                            // $SenderCN->is_active = true;
                            // $SenderCN->save();
                            // $SenderCName = $SenderCN;
                        }
                        $insert['sc_id'] = $SenderCName->id ?? Str::upper($data[7]);

                        $insert['price_ccy'] = $data[10];
                        $insert['price_ccy_rate'] = $data[9];
                        $insert['quantity'] = $data[11];
                        $converted = price_converter(
                            is_numeric($data[4]) ? Carbon::parse(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data[4]))->format('Y-m-d') : Carbon::parse($data[4])->format('Y-m-d'),
                            $data[9],
                            $data[10]
                        );
                        $insert['price_usd'] = $converted['usd_price_rate'];
                        $insert['price_uzs'] = $converted['uzs_price_rate'];
                        $insert['price_eur'] = $converted['eur_price_rate'];
                        $insert['price_rub'] = $converted['rub_price_rate'];

                        $insert['sum_price_usd'] = round($converted['usd_price_rate'] * $data[11], 2);
                        $insert['sum_price_uzs'] = round($converted['uzs_price_rate'] * $data[11], 2);
                        $insert['sum_price_eur'] = round($converted['eur_price_rate'] * $data[11], 2);
                        $insert['sum_price_rub'] = round($converted['rub_price_rate'] * $data[11], 2);
                        $insert['is_updated'] = false;
                        if ($insert['drug_id'] && $insert['mode_70_date'] && $insert['m70d_id'] && $insert['m40d_id'] && $insert['m40d_id']) {
                            $inserted[] = $insert;
                            $this->ddd[] = $insert;
                        } else {
                            $errData['message'] = 'Ошибка в данных в строке ' . ($k + 1) . '. Пожалуйста, проверьте эти строки.';
                            $this->errList[] = $errData;
                        }
                    } else {
                        $errData['message'] = "Данные " . $data[1] . " не найдены в строке " . $k + 1;
                        $this->errList[] = $errData;
                    }
                }
            }
            DrugReport::insert($inserted);
        } else {
            $errData['message'] = "Недостаточно данных для загрузки.";
            $this->errList[] = $errData;
        }
    }

    /**
     * @param \Throwable $e
     */
    public function onError(\Throwable $e)
    {
        $this->errList[] = $e;
        // Handle the exception how you'd like.
    }
}
