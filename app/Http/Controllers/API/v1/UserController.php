<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GetDataCollection;
use App\Models\User;
use App\Models\Users\UserAccess;
use App\Models\Users\UserRole;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Hash;
use Validator;


class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /***
     * GET BY ID
     * @var ID = INT
     */
    public function GetUserList(Request $request)
    {
        try {
            $inputs = $request->all();
            $isActive = $inputs["is_active"] == true ? 1 : 0;
            $IsDeleted = $inputs["is_deleted"] == true ? 1 : 0;
            $IsBlocked = $inputs["is_blocked"] == true ? 1 : 0;
            $SortBy = $inputs["sortBy"] ?? "created_at";
            $SortByDesc = $request->has('sortByDesc') && $inputs["sortByDesc"] == true ? "DESC" : "ASC";
          
            $query = [
                ['is_active', $isActive],
                ['is_deleted', $IsDeleted],
                ['is_blocked', $IsBlocked],
                ['ur.x_roles_id', '!=', 1]
            ];

            if ($request->has('company_name') && !empty($inputs['company_name'])) {
                $query[] = ['company_name', 'LIKE', '%'. $inputs['company_name'].'%'];
            }

            if ($request->has('company_inn') && !empty($inputs['company_inn'])) {
                $query[] = ['company_inn', 'LIKE', '%' . $inputs['company_inn'] . '%'];
            }

            if ($request->has('phone_number') && !empty($inputs['phone_number'])) {
                $query[] = ['phone_number', 'LIKE', '%' . $inputs['phone_number'] . '%'];
            }

            if ($request->has('email') && !empty($inputs['email'])) {
                $query[] = ['email', 'LIKE', '%' . $inputs['email'] . '%'];
            }

            $data = User::select('users.*', 'ur.x_roles_id as user_role')
            ->leftJoin('users_roles as ur', 'ur.user_id', '=', 'users.id')
            ->with('access')
            ->where($query)->orderBy($SortBy, $SortByDesc)
            ->paginate($inputs['limit']);
            
            if (empty($data)) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                $error = "Data not found";
                return _sendError(404, $message, $error);
            }
            $message = "Data found";
            return response()->json(new GetDataCollection($data), 201);
            //return _sendResponse(201, $message, $data);
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
            //$data = User::findOrFail($dataID);
            $data = User::select('users.*', 'ur.x_roles_id as user_role')
            ->leftJoin('users_roles as ur', 'ur.user_id', '=', 'users.id')
            ->with('access')
            ->where('id', $dataID)->first();
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
     * GET CURRENT USER INFO
     */
    public function GetUserInfo(Request $request)
    {
        try {
            $dataID = Auth::user()->id;
            $data = User::select('users.*', 'ur.x_roles_id as user_role')
            ->leftJoin('users_roles as ur', 'ur.user_id', '=', 'users.id')
            ->with('access')
            ->where('id', $dataID)->first();
            //$data = User::findOrFail($dataID)->leftJoin('user_roles ur', 'ur.user_id', '=', 'users.id');
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
     * Starting quiz
     */

    /**
     * Upload user avatar
     */

    public function UpdateAvatar(Request $request)
    {
        //Auth::guard('sanctum')->user();
        //Auth::user()->id;
        try {
            //Get user for update
            //$user = User::where('email', $request['email'])->firstOrFail();
            if ($request->has("user_id")) {
                $users = User::findOrFail($request->user_id);
            } else {
                $users = User::findOrFail(Auth::user()->id);
            }

            $rules = [
                'user_image' => 'required|mimes:png,PNG,JPG,jpg,jpeg|max:4096',
            ];
            $inputs = $request->all();
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $response = [
                    'code' => 0041,
                    'status' => 422,
                    //'success' => false,
                    'message' => "Что-то пошло не так! Пожалуйста, попробуйте позже!",
                    'error' => $validator->errors(),
                ];
                return response()->json($response, 422);
            }

            //User image
            $user_image = $request->file('user_image');
            if ($user_image) {
                //Save original file
                //store file into document folder
                //$file = $request->file->store('public/documents');

                if (!empty($inputs['user_image']) && Auth::user()->user_image != $inputs['user_image']) {
                    \File::delete(public_path() . '/' . Auth::user()->user_image . '-b.jpg');
                    \File::delete(public_path() . '/' . Auth::user()->user_image);
                    \File::delete(public_path() . '/' . Auth::user()->user_image . '-s.jpg');
                }
                $tmpFilePath = 'upload/users/';
                $hardPath = Str::slug('user', '-') . '-' . md5(time());
                // $img = Image::make($user_image);
                $img1 = Image::make($user_image);
                // $img->save($tmpFilePath.$hardPath.'-b.jpg');
                $saved = $tmpFilePath . $hardPath . '-s.jpg';
                $img1->fit(512, 512)->save($saved);
                $users->avatar = $saved;
                $users->save();

                $response = [
                    'code' => 00011,
                    'status' => 201,
                    'message' => "Your avatar updated successful.",
                ];

                return response()->json($response, 201);
            }

            $response = [
                'code' => 00011,
                'status' => 403,
                'message' => "Something went wrong! Please provide valid image or file",
            ];

            return response()->json($response, 403);
        } catch (\Throwable $th) {
            $response = [
                'code' => 00042,
                'status' => 419,
                'message' => "Что-то пошло не так! Пожалуйста, попробуйте позже!",
                'error' => $th,
            ];
            return response()->json($response, 419);
        }
    }

    /**
     * UPDATE Birthday
     */

    public function UpdateUserInfo(Request $request)
    {
        //Auth::guard('sanctum')->user();
        //Auth::user()->id;
        try {
            //Get user for update
            //$user = User::where('email', $request['email'])->firstOrFail();
            $userId = Auth::user()->id;
            if ($request->has("user_id")) {
                $userId = $request->user_id;
                $users = User::findOrFail($request->user_id);
            } else {
                $users = User::findOrFail(Auth::user()->id);
            }
            $rule = array(
                'last_name' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $userId,
                'user_mac' => 'required|string|max:50',
            );

            $inputs = $request->all();
            $validator = \Validator::make($inputs, $rule);

            if ($validator->fails()) {
                return _sendError(422, "Что-то пошло не так! Пожалуйста, попробуйте позже!", $validator->errors());
            }

            //dd(Carbon::createFromFormat('m/d/Y', $inputs['birth_date'])->toDate() );
            //dd(Carbon::parse($inputs['birth_date']));
            $users->first_name = $inputs["first_name"];
            $users->last_name = $inputs["last_name"];
            $users->middle_name = $inputs["middle_name"];
            $users->company_name = $inputs["company_name"];
            $users->company_inn = $inputs["company_inn"];
            $users->phone_number = $inputs["phone_number"];
            $users->email = $inputs["email"];
            $users->confirmed = $inputs["confirmed"] ?? 1;
            $users->is_blocked = $inputs["is_blocked"];
            $users->status = $inputs["status"] ?? 1;
            $users->user_mac = $inputs["user_mac"];
            $users->um_created_at = Carbon::parse($inputs['um_created_at']);
            $users->um_expired_at = Carbon::parse($inputs['um_expired_at']);
            $users->passport_info = $inputs["passport_info"];
            $users->address = $inputs["address"];
            //shipment_access
            $users->shipment_access = $inputs["shipment_access"] ?? 0;
            //$users->birthday = Carbon::parse($inputs['birth_date']); //$inputs['birth_date'];
            $users->save();
            if (isset($inputs["user_role"])) {
               $userRole = UserRole::where('user_id', $users->id)->first();
               $userRole->x_roles_id = $inputs["user_role"];
               $userRole->save();
            }

            return _sendResponse(201, "Your data updated successful.", $users);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * UPDATE Birthday
     */

    public function UpdateUserPassword(Request $request)
    {
        //Auth::guard('sanctum')->user();
        //Auth::user()->id;
        try {
            //Get user for update
            //$user = User::where('email', $request['email'])->firstOrFail();
            $userId = Auth::user()->id;
            if ($request->has("user_id")) {
                $userId = $request->user_id;
                $users = User::findOrFail($userId);
            }

            $rule = ['password' => 'required|string|min:8'];

            $inputs = $request->all();
            $validator = \Validator::make($inputs, $rule);

            if ($validator->fails()) {
                return _sendError(422, "Что-то пошло не так! Пожалуйста, попробуйте позже!", $validator->errors());
            }

            //dd(Carbon::createFromFormat('m/d/Y', $inputs['birth_date'])->toDate() );
            //dd(Carbon::parse($inputs['birth_date']));
            $users->password = Hash::make($inputs['password']);
            $users->save();

            return _sendResponse(201, "Your data updated successful.", $users);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * UPDATE Birthday
     */

    public function UpdateUserLanguage(Request $request)
    {
        try {
            $users = User::findOrFail(Auth::user()->id);
            $rule = array(
                'language' => 'required|integer|min:0|max:250',
            );

            $inputs = $request->all();

            $validator = \Validator::make($inputs, $rule);

            if ($validator->fails()) {
                return _sendError(422, "Что-то пошло не так! Пожалуйста, попробуйте позже!", $validator->errors());
            }

            $users->language = $inputs['language'];
            $users->save();

            return _sendResponse("Your language updated successful.");
        } catch (\Throwable $th) {
            return _sendError(401, "Что-то пошло не так! Пожалуйста, попробуйте позже!", $th);
        }
    }

    /**
     * UPDATE User role
     */

    public function SetUserRole(Request $request)
    {
        //Auth::guard('sanctum')->user();
        //Auth::user()->id;
        try {
            //Get user for update
            //$user = User::where('email', $request['email'])->firstOrFail();
            //$users = User::findOrFail(Auth::user()->id);
            $users = UserRole::findOrFail(Auth::user()->id);

            $rule = array(
                //'user_id' => 'required',
                'role' => 'required|integer|between:4,5',
                //'user_image' => 'required|mimes:png,PNG,JPG,jpg,jpeg|max:4096',
            );

            $inputs = $request->all();

            $validator = \Validator::make($inputs, $rule);

            if ($validator->fails()) {
                return _sendError(422, "Что-то пошло не так! Пожалуйста, попробуйте позже!", $validator->errors());
            }

            $users->x_roles_id = $inputs['role'];
            $users->save();

            return _sendResponse("Your role updated successful.");
        } catch (\Throwable $th) {
            return _sendError(401, "Что-то пошло не так! Пожалуйста, попробуйте позже!", $th);
        }
    }

    /**
     * Get quiz for user
     */

    public function PastTestClient(Request $request)
    {
        try {
            $personInfo = $request->id ? PersonInfo::findOrFail($request->id) : new PersonInfo;

            $rule = [
                'length' => 'required|integer|between:100,260',
                'weight' => 'required',
                'working_out' => 'required|integer|between:0,7',
                'fitness_goals.*.goal_id' => 'required|integer',
            ];

            $inputs = $request->all();

            $validator = \Validator::make($inputs, $rule);

            if ($validator->fails()) {
                return _sendError(422, "Что-то пошло не так! Пожалуйста, попробуйте позже!", $validator->errors());
            }
            $personInfo->user_id = Auth::user()->id;
            $personInfo->length = $inputs['length'];
            $personInfo->weight = (float) $inputs['weight'];
            $personInfo->working_out = $inputs['working_out'];
            $personInfo->save();

            foreach ($request->fitness_goals as $key => $value) {
                //Auth::user()->id
                $personGoals = PersonFitnessGoal::where(['user_id' => Auth::user()->id, 'goal_id' => $value["goal_id"]])->first() ?? new PersonFitnessGoal;

                $personGoals->user_id = Auth::user()->id;
                $personGoals->goal_id = $value["goal_id"];
                $personGoals->active = $value["active"];
                $personGoals->save();
            }

            return _sendResponse("Your goals updated successful.");
        } catch (\Throwable $th) {
            return _sendError(401, "Что-то пошло не так! Пожалуйста, попробуйте позже!", $th);
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

            $data = User::findOrFail($dataID);
            $data->delete();
            $message = "Данные успешно удалены";
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
            $data = User::scopeDeleteIt($dataID, $inputs["is_active"] == true ? 1 : 0, $inputs["deleted"] == true ? 1 : 0);
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


    public function setUserAccess(Request $request)
    {
        try {
            //Starts manual transaction
            \DB::beginTransaction();
            //$inputs = $request->all();
            $dataID = $request->route('DataID');
            $data = User::with('access')->findOrFail($dataID);

            $rules = [
                'access' => 'present|array',
                'access.*.type_id' => 'required|integer',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }
            
            UserAccess::where('member_id', $data->id)->delete();
            foreach ($request->access as $item) {
                $accessSearch = [
                    ["member_id", $data->id],
                    ["type_id", $item['type_id']]
                ];

                $accessData = UserAccess::where($accessSearch)->first() ?? new UserAccess;
                $accessData->user_id = Auth::user()->id;
                $accessData->member_id = $data->id;
                $accessData->type_id = $item['type_id'];
                $accessData->save();
            }
            $data = User::with('access')->findOrFail($dataID);

            \DB::commit();
            $message = "Данные успешно сохранены";
            return _sendResponse(201, $message, $data);
        } catch (\Throwable $error) {
            \DB::rollback();
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }
}
