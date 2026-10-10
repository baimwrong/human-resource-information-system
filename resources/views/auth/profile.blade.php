@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-badge text-primary me-2"></i>My Account Profile</h4>
        <p class="text-muted small mb-0">Update your user credentials and change account password.</p>
    </div>
</div>

<div class="row g-4 justify-content-center">
    <!-- Account Information -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-person me-1"></i> Account Details
            </div>
            <div class="card-body p-4">
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Display Name</label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control form-control-sm" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">System Role</label>
                        <input type="text" class="form-control form-control-sm bg-light text-capitalize" value="{{ str_replace('_', ' ', $user->role) }}" readonly>
                    </div>

                    @if($user->employee)
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Employee ID &amp; Department</label>
                            <input type="text" class="form-control form-control-sm bg-light" value="{{ $user->employee->employee_code }} &bull; {{ $user->employee->department?->name ?? 'No Dept' }} ({{ $user->employee->designation?->title ?? 'No Designation' }})" readonly>
                        </div>
                    @endif

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-save me-1"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Change -->
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-lock me-1"></i> Security &amp; Password
            </div>
            <div class="card-body p-4">
                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Current Password <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control form-control-sm" required placeholder="Verify current password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control form-control-sm" required placeholder="Minimum 8 characters">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control form-control-sm" required placeholder="Repeat new password">
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-danger btn-sm px-3">
                            <i class="bi bi-shield-lock me-1"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
