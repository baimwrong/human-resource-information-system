@extends('layouts.guest')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 p-3 mb-2 shadow-sm">
                <i class="bi bi-people-fill fs-2"></i>
            </div>
            <h3 class="fw-bold mb-1">HRIS Enterprise</h3>
            <p class="text-muted small">Human Resource Information & Workforce System</p>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3 text-center">Sign In to Your Account</h5>

                @if(session('info'))
                    <div class="alert alert-info py-2 small alert-dismissible fade show">
                        {{ session('info') }}
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

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" id="emailInput" name="email" class="form-control border-start-0" value="{{ old('email') }}" required autofocus placeholder="name@company.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" id="passwordInput" name="password" class="form-control border-start-0" required placeholder="Enter password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label small text-muted" for="remember">
                                Remember Me
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </form>

                <hr class="my-4 text-muted">

                <!-- Quick Demo Login Selection -->
                <div class="text-center">
                    <p class="small text-muted mb-2 fw-semibold">Quick Demo Login Presets:</p>
                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="setCredentials('admin@enterprise-hris.local', 'Password123!')">
                            <i class="bi bi-shield-lock"></i> Admin
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="setCredentials('hr@enterprise-hris.local', 'Password123!')">
                            <i class="bi bi-person-badge"></i> HR Mgr
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="setCredentials('manager@enterprise-hris.local', 'Password123!')">
                            <i class="bi bi-briefcase"></i> Manager
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setCredentials('employee@enterprise-hris.local', 'Password123!')">
                            <i class="bi bi-person"></i> Employee
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-3 text-muted small">
            &copy; {{ date('Y') }} HRIS Enterprise &bull; Powered by Laravel 13 &amp; PostgreSQL 18
        </div>

    </div>
</div>

<script>
    function setCredentials(email, password) {
        document.getElementById('emailInput').value = email;
        document.getElementById('passwordInput').value = password;
    }
</script>
@endsection
