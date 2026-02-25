<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt(
            $this->credentials($request),
            false
        );
    }

    protected function authenticated(Request $request, $user)
    {
        $currentSessionId = $request->session()->getId();

        if (Schema::hasColumn('users', 'current_session_id')) {
            $user->forceFill(['current_session_id' => $currentSessionId])->save();
        }

        $sessionTable = config('session.table', 'sessions');

        DB::table($sessionTable)
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        $this->guard()->logout();

        if ($user && Schema::hasColumn('users', 'current_session_id')) {
            $user->forceFill(['current_session_id' => null])->save();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->loggedOut($request) ?: redirect('/');
    }
}
