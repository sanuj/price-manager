<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    public function loginWithEasyECom(Request $request)
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->validateTokenLogin($request) !== true) {
            return $this->sendFailedLoginResponse($request);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return $this->sendFailedLoginResponse($request);
        }

        $this->guard()->login($user);

        return $this->sendLoginResponse($request);
    }

    /**
     * Validate Login using hashed token.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $service
     *
     * @return bool
     */
    protected function validateTokenLogin(Request $request, string $service = 'easyecom'): bool
    {
        $validator = $this->getValidationFactory()->make($request->all(), [
            'email' => 'required|email',
            'expires' => 'required|numeric',
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return false;
        }

        $email = (string)$request->input('email');
        $expires = (int)$request->input('expires');
        $token = (string)$request->input('token');
        $secret = config("services.${service}.secret");

        $hash = hash_hmac('sha512', "${email}:${expires}", $secret);

        return Carbon::createFromTimestamp($expires)->isFuture() and hash_equals($hash, $token);
    }
}
