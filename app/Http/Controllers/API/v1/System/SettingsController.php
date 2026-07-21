<?php

namespace App\Http\Controllers\API\v1\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\Models\System\AppSettings;
use App\Models\System\UpdateLog;
//use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
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

    public function getSettings() {
        $data = AppSettings::first();
        if (empty($data)) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            $error = "Data not found";
            return _sendError(404, $message, $error);
        }
        $message = "Success";
        return _sendResponse(201, $message, $data);
    }

    /**
     * Add Data
     */
    public function updateSettings(Request $request)
    {
        try {
            $inputs = $request->all();
            $data = AppSettings::first();
            $rules = [
                'app_name' => 'required|string|max:255',
                'support_email' => 'required|string|max:255',
                'contact_phone' => 'required|string|max:255',
                'referent_cost_file' => 'mimes:csv,txt,xlx,xls,xlsx,pdf|max:2048',
                'reg_cost_glc_file' => 'mimes:csv,txt,xlx,xls,xlsx,pdf|max:2048',
                'customer_cost_file' => 'mimes:csv,txt,xlx,xls,xlsx,pdf|max:2048',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            //files
            $tmpFilePath = '/upload/config/';
            $reg_cost_glc_file = $request->file('reg_cost_glc_file');
            if ($reg_cost_glc_file) {
                $fileName = $tmpFilePath. time() . '_' . $reg_cost_glc_file->getClientOriginalName();
                $reg_cost_glc_file->move(public_path() .$tmpFilePath, $fileName);
                $data->reg_cost_glc_file = $fileName;
            }

            $referent_cost_file = $request->file('referent_cost_file');
            if ($referent_cost_file) {
                $fileName = $tmpFilePath. time() . '_' . $referent_cost_file->getClientOriginalName();
                $referent_cost_file->move(public_path() .$tmpFilePath, $fileName);
                $data->referent_cost_file = $fileName;
            }

            $customer_cost_file = $request->file('customer_cost_file');
            if ($customer_cost_file) {
                $fileName = $tmpFilePath. time() . '_' . $customer_cost_file->getClientOriginalName();
                $customer_cost_file->move(public_path() .$tmpFilePath, $fileName);
                $data->customer_cost_file = $fileName;
            }
            //start storing
            //$data->user_id = Auth::user()->id;
            $data->app_name = Str::upper($inputs['app_name']);
            $data->support_email = $inputs['support_email'];
            $data->description = $inputs['description'];
            $data->contact_phone = $inputs['contact_phone'];
            $data->contact_fax = $inputs['contact_fax'];
            $data->contact_address = $inputs['contact_address'];
            //$data->app_version = $inputs['app_version'];
            $data->save();

            $message = "Данные успешно сохранены";
            return _sendResponse(201, $message, $data);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

     /**
     * Add Data
     */
    public function contactSupport(Request $request)
    {
        try {
            $inputs = $request->all();
            $rules = [
                'email' => 'required|email|string|max:255',
                'subject' => 'required|string|max:255',
                'description' => 'required|string',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }
            $message = "Электронная почта службы поддержки отправлена.";
            return _sendResponse(201, $message);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * Update log activity
     */
    public function addUpdateLog(Request $request)
    {
        try {
            $inputs = $request->all();
            $data = UpdateLog::orderBy('id', 'DESC')->first();
            $rules = [
                'updated_date' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            $data->user_id = Auth::user()->id;
            $data->title = $inputs['title'];
            $data->updated_date = Carbon::parse($inputs['updated_date']);
            $data->save();

            $message = "Данные успешно сохранены";
            return _sendResponse(201, $message, $data);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    
    public function getLogs() {
        $data = UpdateLog::orderBy('id', 'DESC')->first();
        if (empty($data)) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            $error = "Data not found";
            return _sendError(404, $message, $error);
        }
        $message = "Success";
        return _sendResponse(201, $message, $data);
    }

}
