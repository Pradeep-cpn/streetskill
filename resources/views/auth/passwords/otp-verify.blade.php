@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-6">
        <div class="card p-4">
            <h2 class="mb-3 text-center">Verify OTP</h2>
            <p class="text-center mb-4">Enter the 6-digit OTP sent to your email.</p>

            <form method="POST" action="{{ route('password.otp.check') }}">
                @csrf

                <div class="mb-4">
                    <label for="otp" class="form-label">OTP Code</label>
                    <input id="otp" type="text" class="form-control @error('otp') is-invalid @enderror" name="otp" inputmode="numeric" maxlength="6" required autofocus>
                    @error('otp')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-gradient w-100">Verify OTP</button>
            </form>

            <form method="POST" action="{{ route('password.otp.resend') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-outline-light w-100">Resend OTP (60s cooldown)</button>
            </form>
        </div>
    </div>
</div>
@endsection
