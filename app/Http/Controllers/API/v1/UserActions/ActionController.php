<?php

namespace App\Http\Controllers\API\v1\UserActions;

use App\Http\Controllers\Controller;
use App\Models\Users\UserAction;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Validator;

//use Illuminate\Support\Facades\DB;

class ActionController extends Controller
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


    /***
     * GET BY ACTION DATA
     * @var ID = INT
     */
    public function GetData(Request $request)
    {
        try {

            $data = UserAction::where('user_id', Auth::user()->id)->first();
            if (empty($data)) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                $error = "Data not found";
                return _sendError(404, $message, $error);
            }
            $message = "Data found";
            return _sendResponse(201, $message, json_decode($data->body, true));
        } catch (\Throwable$error) {
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
            $data = UserAction::where('user_id', Auth::user()->id)->first() ?? new UserAction;
            $rules = [
                'filterCol' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            //start storing
            $data->user_id = Auth::user()->id;
            $data->name = Str::upper("USER_FILTER_FIELDS");
            $data->body = \json_encode($inputs['filterCol']);
            $data->is_active = $inputs['is_active'];
            $data->deleted = $inputs['deleted'];
            $data->save();
            $message = "Данные успешно сохранены";
            return _sendResponse(201, $message, \json_decode($data->body, true));
        } catch (\Throwable$error) {
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

            if ($request->has("deleted")) {
                $rules = [
                    'deleted' => 'required|boolean',
                ];
            }
            if ($request->has("is_active")) {
                $rules = [
                    'is_active' => 'required|boolean',
                ];
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            if ($request->has("is_active")) {
                $data = UserAction::scopeActiveDeactivate($dataID, $inputs["is_active"] == true ? 1 : 0, 1);
            }

            if ($request->has("deleted")) {
                $data = UserAction::scopeDeleteIt($dataID, $inputs["deleted"] == true ? 1 : 0, 1);
            }

            $message = "Data updated successfully";
            return _sendResponse(201, $message, $data);
        } catch (\Throwable$error) {
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

            $data = UserAction::findOrFail($dataID);
            $data->delete();
            $message = "Data removed successfully";
            return _sendResponse(201, $message);
        } catch (\Throwable$error) {
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

            $data = UserAction::whereIn('id', $dataID['dataID']);
            $data->delete();
            $message = "Data removed successfully";
            return _sendResponse(201, $message);
        } catch (\Throwable$error) {
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

            if ($request->has("deleted")) {
                $rules = [
                    'deleted' => 'required|boolean',
                ];
            }
            if ($request->has("is_active")) {
                $rules = [
                    'is_active' => 'required|boolean',
                ];
            }

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            if ($request->has("is_active")) {
                $data = UserAction::scopeActiveDeactivateList($inputs['dataID'], $inputs["is_active"] == true ? 1 : 0, 1);
            }

            if ($request->has("deleted")) {
                $data = UserAction::scopeDeleteList($inputs['dataID'], $inputs["deleted"] == true ? 1 : 0, 1);
            }
            $message = "Data updated successfully";
            return _sendResponse(201, $message, $data);
        } catch (\Throwable$error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }
}
