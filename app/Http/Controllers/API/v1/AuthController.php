<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Models\Drugs\DrugReport;
use App\Models\System\AppSettings;
use App\Models\User;
use App\Models\Users\UserRole;
use Auth;
use Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuthController extends Controller
{

    /**
     * USER REGISTRATION
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'user_mac' => 'required|string|max:100|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                $response = [
                    'status' => 422,
                    //'success' => false,
                    'message' => "Что-то пошло не так! Пожалуйста, попробуйте позже!",
                    'error' => $validator->errors(),
                ];
                return response()->json($response, 422);
            }

            $data = [
                'first_name'      => $request->first_name,
                'last_name'       => $request->last_name,
                'middle_name'     => $request->middle_name,
                'company_name'    => $request->company_name,
                'company_inn'     => $request->company_inn,
                'phone_number'    => $request->phone_number,
                'user_mac'        => $request->user_mac,
                'user_address'    => $request->user_address,
                'email'           => $request->email,
                'password'        => Hash::make($request->password),
                'browserName'     => $request->browserName ?? null,
                'platform'        => $request->platform ?? null,
                'browserLanguage' => $request->browserLanguage ?? null,
            ];

            if ($request->has('platform') && $request->get('user_mac') == 'MAC_MACINTOSH') {
                $data['user_mac'] = $request->user_mac . time();
            }

            $user = User::create($data);

            UserRole::create([
                'user_id' => $user->id,
                'x_roles_id' => 3,
            ]);

            //$token = $user->createToken('auth_token')->plainTextToken;

            $response = [
                'status' => 201,
                //'data' => $user,
                'message' => "Вы успешно зарегистрировались. Пожалуйста, свяжитесь с системным администратором для утверждения вашего профиля.",
                //'access_token' => $token,
                //'token_type' => 'Bearer',
            ];

            return response()->json($response, 201);
        } catch (\Throwable $error) {
            $response = [
                'status' => 419,
                'message' => "Что-то пошло не так! Пожалуйста, попробуйте позже!",
                'error' => $error,
            ];
            return response()->json($response, 419);
        }
    }

    /**
     * USER LOGIN
     */
    public function login(Request $request)
    {
        try {
            if (!Auth::attempt($request->only('email', 'password'))) {
                $response = [
                    'status' => 401,
                    'message' => "Ошибка входа. Электронная почта или пароль неверны. Пожалуйста, попробуйте позже",
                ];
                return response()->json($response, 401);
            }

            $user = User::where('email', $request['email'])->firstOrFail();
            switch ($user) {
                case UserRole::scopeIsAdmin($user->id):
                    return self::AccessToken($user);
                    break;
                case $user->confirmed == false && !UserRole::scopeIsAdmin($user->id):
                    $response = [
                        'status' => 419,
                        'message' => "Ваша учетная запись не активирована! Пожалуйста, свяжитесь с администратором!",
                    ];
                    return response()->json($response, 419);
                    break;
                case $request->has('platform') && str_contains($request->get('platform'), $user->platform) && $user->um_expired_at >= Carbon::now():
                    return self::AccessToken($user);
                    break;
                case $user->user_mac == $request['user_mac'] && $user->um_expired_at >= Carbon::now():
                    return self::AccessToken($user);
                    break;
                case $user->user_mac == $request['user_mac'] && $user->um_expired_at <= Carbon::now():
                    $response = [
                        'status' => 419,
                        'message' => "Срок действия вашей подписки истек. Пожалуйста, свяжитесь с администратором.",
                    ];
                    return response()->json($response, 419);
                    break;
                default:
                    $response = [
                        'status' => 419,
                        'message' => "Что-то пошло не так при входе!. Пожалуйста, свяжитесь с администратором.",
                    ];
                    return response()->json($response, 419);
                    break;
            }
        } catch (\Throwable $error) {
            $response = [
                'status' => 419,
                'message' => "Что-то пошло не так! Пожалуйста, попробуйте позже!",
                'error' => $error,
            ];
            return response()->json($response, 419);
        }
    }

    private function AccessToken(User $user)
    {
        $token = $user->createToken('auth_token')->plainTextToken;
        $dr = DrugReport::where('is_active', 1)->orderBy('created_at', 'DESC')->first();
        $response = [
            'status' => 201,
            'message' => "Последнее обновление базы данных: " . $dr->created_at,
            //'message' => "Вы успешно вошли в систему! ". Carbon::now(),
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
        return response()->json($response, 201);
    }

    public function loginOld(Request $request)
    {
        try {
            if (!Auth::attempt($request->only('email', 'password'))) {
                $response = [
                    'status' => 401,
                    'message' => "Ошибка входа. Электронная почта или пароль неверны. Пожалуйста, попробуйте позже",
                ];
                return response()->json($response, 401);
            }

            $user = User::where('email', $request['email'])->firstOrFail();
            
            if (($user && $user->user_mac == $request['user_mac'] && $user->um_expired_at >= Carbon::now()) || ($user && UserRole::scopeIsAdmin($user->id))) {
                $token = $user->createToken('auth_token')->plainTextToken;
                $dr = DrugReport::where('is_active', 1)->orderBy('created_at', 'DESC')->first();
                $response = [
                    'status' => 201,
                    'message' => "Последнее обновление базы данных: ". $dr->created_at,
                    //'message' => "Вы успешно вошли в систему! ". Carbon::now(),
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ];

                return response()->json($response, 201);
            }
            
            if (($user->confirmed == false && !UserRole::scopeIsAdmin($user->id)) || ($user && $user->user_mac == $request['user_mac'] && $user->um_expired_at <= Carbon::now())) {
                $response = [
                    'status' => 419,
                    'message' => "Срок действия вашей подписки истек. Пожалуйста, свяжитесь с администратором.",
                ];
                return response()->json($response, 419);
            }
            if ($user && $user->user_mac != $request['user_mac']) {
                $response = [
                    'status' => 419,
                    'message' => "Данные пользователя не совпадают. Пожалуйста, обратитесь к системному администратору.",
                ];
                return response()->json($response, 419);
            }
        } catch (\Throwable $error) {
            $response = [
                'status' => 419,
                'message' => "Что-то пошло не так! Пожалуйста, попробуйте позже!",
                'error' => $error,
            ];
            return response()->json($response, 419);
        }
    }

    // method for user logout and delete token
    public function logout()
    {
        auth()->user()->tokens()->delete();
        return [
            'message' => 'You have successfully logged out and the token was successfully deleted',
        ];
    }


    public function getContactInfo() {
        $data = AppSettings::first();
        if (empty($data)) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            $error = "Data not found";
            return _sendError(404, $message, $error);
        }
        $message = "Success";
        return _sendResponse(201, $message, $data);
    }
}
