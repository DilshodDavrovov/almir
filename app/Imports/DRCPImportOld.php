<?php

namespace App\Imports;

use App\Models\Distributor;
use App\Models\Drug;
use App\Models\Drugs\Company;
use App\Models\Drugs\Contrahens;
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

class DRCPImport implements ToCollection, WithChunkReading, SkipsOnError
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
          $insert['data_type'] = 2;

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

            $insert['serial_number'] = null;
            $insert['shelf_life'] = null;

            $insert['mode_40_date'] = is_numeric($data[2]) ? Carbon::parse(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data[2])) : Carbon::parse($data[2]);

            $dist40Name = Distributor::where('distributor_inn', $data[3])->first() ?? new Distributor;
            if (!$dist40Name->exists) {
              try {
                $dist40Name->distributor_inn = $data[3];
                $dist40Name->name = $data[4];
                $dist40Name->save();
              } catch (\Exception $ex) {
                $errData['message'] = "Произошла ошибка при сохранении данных: " . Str::upper($data[4]) . ' ' . $ex->getMessage() . " Строка данных: " . $k + 1;
                $this->errList[] = $errData;
              }
            }
            $insert['m40d_id'] = $dist40Name->id ?? null;

            $contrName = Contrahens::where('inn', $data[5])->first();
            if (!$contrName->exists) {
              $errData['message'] = "Произошла ошибка при сохранении данных: " . Str::upper($data[6]) . ' ' . $ex->getMessage() . " Строка данных: " . $k + 1;
              $this->errList[] = $errData;
            }
            $insert['counterparty_id'] = $contrName->id ?? null;
            $insert['region_id'] = $contrName->region_id ?? null;
            $insert['district_id'] = $contrName->district_id ?? null;

            $insert['sc_id'] = null;

            $insert['price_ccy'] = $data[9];
            $insert['price_ccy_rate'] = $data[8];
            $insert['quantity'] = $data[7];
            $converted = price_converter(
              is_numeric($data[2]) ? Carbon::parse(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($data[2]))->format('Y-m-d') : Carbon::parse($data[2])->format('Y-m-d'),
              $data[8],
              $data[9]
            );
            $insert['price_usd'] = $converted['usd_price_rate'];
            $insert['price_uzs'] = $converted['uzs_price_rate'];
            $insert['price_eur'] = $converted['eur_price_rate'];
            $insert['price_rub'] = $converted['rub_price_rate'];

            $insert['sum_price_usd'] = round($converted['usd_price_rate'] * $data[7], 2);
            $insert['sum_price_uzs'] = round($converted['uzs_price_rate'] * $data[7], 2);
            $insert['sum_price_eur'] = round($converted['eur_price_rate'] * $data[7], 2);
            $insert['sum_price_rub'] = round($converted['rub_price_rate'] * $data[7], 2);
            $insert['is_updated'] = false;
            if ($insert['drug_id'] && $insert['m40d_id'] && $insert['m40d_id']) {
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
    $this->errList[] = $e->getTrace();
    // Handle the exception how you'd like.
  }
}
