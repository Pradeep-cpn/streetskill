<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\EmailOtp;
use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class OtpRegisterController extends Controller
{
    public function showEmailForm()
    {
        return view('auth.register-email');
    }

    public function sendOtp(Request $request)
    {
        $adminEmail = trim((string) config('streetskill.admin_email'));

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
                function ($attribute, $value, $fail) use ($adminEmail) {
                    if ($adminEmail !== '' && strcasecmp((string) $value, $adminEmail) === 0) {
                        $fail('This email is reserved for the primary admin account.');
                    }
                },
            ],
        ]);

        $email = strtolower(trim((string) $request->email));
        $name = trim((string) $request->name);

        $rateKey = 'otp:register:' . $email;
        if (RateLimiter::tooManyAttempts($rateKey, 1)) {
            return back()->with('error', 'Please wait 60 seconds before requesting another OTP.');
        }

        EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', 'register')
            ->whereNull('verified_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        EmailOtp::create([
            'email' => $email,
            'purpose' => 'register',
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($email)->send(new OtpCodeMail($code, 'register', 10));
        } catch (\Throwable $e) {
            logger()->error('Mail failed: ' . $e->getMessage());
        }
        RateLimiter::hit($rateKey, 60);

        $request->session()->put('register.email', $email);
        $request->session()->put('register.name', $name);

        return redirect()->route('register.verify')->with('success', 'OTP sent successfully — check spam too.');
    }

    public function resendOtp(Request $request)
    {
        $email = (string) $request->session()->get('register.email', '');
        $name = (string) $request->session()->get('register.name', '');

        if ($email === '' || $name === '') {
            return redirect()->route('register');
        }

        $rateKey = 'otp:register:' . $email;
        if (RateLimiter::tooManyAttempts($rateKey, 1)) {
            return back()->with('error', 'Please wait 60 seconds before requesting another OTP.');
        }

        EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', 'register')
            ->whereNull('verified_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        EmailOtp::create([
            'email' => $email,
            'purpose' => 'register',
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($email)->send(new OtpCodeMail($code, 'register', 10));
        } catch (\Throwable $e) {
            logger()->error('Mail failed: ' . $e->getMessage());
        }
        RateLimiter::hit($rateKey, 60);

        return back()->with('success', 'OTP resent successfully — check spam too.');
    }

    public function showVerifyForm(Request $request)
    {
        if (!$request->session()->has('register.email')) {
            return redirect()->route('register');
        }

        return view('auth.register-verify');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'regex:/^\\d{6}$/'],
        ], [
            'otp.regex' => 'OTP must be a 6-digit code.',
        ]);

        $email = (string) $request->session()->get('register.email', '');
        if ($email === '') {
            return redirect()->route('register');
        }

        $record = EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', 'register')
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (!$record || $record->expires_at?->isPast()) {
            return back()->with('error', 'OTP expired. Please request a new one.');
        }

        if (!Hash::check($request->otp, $record->code_hash)) {
            $record->increment('attempts');
            return back()->with('error', 'Invalid OTP. Please try again.');
        }

        $record->update(['verified_at' => now()]);

        $request->session()->put('register.verified', true);

        return redirect()->route('register.password');
    }

    public function showPasswordForm(Request $request)
    {
        if (!$request->session()->get('register.verified')) {
            return redirect()->route('register');
        }

        return view('auth.register-password');
    }

    public function store(Request $request)
    {
        if (!$request->session()->get('register.verified')) {
            return redirect()->route('register');
        }

        $request->validate([
            'password' => PasswordRules::rules(),
        ], PasswordRules::messages());

        $email = (string) $request->session()->get('register.email');
        $name = (string) $request->session()->get('register.name');

        $user = User::create([
            'name' => $name,
            'slug' => User::generateUniqueSlug($name),
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($request->password),
        ]);

        $request->session()->forget(['register.email', 'register.name', 'register.verified']);

        Auth::login($user);

        return redirect('/home')->with('success', 'Welcome to StreetSkill!');
    }
}
