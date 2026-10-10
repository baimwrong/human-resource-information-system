@extends('layouts.app')

@section('title', 'Onboard New Employee')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-plus-fill text-primary me-2"></i>Onboard New Employee</h4>
        <p class="text-muted small mb-0">Create employee record, system user account, and initialize compensation structure.</p>
    </div>
    <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Directory
    </a>
</div>

<form action="{{ route('employees.store') }}" method="POST">
    @csrf

    <div class="row g-4">
        <!-- 1. Personal & Account Information -->
        <div class="col-lg-6">
            <div class="card h-100 mb-0">
                <div class="card-header bg-light">
                    <i class="bi bi-person-lines-fill me-1 text-primary"></i> 1. Personal &amp; Account Details
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control form-control-sm" value="{{ old('first_name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control form-control-sm" value="{{ old('last_name') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Portal Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control form-control-sm" value="{{ old('email') }}" required placeholder="email@company.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Initial Password</label>
                            <input type="password" name="password" class="form-control form-control-sm" placeholder="Leave empty for default (Password123!)">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Portal Access Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select form-select-sm" required>
                                <option value="employee" {{ old('role') == 'employee' ? 'selected' : '' }}>Employee (Self-Service)</option>
                                <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Manager (Approvals)</option>
                                <option value="hr_manager" {{ old('role') == 'hr_manager' ? 'selected' : '' }}>HR Manager</option>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Super Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select form-select-sm" required>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control form-control-sm" value="{{ old('date_of_birth') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control form-control-sm" value="{{ old('phone') }}" placeholder="+62 812 ...">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">National ID (NIK / SSN)</label>
                            <input type="text" name="national_id" class="form-control form-control-sm" value="{{ old('national_id') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Emergency Contact</label>
                            <input type="text" name="emergency_contact_name" class="form-control form-control-sm" value="{{ old('emergency_contact_name') }}" placeholder="Name & relation">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Residential Address</label>
                            <textarea name="address" rows="2" class="form-control form-control-sm" placeholder="Street address, city, postal code">{{ old('address') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Organizational Placement -->
        <div class="col-lg-6">
            <div class="card h-100 mb-0">
                <div class="card-header bg-light">
                    <i class="bi bi-briefcase-fill me-1 text-primary"></i> 2. Organizational Placement
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Employee Code <span class="text-danger">*</span></label>
                            <input type="text" name="employee_code" class="form-control form-control-sm fw-bold" value="{{ old('employee_code', $nextCode) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Department</label>
                            <select name="department_id" class="form-select form-select-sm">
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Job Designation</label>
                            <select name="designation_id" class="form-select form-select-sm">
                                <option value="">Select Designation</option>
                                @foreach($designations as $desig)
                                    <option value="{{ $desig->id }}" {{ old('designation_id') == $desig->id ? 'selected' : '' }}>{{ $desig->title }} ({{ $desig->pay_grade }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Direct Manager / Supervisor</label>
                            <select name="manager_id" class="form-select form-select-sm">
                                <option value="">Select Manager</option>
                                @foreach($managers as $mgr)
                                    <option value="{{ $mgr->id }}" {{ old('manager_id') == $mgr->id ? 'selected' : '' }}>{{ $mgr->full_name }} ({{ $mgr->employee_code }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Assigned Shift</label>
                            <select name="shift_id" class="form-select form-select-sm">
                                <option value="">Select Shift</option>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}" {{ old('shift_id') == $shift->id ? 'selected' : '' }}>{{ $shift->name }} ({{ substr($shift->start_time, 0, 5) }} - {{ substr($shift->end_time, 0, 5) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Employment Type <span class="text-danger">*</span></label>
                            <select name="employment_type" class="form-select form-select-sm" required>
                                <option value="permanent" {{ old('employment_type') == 'permanent' ? 'selected' : '' }}>Permanent</option>
                                <option value="probation" {{ old('employment_type') == 'probation' ? 'selected' : '' }}>Probation</option>
                                <option value="contract" {{ old('employment_type') == 'contract' ? 'selected' : '' }}>Contract</option>
                                <option value="intern" {{ old('employment_type') == 'intern' ? 'selected' : '' }}>Internship</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Employment Status <span class="text-danger">*</span></label>
                            <select name="employment_status" class="form-select form-select-sm" required>
                                <option value="active" {{ old('employment_status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="on_leave" {{ old('employment_status') == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                                <option value="resigned" {{ old('employment_status') == 'resigned' ? 'selected' : '' }}>Resigned</option>
                                <option value="terminated" {{ old('employment_status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Joining Date <span class="text-danger">*</span></label>
                            <input type="date" name="join_date" class="form-control form-control-sm" value="{{ old('join_date', date('Y-m-d')) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Probation End Date</label>
                            <input type="date" name="probation_end_date" class="form-control form-control-sm" value="{{ old('probation_end_date') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Compensation & Allowances -->
        <div class="col-lg-6">
            <div class="card h-100 mb-0">
                <div class="card-header bg-light">
                    <i class="bi bi-wallet-fill me-1 text-primary"></i> 3. Monthly Compensation &amp; Deductions
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Basic Monthly Salary <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">IDR</span>
                                <input type="number" step="1000" name="basic_salary" class="form-control" value="{{ old('basic_salary', 0) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Housing Allowance</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">IDR</span>
                                <input type="number" step="1000" name="house_allowance" class="form-control" value="{{ old('house_allowance', 0) }}">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Transport Allowance</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">IDR</span>
                                <input type="number" step="1000" name="transport_allowance" class="form-control" value="{{ old('transport_allowance', 0) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Meal Allowance</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">IDR</span>
                                <input type="number" step="1000" name="meal_allowance" class="form-control" value="{{ old('meal_allowance', 0) }}">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tax Deduction (PPh 21)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">IDR</span>
                                <input type="number" step="1000" name="tax_deduction" class="form-control" value="{{ old('tax_deduction', 0) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Health &amp; Social Insurance</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">IDR</span>
                                <input type="number" step="1000" name="insurance_deduction" class="form-control" value="{{ old('insurance_deduction', 0) }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Banking & Tax Info -->
        <div class="col-lg-6">
            <div class="card h-100 mb-0">
                <div class="card-header bg-light">
                    <i class="bi bi-bank2 me-1 text-primary"></i> 4. Banking &amp; Tax Information
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control form-control-sm" value="{{ old('bank_name') }}" placeholder="e.g. BCA, Mandiri, BNI">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Bank Account Number</label>
                            <input type="text" name="bank_account_number" class="form-control form-control-sm" value="{{ old('bank_account_number') }}" placeholder="Account digits">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Bank Account Holder Name</label>
                            <input type="text" name="bank_holder_name" class="form-control form-control-sm" value="{{ old('bank_holder_name') }}" placeholder="Name in passbook">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tax Identification (NPWP)</label>
                            <input type="text" name="tax_id" class="form-control form-control-sm" value="{{ old('tax_id') }}" placeholder="XX.XXX.XXX.X-XXX.XXX">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 text-end">
            <a href="{{ route('employees.index') }}" class="btn btn-light border me-2">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle me-1"></i> Save Employee Profile
            </button>
        </div>
    </div>
</form>
@endsection
