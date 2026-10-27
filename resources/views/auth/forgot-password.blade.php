@extends('layouts.guest')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 p-3 mb-2 shadow-sm">
                <i class="bi bi-shield-lock-fill fs-2"></i>
            </div>
            <h3 class="fw-bold mb-1">Reset Password</h3>
            <p class="text-muted small">Enter your email to receive a password reset link</p>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                @if(session('status'))
                    <div class="alert alert-success py-2 small alert-dismissible fade show">
                        <i class="bi bi-check-circle me-1"></i> {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger py-2 small alert-dismissible fade show">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Registered Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control border-start-0" value="{{ old('email') }}" required autofocus placeholder="name@company.com">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold mb-3">
                        <i class="bi bi-send me-1"></i> Send Password Reset Link
                    </button>

                    <div class="text-center">
                        <a href="{{ route('login') }}" class="small text-muted text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Back to Sign In
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-3 text-muted small">
            &copy; {{ date('Y') }} HRIS Enterprise &bull; Secure Access
        </div>

    </div>
</div>
@endsection
