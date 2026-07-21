<?php

namespace App\Http\Controllers\API\v1\UserActions;

use App\Http\Controllers\Controller;
use App\Models\Users\UserActivity;
use App\Models\Views\ActivityView;
use Auth;
use Illuminate\Http\Request;

class ActivityController extends Controller
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
            if ($request->has('filter')) {
                $data = UserActivity::where(['user_id' => Auth::user()->id, 'by_table' => $request->get('filter')])->orderBy('id', 'DESC')->first();
            } else {
                $data = ActivityView::where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->limit(10)->get();
            }
            if (!$data) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                $error = "Data not found";
                return _sendError(404, $message, $error);
            }
            $message = "Data found";
            return _sendResponse(201, $message, json_decode($data, true));
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }
}
