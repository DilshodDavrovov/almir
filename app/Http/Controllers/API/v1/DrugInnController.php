<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GetDataCollection;
use App\Imports\InnImport;
use App\Models\DrugInn;
use App\Models\Views\DIView;
use Maatwebsite\Excel\Facades\Excel;
use Auth;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Str;

//use Illuminate\Support\Facades\DB;

class DrugInnController extends Controller
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
        $SortByDesc = $request->has('sortByDesc') && $inputs["sortByDesc"] == true ? "DESC" : "ASC";
        //->orderBy($SortBy, $SortByDesc)

        if ($request->has('dataID') && !empty($inputs['dataID'])) {
            $data = DrugInn::where([
                ['is_active', $isActive],
                ['deleted', $IsDeleted],
            ])->whereIn('id', $inputs['dataID'])
                ->orderBy($SortBy, $SortByDesc)
                ->paginate($inputs['limit']);
        } else {
            $data = DrugInn::where([
                ['is_active', $isActive],
                ['deleted', $IsDeleted],
            ])
                ->orderBy($SortBy, $SortByDesc)
                ->paginate($inputs['limit']);
        }

        if (empty($data)) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            $error = "Data not found";
            return _sendError(404, $message, $error);
        }
        return response()->json(new GetDataCollection($data), 201);

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

            $dtIDList = $inputs['dtID'] ?? null;
            $scID = $inputs['companyID'] ?? null;
            $countryID = $inputs['countryID'] ?? null;
            $distID = $inputs['distID'] ?? null;
            $dfgID = $inputs['dfgID'] ?? null;
            $dfID = $inputs['dfID'] ?? null;
            $dtgID = $inputs['dtgID'] ?? null;
            $mfID = $inputs['mfID'] ?? null;
            $trademarkID = $inputs['trademarkID'] ?? null;
            $drugID = $inputs['drugID'] ?? null;

            if ($request->has('idList') && $inputs['idList']) {
                $data = DIView::scopeSearchByID(explode(',',$inputs['idList']), $isActive, $isDeleted);
            }
            else {
                $data = DIView::
                scopeSearchByWord(
                    Str::upper($keyword),
                    $dtIDList,
                    $distID,
                    $countryID,
                    $scID,
                    $dfgID,
                    $dfID,
                    $dtgID,
                    $mfID,
                    $trademarkID,
                    $drugID,
                    $isActive,
                    $isDeleted
                );
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
            $data = DrugInn::findOrFail($dataID);
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
            $inputs = $request->all();
            $dataID = $request->route('DataID');

            if (!empty($dataID)) {
                $data = DrugInn::findOrFail($dataID);
            } else {
                $data = new DrugInn;
            }
            $rules = [
                'name' => 'required|string|max:255|unique:drug_inns,name,' . $dataID,
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            //start storing
            $data->user_id = Auth::user()->id;
            $data->name = Str::upper($inputs['name']);
            $data->is_active = $inputs['is_active'];
            $data->deleted = $inputs['deleted'];
            $data->save();

            $message = "Данные успешно сохранены";
            return _sendResponse(201, $message, $data);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * BULK IMPORT
     */
    public function ImportBulkData(Request $request)
    {
        try {
            Excel::import(new InnImport, $request->file);
            $message = "Data Imported Successfully";
            return _sendResponse(201, $message);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
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
            $data = DrugInn::scopeDeleteIt($dataID, $inputs["is_active"] == true ? 1 : 0, $inputs["deleted"] == true ? 1 : 0);
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

            $data = DrugInn::findOrFail($dataID);
            $data->delete();
            $message = "Data removed successfully";
            return _sendResponse(201, $message);
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

            $data = DrugInn::whereIn('id', $dataID['dataID']);
            $data->delete();
            $message = "Data removed successfully";
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
            $data = DrugInn::scopeActiveDeactivateList($inputs['dataID'], $isActive, $isDeleted);
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
}
