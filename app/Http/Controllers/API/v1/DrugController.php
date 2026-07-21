<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GetDataCollection;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DrugImport;
use App\Models\Drug;
use App\Models\Drugs\DrugManufacturer;
use App\Models\Drugs\DrugReport;
use App\Models\Users\UserRole;
use App\Models\Views\DrugView;
use Auth;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DrugController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function getData(Request $request)
    {

        //   if (auth()->user()->can('edit-passwords')) {
        //     $user->password = $request->password;
        // }
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $SortBy = $inputs["sortBy"] ?? "created_at";
        if ($inputs['sortBy'] == 'mf') {
            $SortBy = DrugManufacturer::select('counter')->whereColumn('drug_manufacturers.drug_id', 'drugs.id')->take(1);
        }

        $SortByDesc = $request->has('sortByDesc') && $inputs["sortByDesc"] == true ? "DESC" : "ASC";
        $searchByID = $request->has('dataCode') && $inputs['dataCode'] ? $inputs['dataCode'] : null;
        $query = [
            ['is_active', $isActive],
            ['deleted', $IsDeleted],
        ];

        //starting query
        $data = Drug::where($query);
        if ($request->has('dataID') && !empty($inputs['dataID'])) {
            $data->whereIn('id', $inputs['dataID']);
        }
        if ($searchByID) {
            $data->whereRelation('_manufacturers', 'counter', '=', $searchByID);
        }
        
        //Added search by Types
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('dt_id', $inputs['dtID']);
        }

        $results = $data->with('_dt:id,name')
            ->with('trademark:id,name')
            ->with('drug_inn:id,name')
            ->with('drug_form:id,name')
            ->with('drug_form_group:id,name')
            ->with('drug_ts_group:id,name')
            ->with([
                '_manufacturers' => function ($q) {
                    $q
                        ->leftJoin('manufacturers', 'manufacturers.id', '=', 'drug_manufacturers.manufacturer_id')
                        ->leftJoin('countries', 'countries.id', '=', 'manufacturers.country_id')
                        ->select('drug_manufacturers.drug_id', 'drug_manufacturers.counter', DB::raw("CONCAT(manufacturers.name, ' (', countries.name, ')') AS full_name"));
                }
            ])
            ->orderBy($SortBy, $SortByDesc)
            ->paginate($inputs['limit']);

        if (empty($results)) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            $error = "Data not found";
            return _sendError(404, $message, $error);
        }
        return response()->json(new GetDataCollection($results), 201);

        //$message = "Data successfully";
        //return _sendResponse(201, $message, $data);
    }

    /**
     * SEARCH DATA
     * @var STRING Keyword
     */
    
    public function getSearchKeyword(Request $request)
    {
        try {
            $inputs = $request->all();
            
            $keyword = $request->has('search') ? $inputs['search'] : null;
            $isActive = $request->has('is_active') ? $inputs['is_active'] : 1;
            $isDeleted = $request->has('is_deleted') ? $inputs['is_deleted'] : 0;
            if ($request->has('idList') && $inputs['idList']) {
                $data = DrugView::scopeSearchByID(explode(',',$inputs['idList']), $isActive, $isDeleted);
            }
            else {
                $data = DrugView::scopeSearchByWord(Str::upper($keyword), $isActive, $isDeleted);
            }
            $message = "Данные успешно сохранены";
            return _sendResponse(201, $message, $data);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * SEARCH DATA
     * @var STRING Keyword
     */

    public function getDrugSearchByKeyword(Request $request)
    {
        try {
            $inputs = $request->all();
            
            $keyword = $request->has('search') ? $inputs['search'] : null;
            $isActive = $request->has('is_active') ? $inputs['is_active'] : 1;
            $isDeleted = $request->has('is_deleted') ? $inputs['is_deleted'] : 0;

            $dtIDList = $inputs['dtID'] ?? null;
            $distID = $inputs['distID'] ?? null;
            $scID = $inputs['companyID'] ?? null;
            $mfID = $inputs['mfID'] ?? null;
            $dfID = $inputs['dfID'] ?? null;
            $innID = $inputs['innID'] ?? null;
            $dtgID = $inputs['dtgID'] ?? null;
            $dfgID = $inputs['dfgID'] ?? null;
            $trademarkID = $inputs['trademarkID'] ?? null;
            $countryID = $inputs['countryID'] ?? null;
            
            if ($request->has('idList') && $inputs['idList']) {
                $data = Drug::scopeSearchByID(explode(',',$inputs['idList']), $dtIDList, $isActive, $isDeleted);
            }
            else {
                $data = Drug::scopeSearchByWord(
                    Str::upper($keyword), 
                    $dtIDList,
                    $distID,
                    $scID,
                    $mfID,
                    $dfID,
                    $innID,
                    $dtgID,
                    $dfgID,
                    $trademarkID,
                    $countryID,
                    $isActive, 
                    $isDeleted);
            }
            $message = "Данные успешно сохранены";
            return _sendResponse(201, $message, $data);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /***
     * GET BY ID
     * @var ID = INT
     */
    public function GetByID(Request $request)
    {
        try {
            $dataID = $request->route('DataID');
            $data = Drug::with('_dt:id,name')
                ->with('trademark:id,name')
                ->with('drug_inn:id,name')
                ->with('drug_form:id,name')
                ->with('drug_form_group:id,name')
                ->with('drug_ts_group:id,name')
                ->with([
                    '_manufacturers' => function ($q) {
                        $q
                            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'drug_manufacturers.manufacturer_id')
                            ->leftJoin('countries', 'countries.id', '=', 'manufacturers.country_id')
                            ->select('drug_manufacturers.drug_id', 'drug_manufacturers.drug_mxik', 'manufacturers.id as manufacturer_id', 'drug_manufacturers.counter', DB::raw("CONCAT(manufacturers.name, ' (', countries.name, ')') AS full_name"));
                    }
                ])->findOrFail($dataID);
            if (empty($data)) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                $error = "Data not found";
                return _sendError(404, $message, $error);
            }
            $message = "Data found";
            return _sendResponse(201, $message, $data);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * Add Data
     */
    public function AddData(Request $request)
    {
        try {
            //Starts manual transaction
            \DB::beginTransaction();
            $inputs = $request->all();
            $dataID = $request->route('DataID');

            if ($dataID) {
                $data = Drug::with('_manufacturers')->findOrFail($dataID);
            } else {
                $data = new Drug;
            }
            $rules = [
                'name' => 'required|string|max:255|unique:drugs,name,' . $dataID,
                'manufacturers' => 'present|array',
                'manufacturers.*.manufacturer_id' => 'required|integer',
                //'manufacturers.*.country_id' => 'required|integer',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            //start storing
            $data->user_id = Auth::user()->id;
            $data->name = Str::upper($inputs['name']);
            $data->drug_mxik = $inputs['drug_mxik'] ?? null; //added new 2025.01.13
            $data->dt_id = $inputs['dt_id'];
            $data->trademark_id = $inputs['trademark_id'];
            $data->di_id = $inputs['di_id'];
            $data->df_id = $inputs['df_id'];
            $data->dfg_id = $inputs['dfg_id'];
            $data->dtg_id = $inputs['dtg_id'];
            $data->ref_price = $inputs['ref_price'];
            $data->ref_price_ccy = $inputs['ref_price_ccy'];
            //$data->counter = $maxValue;
            $data->is_active = $inputs['is_active'];
            $data->is_rx = $request->has('is_rx') ? $inputs['is_rx'] : false;
            $data->is_otc = $request->has('is_otc') ? $inputs['is_otc'] : false;
            $data->deleted = $inputs['deleted'];
            $data->save();

            $RemoveList = [];
            foreach ($request->manufacturers as $key => $item) {
                $mfSearch = [
                    ["drug_id", $data->id],
                    ["manufacturer_id", $item['manufacturer_id']]
                ];
                if (isset($item['counter']) && $item['counter'] != "") {
                    $mfSearch = [
                        ["counter", $item['counter']]
                    ];
                }

                $hasId = DrugManufacturer::where($mfSearch)->first();
                if ($hasId) {
                    $mID = $hasId;
                    $rMfID = $hasId->manufacturer_id;
                } else {
                    $mID = new DrugManufacturer;
                    $rMfID = 25478961235;
                }
                $mID->user_id = Auth::user()->id;
                $mID->manufacturer_id = $item['manufacturer_id'];
                $mID->drug_id = $data->id;
                $mID->drug_mxik = $item['drug_mxik'] ?? null; //added new 2025.01.13
                #$mID->is_active = $item['is_active'];
                #$mID->deleted = $item['deleted'];
                $mID->save();
                DrugReport::where(['drug_id' => $mID->drug_id, 'mf_id' => $rMfID])->update(['mf_id' => $item['manufacturer_id']]);

                array_push($RemoveList, $mID->counter);
            }
            if (!empty($RemoveList)) {
                $removed =  DrugManufacturer::whereNotIn('counter', $RemoveList)->where('drug_id', $data->id);
                $removed->delete();
                $data = Drug::with('_manufacturers')->findOrFail($data->id);
            }
            \DB::commit();
            $message = "Данные успешно сохранены";
            return _sendResponse(201, $message, $data);
        } catch (\Throwable $error) {
            \DB::rollback();
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    public function ImportBulkData(Request $request)
    {
        try {
            $importer = new DrugImport;
            Excel::import($importer, $request->file);
            if ($importer->errList) {
                $message = "Возникли проблемы с загрузкой данных. Некоторые данные, показанные ниже, не были загружены в систему.";
                return _sendError(402, $message, $importer->errList);
            }
            $message = "Данные успешно импортированы без каких-либо проблем.";
            return _sendResponse(201, $message);
        } catch (\Exception $error) {
            $message = "Возникли проблемы с загрузкой данных. Некоторые данные, показанные ниже, не были загружены в систему.";
            return _sendError(402, $message, $error->getTrace());
        }
    }

    /**
     * CHANGE STATUS
     * @var Boolean IsActive
     * @var Boolean Deleted
     */

    public function ChangeStatus(Request $request)
    {
        try {
            $inputs = $request->all();
            $dataID = $request->route('DataID');
            $rules = [
                'is_active' => 'required|boolean',
                'deleted' => 'required|boolean',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }
            $data = Drug::scopeDeleteIt($dataID, $inputs["is_active"] == true ? 1 : 0, $inputs["deleted"] == true ? 1 : 0);
            switch ($data) {
                case $data->is_active && !$data->deleted:
                    $message = "Данные: " . $data->name . " успешно активированы.";
                    return _sendResponse(201, $message, $data);
                    break;
                case !$data->is_active && !$data->deleted:
                    $message = "Данные: " . $data->name . " успешно деактивированы.";
                    return _sendResponse(201, $message, $data);
                    break;
                case !$data->is_active && $data->deleted:
                    $message = "Данные: " . $data->name . " успешно перемещены в корзину.";
                    return _sendResponse(201, $message, $data);
                    break;
                default:
                    $message = "Что-то пошло не так при изменении статуса данных: " . $data->name;
                    return _sendResponse(201, $message, $data);
                    break;
            }
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * DELETE DATA BY ID
     * @var ID
     */
    public function RemoveDataByID(Request $request)
    {
        try {
            $dataID = $request->route('DataID');

            if (empty($dataID)) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                $error = "ID not found or error ID displayed.";
                return _sendError(422, $message, $error);
            }

            $data = Drug::findOrFail($dataID);
            $data->delete();
            $message = "Drug data removed successfully";
            return _sendResponse(201, $message);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * Add or Edit MF data.
     * @var MF id => INT
     * @var DRUG ID => INT
     */
    public function AddMFData(Request $request)
    {
        try {
            //Starts manual transaction
            \DB::beginTransaction();
            $inputs = $request->all();
            $dataID = $request->route('DataID');

            $rules = [
                'manufacturer_id' => 'required|integer',
                'country_id' => 'required|integer',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            $mID = new DrugManufacturer;

            $mID->user_id = Auth::user()->id;
            $mID->manufacturer_id = $inputs['manufacturer_id'];
            $mID->drug_id = $dataID;
            $mID->drug_mxik = $inputs['drug_mxik'] ?? null; //added new 2025.01.13
            $mID->is_active = $inputs['is_active'];
            $mID->deleted = $inputs['deleted'];
            $mID->save();

            \DB::commit();

            $message = "Manufacturer Данные успешно сохранены";
            return _sendResponse(201, $message, $mID);
        } catch (\Throwable $error) {
            \DB::rollback();

            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * Update MF data
     */
    public function UpdateMFData(Request $request)
    {
        try {
            //Starts manual transaction
            \DB::beginTransaction();
            $inputs = $request->all();
            $dataID = $request->route('DataID');

            $rules = [
                'manufacturer_id' => 'required|integer',
                'country_id' => 'required|integer',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            //$mID = DrugManufacturer::findOrFail($dataID);
            $mID = DrugManufacturer::where('counter', $dataID)->first();
            $bMfId = $mID->manufacturer_id;

            $mID->user_id = Auth::user()->id;
            $mID->manufacturer_id = $inputs['manufacturer_id'];
            //$mID->drug_id = $dataID;
            $mID->is_active = $inputs['is_active'];
            $mID->deleted = $inputs['deleted'];
            $mID->save();
            DrugReport::where(['drug_id' => $mID->drug_id, 'mf_id' => $bMfId])->update(['mf_id'=> $inputs['manufacturer_id']]);
            \DB::commit();

            $message = "Manufacturer Данные успешно сохранены";
            return _sendResponse(201, $message, $mID);
        } catch (\Throwable $error) {
            \DB::rollback();

            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * DELETE DATA BY ID
     * @var ID
     */
    public function RemoveMFDataByID(Request $request)
    {
        try {
            $drugID = $request->route('DrugID');
            $mfID = $request->route('mfID');

            if (empty($drugID)) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                $error = "ID not found or error ID displayed.";
                return _sendError(422, $message, $error);
            }

            $q = 'DELETE FROM drug_manufacturers where drug_id =' . $drugID . ' AND manufacturer_id = ' . $mfID;
            \DB::delete($q);
            $message = "Drug manufacturer data removed successfully";
            return _sendResponse(201, $message);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * Trash id list
     */
    public function RemoveListStatus(Request $request)
    {
        try {
            $inputs = $request->all();
            $rules = [
                'dataID' => 'required',
                'is_active' => 'required|boolean',
                'deleted' => 'required|boolean',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }
            $isActive = $inputs["is_active"] == true ? 1 : 0;
            $isDeleted = $inputs["deleted"] == true ? 1 : 0;
            $data = Drug::scopeActiveDeactivateList($inputs['dataID'], $isActive, $isDeleted);
            if ($data) {
                $message = "Выбранные данные успешно изменены.";
                return _sendResponse(201, $message);
            }
            if (!$data) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message);
            }
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * Remove Multiple IDs
     */
    public function RemoveIdList(Request $request)
    {
        try {
            $dataID = $request->all();
            if (empty($dataID)) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                $error = "ID not found or error ID displayed.";
                return _sendError(422, $message, $error);
            }

            $data = Drug::whereIn('id', $dataID['dataID']);
            $data->delete();
            $message = "Data removed successfully";
            return _sendResponse(201, $message);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }
}
