<?php

namespace App\Http\Controllers\API\v1\Region;

use App\Http\Controllers\Controller;
use App\Http\Resources\GetDataCollection;
use App\Models\System\District;
use App\Models\Views\CountryView;
use App\Models\Views\DistrictView;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Auth;
use Validator;
use Illuminate\Support\Str;

class DistrictController extends Controller
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
        $inputs = $request->all();

        // Asosiy so'rov
        $query = District::with([
            'region:id,name',
        ])
        ->where(function ($q) use ($inputs, $request) {
            $q->where([
                ['districts.is_active', $inputs["is_active"] == true ? 1 : 0],
                ['districts.deleted', $inputs["deleted"] == true ? 1 : 0],
            ]);

            if ($request->has('region_id') && $inputs['region_id']) {
                $q->where('districts.region_id', $inputs["region_id"]);
            }

            if ($request->has('dataID') && !empty($inputs['dataID'])) {
                $q->whereIn('districts.id', $inputs['dataID']);
            }
        });
        // Ma'lumotlarni paginate qilish
        $perPage = $inputs['limit'] ?? 50; // Default limit: 50
        $data = $query->orderBy($inputs['SortBy'] ?? 'id', $inputs['SortByDesc'] ?? 'desc')
            ->paginate($perPage);


        if (empty($data)) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            $error = "Data not found";
            return _sendError(404, $message, $error);
        }
        return response()->json(new GetDataCollection($data), 201);
    }

    /***
     * GET BY ID
     * @var ID = INT
     */
    public function GetByID(Request $request)
    {
        try {
            $dataID = $request->route('DataID');
            $data = District::with('region:id,name')->findOrFail($dataID);
            if (empty($data)) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                $error = "Data not found";
                return _sendError(404, $message, $error);
            }
            $message = "Data found";
            return _sendResponse(201, $message, $data);
        } catch (\Exception $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
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
            $RegionId = $inputs['regionID'] ?? null;

            $dtIDList = $inputs['dtID'] ?? null;
            $distID = $inputs['distID'] ?? null;
            $scID = $inputs['companyID'] ?? null;
            $mfID = $inputs['mfID'] ?? null;
            $dfID = $inputs['dfID'] ?? null;
            $innID = $inputs['innID'] ?? null;
            $dtgID = $inputs['dtgID'] ?? null;
            $dfgID = $inputs['dfgID'] ?? null;
            $trademarkID = $inputs['trademarkID'] ?? null;
            $drugID = $inputs['drugID'] ?? null;

            if ($request->has('idList') && $inputs['idList']) {
                $data = DistrictView::scopeSearchByID(explode(',', $inputs['idList']), $isActive, $isDeleted, $RegionId);
            } else {
                $data = DistrictView::scopeSearchByWord(
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
                    $drugID,
                    $isActive,
                    $isDeleted,
                    $RegionId
                );
            }
            $message = "Данные успешно сохранены";
            return _sendResponse(201, $message, $data);
        } catch (\Exception $error) {
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
                $data = District::findOrFail($dataID);
            } else {
                $data = new District;
            }
            $rules = [
                'name' => 'required|string|max:255|unique:countries,name,' . $dataID,
                'region_id' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            //start storing
            $data->user_id = Auth::user()->id;
            $data->name = Str::upper($inputs['name']);
            $data->region_id = Str::upper($inputs['region_id']);
            $data->is_active = $inputs['is_active'];
            $data->deleted = $inputs['deleted'];
            $data->save();

            $message = "Данные успешно сохранены";
            return _sendResponse(201, $message, $data);
        } catch (\Exception $error) {
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
            Excel::import(new DistrictImport, $request->file);
            $message = "Data Imported Successfully";
            return _sendResponse(201, $message);
        } catch (\Exception $error) {
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
            $data = District::scopeDeleteIt($dataID, $inputs["is_active"] == true ? 1 : 0, $inputs["deleted"] == true ? 1 : 0);
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

            $data = District::findOrFail($dataID);
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

            $data = District::whereIn('id', $dataID['dataID']);
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
            $data = District::scopeActiveDeactivateList($inputs['dataID'], $isActive, $isDeleted);
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
