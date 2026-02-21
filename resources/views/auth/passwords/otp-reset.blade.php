@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-6">
        <div class="card p-4">
            <h2 class="mb-3 text-center">Reset Password</h2>
            <p class="text-center mb-4">Password must be 8-12 characters with 1 uppercase, 1 lowercase, 1 number, 1 special.</p>

            <form method="POST" action="{{ route('password.otp.update') }}">
                @csrf

                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <div class="input-group">
                        <input
                            id="password"
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            name="password"
                            required
                            autocomplete="new-password"
                            minlength="8"
                            maxlength="12"
                            pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,12}$"
                            title="8-12 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character."
                        >
                        <button class="btn btn-outline-light password-toggle" type="button" data-toggle-password data-target="#password" aria-label="Show password">
                            <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.6"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/>
                            </svg>
                            <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M10.6 10.6a2.5 2.5 0 0 0 3.3 3.3" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M5.1 5.1C3.4 6.3 2 8 2 8s4 6 10 6c1.4 0 2.7-.2 3.8-.6" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M19 10s-1.2 1.8-3.6 3.3" stroke="currentColor" stroke-width="1.6"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password-confirm" class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input
                            id="password-confirm"
                            type="password"
                            class="form-control @error('password_confirmation') is-invalid @enderror"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            minlength="8"
                            maxlength="12"
                            pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,12}$"
                            title="8-12 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character."
                        >
                        <button class="btn btn-outline-light password-toggle" type="button" data-toggle-password data-target="#password-confirm" aria-label="Show password">
                            <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.6"/>
                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/>
                            </svg>
                            <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M10.6 10.6a2.5 2.5 0 0 0 3.3 3.3" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M5.1 5.1C3.4 6.3 2 8 2 8s4 6 10 6c1.4 0 2.7-.2 3.8-.6" stroke="currentColor" stroke-width="1.6"/>
                                <path d="M19 10s-1.2 1.8-3.6 3.3" stroke="currentColor" stroke-width="1.6"/>
                            </svg>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-gradient w-100">Update Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
