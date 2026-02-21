<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpCodeMail;
use App\Models\EmailOtp;
use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class OtpPasswordController extends Controller
{
    public function showEmailForm()
    {
        return view('auth.passwords.otp-email');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
        ]);

        $email = strtolower(trim((string) $request->email));

        $rateKey = 'otp:reset:' . $email;
        if (RateLimiter::tooManyAttempts($rateKey, 1)) {
            return back()->with('error', 'Please wait 60 seconds before requesting another OTP.');
        }

        EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', 'reset')
            ->whereNull('verified_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        EmailOtp::create([
            'email' => $email,
            'purpose' => 'reset',
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($email)->send(new OtpCodeMail($code, 'reset', 10));
        RateLimiter::hit($rateKey, 60);

        $request->session()->put('reset.email', $email);

        return redirect()->route('password.otp.verify')->with('success', 'OTP sent to your email.');
    }

    public function resendOtp(Request $request)
    {
        $email = (string) $request->session()->get('reset.email', '');
        if ($email === '') {
            return redirect()->route('password.request');
        }

        $rateKey = 'otp:reset:' . $email;
        if (RateLimiter::tooManyAttempts($rateKey, 1)) {
            return back()->with('error', 'Please wait 60 seconds before requesting another OTP.');
        }

        EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', 'reset')
            ->whereNull('verified_at')
            ->delete();

        $code = (string) random_int(100000, 999999);

        EmailOtp::create([
            'email' => $email,
            'purpose' => 'reset',
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($email)->send(new OtpCodeMail($code, 'reset', 10));
        RateLimiter::hit($rateKey, 60);

        return back()->with('success', 'OTP resent to your email.');
    }

    public function showVerifyForm(Request $request)
    {
        if (!$request->session()->has('reset.email')) {
            return redirect()->route('password.request');
        }

        return view('auth.passwords.otp-verify');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'regex:/^\\d{6}$/'],
        ], [
            'otp.regex' => 'OTP must be a 6-digit code.',
        ]);

        $email = (string) $request->session()->get('reset.email', '');
        if ($email === '') {
            return redirect()->route('password.request');
        }

        $record = EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', 'reset')
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

        $request->session()->put('reset.verified', true);

        return redirect()->route('password.otp.reset');
    }

    public function showResetForm(Request $request)
    {
        if (!$request->session()->get('reset.verified')) {
            return redirect()->route('password.request');
        }

        return view('auth.passwords.otp-reset');
    }

    public function reset(Request $request)
    {
        if (!$request->session()->get('reset.verified')) {
            return redirect()->route('password.request');
        }

        $request->validate([
            'password' => PasswordRules::rules(),
        ], PasswordRules::messages());

        $email = (string) $request->session()->get('reset.email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('password.request')->with('error', 'Email not found.');
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        $request->session()->forget(['reset.email', 'reset.verified']);

        return redirect()->route('login')->with('success', 'Password updated. You can log in now.');
    }
}
