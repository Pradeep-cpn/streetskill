@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-6">
        <div class="card p-4">
            <h2 class="mb-3 text-center">Forgot Password</h2>
            <p class="text-center mb-4">Enter your email to receive a reset OTP.</p>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus>
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-gradient w-100">Send OTP</button>
            </form>
        </div>
    </div>
</div>
@endsection
