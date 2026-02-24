@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-6">
        <div class="card p-4">
            <h2 class="mb-3 text-center">Verify Email</h2>
            <p class="text-center mb-4">Enter the 6-digit OTP sent to your email.</p>

            <form method="POST" action="{{ route('register.verify.submit') }}">
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

            <form method="POST" action="{{ route('register.resend') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-outline-light w-100" id="resend-otp" data-cooldown="60">Resend OTP</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const button = document.getElementById('resend-otp');
    if (!button) {
        return;
    }

    let cooldown = Number(button.getAttribute('data-cooldown')) || 60;
    let remaining = cooldown;
    button.disabled = true;
    button.textContent = `Resend OTP (${remaining}s)`;

    const timer = setInterval(function () {
        remaining -= 1;
        if (remaining <= 0) {
            clearInterval(timer);
            button.disabled = false;
            button.textContent = 'Resend OTP';
            return;
        }
        button.textContent = `Resend OTP (${remaining}s)`;
    }, 1000);
})();
</script>
@endpush
