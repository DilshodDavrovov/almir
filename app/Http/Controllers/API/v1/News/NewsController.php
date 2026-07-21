<?php

namespace App\Http\Controllers\API\v1\News;


use App\Http\Controllers\Controller;
use App\Models\News\News;
use Auth;
use Illuminate\Http\Request;
use Validator;
use App\Http\Resources\GetDataCollection;
use Intervention\Image\Facades\Image;
//use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewsController extends Controller
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
        $data = News::where([
            ['is_active', $isActive],
            ['deleted', $IsDeleted],
        ])
            ->orderBy($SortBy, $SortByDesc)
            ->paginate($inputs['limit']);

        if (empty($data)) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            $error = "Data not found";
            return _sendError(404, $message, $error);
        }
        return response()->json(new GetDataCollection($data), 201);

        //$message = "Data successfully";
        //return _sendResponse(201, $message, $data);
    }

    /***
     * GET BY ID
     * @var ID = INT
     */
    public function GetByID(Request $request)
    {
        try {
            $dataID = $request->route('DataID');
            $data = News::findOrFail($dataID);
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
                $data = News::findOrFail($dataID);
            } else {
                $data = new News;
            }
            $rules = [
                'title' => 'required|string|max:255|unique:news,title,' . $dataID,
                'description' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            if ($inputs['slug'] == "") {
                $menu_url = Str::slug($inputs['title'], "-");
            } else {
                $menu_url = Str::slug($inputs['slug'], "-");
            }

            //User image
            $user_image = $request->file('image');
            if ($user_image) {
                if (empty($inputs['id'])) {
                    \File::delete(public_path() . '/upload/blogs/' . $data->image . '-b.png');
                    \File::delete(public_path() . '/upload/blogs/' . $data->image . '-s.png');
                }
                $tmpFilePath = '/upload/blogs/';
                $hardPath =  Str::slug('news', '-') . '-' . $menu_url . '-' . md5(time());
                $img = Image::make($user_image);
                $img1 = Image::make($user_image);
                $img->save(public_path() . $tmpFilePath . $hardPath . '-b.png');
                $img1->fit(700, 530)->save(public_path() . $tmpFilePath . $hardPath . '-s.png');
                //$saved = $tmpFilePath.$hardPath. '-s.jpg';
                $data->image = $tmpFilePath . $hardPath;
            }
            //start storing
            $data->user_id = Auth::user()->id;
            $data->title = Str::upper($inputs['title']);
            $data->slug = $menu_url;
            $data->description = $inputs['description'];
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
                    'deleted' => 'required|boolean'
                ];
            }
            if ($request->has("is_active")) {
                $rules = [
                    'is_active' => 'required|boolean'
                ];
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            if ($request->has("is_active")) {
                $data = News::scopeActiveDeactivate($dataID, $inputs["is_active"] == true ? 1 : 0, 1);
            }

            if ($request->has("deleted")) {
                $data = News::scopeDeleteIt($dataID, $inputs["deleted"] == true ? 1 : 0, 1);
            }


            $message = "Data updated successfully";
            return _sendResponse(201, $message, $data);
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

            $data = News::findOrFail($dataID);
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

            $data = News::whereIn('id', $dataID['dataID']);
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

            if ($request->has("deleted")) {
                $rules = [
                    'deleted' => 'required|boolean'
                ];
            }
            if ($request->has("is_active")) {
                $rules = [
                    'is_active' => 'required|boolean'
                ];
            }

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            if ($request->has("is_active")) {
                $data = News::scopeActiveDeactivateList($inputs['dataID'], $inputs["is_active"] == true ? 1 : 0, 1);
            }

            if ($request->has("deleted")) {
                $data = News::scopeDeleteList($inputs['dataID'], $inputs["deleted"] == true ? 1 : 0, 1);
            }
            $message = "Data updated successfully";
            return _sendResponse(201, $message, $data);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }
}
