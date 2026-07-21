<?php

namespace App\Imports;

use App\Models\Drug;
use App\Models\DrugFarmGroup;
use App\Models\DrugForm;
use App\Models\DrugInn;
use App\Models\Drugs\DrugManufacturer;
use App\Models\Drugs\DrugType;
use App\Models\DrugTsGroup;
use App\Models\Manufacturer;
use App\Models\System\Country;
use App\Models\Trademark;
use Auth;
//use Illuminate\Support\Facades\DB;
//use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class DrugImport implements ToCollection, WithChunkReading, SkipsOnError
{
    use Importable, SkipsErrors;
    public $errList = [];

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

    /**
     * @param Collection $collection
     */
    public function collection(Collection $rows)
    {
        set_time_limit(6000);
        $errData = [];
        if (!empty($rows) && $rows->count() >= 2) {
            foreach ($rows as $k => $data) {
                if ($k >= 1 && !empty($data[0])) {
                    //Get drug name and mf
                    $drug = Drug::where('name', Str::upper($data[0]))->first() ?? new Drug;
                    $drug->name = Str::upper($data[0]);
                    $drug->user_id = Auth::user()->id;
                    $drug->is_rx = $data[11] && $data[11] == 1 ? true : false;
                    $drug->is_otc = $data[11] && $data[11] == 2 ? true : false;

                    $_dt = DrugType::where('name', Str::upper($data[1]))->first();
                    if (!$_dt) {
                        $_dt = new DrugType;
                        $_dt->user_id = Auth::user()->id;
                        $_dt->name = Str::upper($data[1]);
                        $_dt->is_active = true;
                        $_dt->save();
                    }
                    $drug->dt_id = $_dt->id;

                    $_di = DrugInn::where('name', Str::upper($data[2]))->first();
                    if (!$_di) {
                        $_di = new DrugInn;
                        $_di->user_id = Auth::user()->id;
                        $_di->name = Str::upper($data[2]);
                        $_di->is_active = true;
                        $_di->save();
                    }
                    $drug->di_id = $_di->id;

                    $_df = DrugForm::where('name', Str::upper($data[3]))->first();
                    if (!$_df) {
                        $_df = new DrugForm;
                        $_df->user_id = Auth::user()->id;
                        $_df->name = Str::upper($data[3]);
                        $_df->is_active = true;
                        $_df->save();
                    }
                    $drug->df_id = $_df->id;

                    $_dfg = DrugFarmGroup::where('name', Str::upper($data[4]))->first();
                    if (!$_dfg) {
                        $_dfg = new DrugFarmGroup;
                        $_dfg->user_id = Auth::user()->id;
                        $_dfg->name = Str::upper($data[4]);
                        $_dfg->is_active = true;
                        $_dfg->save();
                    }
                    $drug->dfg_id = $_dfg->id;

                    $_dtg = DrugTsGroup::where('name', Str::upper($data[5]))->first();
                    if (!$_dtg) {
                        $_dtg = new DrugTsGroup;
                        $_dtg->user_id = Auth::user()->id;
                        $_dtg->name = Str::upper($data[5]);
                        $_dtg->is_active = true;
                        $_dtg->save();
                    }
                    $drug->dtg_id = $_dtg->id;

                    $drug->ref_price = $data[6] ?? 0.00;
                    $drug->ref_price_ccy = $data[7] ?? "UZS";

                    $_trademark = Trademark::where('name', Str::upper($data[8]))->first();
                    if (!$_trademark) {
                        $_trademark = new Trademark;
                        $_trademark->user_id = Auth::user()->id;
                        $_trademark->name = Str::upper($data[8]);
                        $_trademark->is_active = true;
                        $_trademark->save();
                    }
                    $drug->trademark_id = $_trademark->id;

                    $mf_id = Manufacturer::where([
                        ['name', Str::upper($data[9])],
                        //['country_id', Str::upper($data[10])],
                    ])->first();
                    if (!$mf_id) {
                        $_country = Country::where('id', $data[10])->first();
                        if ($_country) {
                            $mf_id = new Manufacturer;
                            $mf_id->user_id = Auth::user()->id;
                            $mf_id->name = Str::upper($data[9]);
                            $mf_id->country_id = $_country->id;
                            $mf_id->is_active = true;
                            $mf_id->save();
                        } else {
                            $errData['message'] = "Данные " . $data[10] . " не найдены в строке " . $k + 1;
                            $this->errList[] = $errData;
                        }
                    }
                    if ($drug->save()) {
                        $_dm = DrugManufacturer::where([
                            ['drug_id', $drug->id],
                            ['manufacturer_id', $mf_id->id]
                        ])->first() ?? new DrugManufacturer;
                        $_dm->drug_id = $drug->id;
                        $_dm->manufacturer_id = $mf_id->id;
                        $_dm->user_id = Auth::user()->id;
                        $_dm->save();
                    } else {
                        $errData['message'] = "Ошибка в данных в строке " . ($k + 1) . "! Эта информация не была сохранена. Проверьте еще раз, пожалуйста.";
                        $this->errList[] = $errData;
                    }
                }
            }
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
        $errData['message'] = $e->getTraceAsString();
        $this->errList[] = $errData;
        // Handle the exception how you'd like.
    }
}
