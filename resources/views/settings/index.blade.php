@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-gear text-primary me-2"></i>Global System Settings</h4>
        <p class="text-muted small mb-0">Configure company organization identity, localization, and operating parameters.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-sliders me-1"></i> General Configuration
            </div>
            <div class="card-body p-4">
                <form action="{{ route('settings.update') }}" method="POST">
                    @csrf

                    <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-building me-1"></i> Company Profile</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Legal Company Name</label>
                            <input type="text" name="company_name" class="form-control form-control-sm" value="{{ $settings['company_name'] ?? 'Acme Global Technologies Inc.' }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Official HR / Contact Email</label>
                            <input type="email" name="company_email" class="form-control form-control-sm" value="{{ $settings['company_email'] ?? 'hr@company.com' }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Corporate Phone Number</label>
                            <input type="text" name="company_phone" class="form-control form-control-sm" value="{{ $settings['company_phone'] ?? '+62 21 555 0199' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primary Office Address</label>
                            <input type="text" name="company_address" class="form-control form-control-sm" value="{{ $settings['company_address'] ?? 'Cyber Tower One, Jakarta' }}">
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3 text-primary border-top pt-3"><i class="bi bi-globe me-1"></i> Localization &amp; Payroll Parameters</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Currency Code</label>
                            <input type="text" name="currency_symbol" class="form-control form-control-sm font-monospace" value="{{ $settings['currency_symbol'] ?? 'IDR' }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Operating Timezone</label>
                            <input type="text" name="timezone" class="form-control form-control-sm font-monospace" value="{{ $settings['timezone'] ?? 'Asia/Jakarta' }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Standard Work Days / Month</label>
                            <input type="number" name="work_days_per_month" class="form-control form-control-sm" value="{{ $settings['work_days_per_month'] ?? '22' }}" required>
                            <div class="form-text fs-8">Used for unpaid leave daily rate divisor</div>
                        </div>
                    </div>

                    <div class="text-end border-top pt-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i> Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
