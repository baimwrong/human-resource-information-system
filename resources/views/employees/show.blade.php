@extends('layouts.app')

@section('title', $employee->full_name . ' - Profile')

@section('content')
<!-- Profile Header Banner Card -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ $employee->avatar_url }}" class="rounded-circle shadow-sm" style="width: 72px; height: 72px; object-fit: cover;" alt="{{ $employee->full_name }}">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="fw-bold mb-0">{{ $employee->full_name }}</h4>
                        <span class="badge bg-light text-dark border">{{ $employee->employee_code }}</span>
                        @if($employee->employment_status === 'active')
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($employee->employment_status) }}</span>
                        @endif
                    </div>
                    <div class="text-secondary small mt-1">
                        <span><i class="bi bi-briefcase me-1"></i> {{ $employee->designation?->title ?? 'No Designation' }}</span>
                        <span class="mx-2">&bull;</span>
                        <span><i class="bi bi-building me-1"></i> {{ $employee->department?->name ?? 'No Department' }}</span>
                        <span class="mx-2">&bull;</span>
                        <span><i class="bi bi-person-badge me-1"></i> Portal: <strong class="text-capitalize">{{ $employee->user?->role ?? 'N/A' }}</strong></span>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                @if(Auth::user()->isHR())
                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-pencil me-1"></i> Edit Profile
                    </a>
                @endif
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Directory
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-3" id="profileTab" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button">
            <i class="bi bi-person-lines-fill me-1"></i> Overview
        </button>
    </li>
    @if($canViewFinancials)
    <li class="nav-item">
        <button class="nav-link" id="salary-tab" data-bs-toggle="tab" data-bs-target="#salary" type="button">
            <i class="bi bi-wallet2 me-1"></i> Salary Structure
        </button>
    </li>
    @endif
    <li class="nav-item">
        <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button">
            <i class="bi bi-clock-history me-1"></i> Attendance Logs
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="leaves-tab" data-bs-toggle="tab" data-bs-target="#leaves" type="button">
            <i class="bi bi-calendar2-check me-1"></i> Leaves ({{ $employee->leaveBalances->sum('allocated_days') }}d)
        </button>
    </li>
    @if($canViewFinancials)
    <li class="nav-item">
        <button class="nav-link" id="payslips-tab" data-bs-toggle="tab" data-bs-target="#payslips" type="button">
            <i class="bi bi-receipt me-1"></i> Payslips ({{ $employee->payslips ? $employee->payslips->count() : 0 }})
        </button>
    </li>
    @endif
</ul>

<!-- Tabs Content -->
<div class="tab-content" id="profileTabContent">
    
    <!-- 1. OVERVIEW TAB -->
    <div class="tab-pane fade show active" id="overview">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light fw-bold"><i class="bi bi-info-circle me-1"></i> Personal &amp; Contact</div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr><th class="ps-3 text-secondary" style="width: 40%;">Email Address</th><td>{{ $employee->user?->email ?? 'N/A' }}</td></tr>
                                <tr><th class="ps-3 text-secondary">Phone Number</th><td>{{ $employee->phone ?? '—' }}</td></tr>
                                <tr><th class="ps-3 text-secondary">Gender</th><td class="text-capitalize">{{ $employee->gender }}</td></tr>
                                <tr><th class="ps-3 text-secondary">Date of Birth</th><td>{{ $employee->date_of_birth ? $employee->date_of_birth->format('d F Y') : '—' }}</td></tr>
                                <tr><th class="ps-3 text-secondary">National ID (NIK)</th><td>{{ $employee->national_id ?? '—' }}</td></tr>
                                <tr><th class="ps-3 text-secondary">Emergency Contact</th><td>{{ $employee->emergency_contact_name ?? '—' }} ({{ $employee->emergency_contact_phone ?? '—' }})</td></tr>
                                <tr><th class="ps-3 text-secondary">Address</th><td>{{ $employee->address ?? '—' }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light fw-bold"><i class="bi bi-briefcase me-1"></i> Employment &amp; Banking</div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr><th class="ps-3 text-secondary" style="width: 40%;">Employment Type</th><td class="text-capitalize">{{ $employee->employment_type }}</td></tr>
                                <tr><th class="ps-3 text-secondary">Join Date</th><td>{{ $employee->join_date ? $employee->join_date->format('d F Y') : '—' }}</td></tr>
                                <tr><th class="ps-3 text-secondary">Probation End</th><td>{{ $employee->probation_end_date ? $employee->probation_end_date->format('d F Y') : '—' }}</td></tr>
                                <tr><th class="ps-3 text-secondary">Reporting Manager</th><td>{{ $employee->manager ? $employee->manager->full_name : 'None (Top Level)' }}</td></tr>
                                <tr><th class="ps-3 text-secondary">Assigned Shift</th><td>{{ $employee->shift ? $employee->shift->name : 'Regular' }}</td></tr>
                                @if($canViewFinancials)
                                <tr><th class="ps-3 text-secondary">Bank Details</th><td>{{ $employee->bank_name ?? 'N/A' }} &bull; {{ $employee->bank_account_number ?? 'N/A' }}</td></tr>
                                <tr><th class="ps-3 text-secondary">Account Holder</th><td>{{ $employee->bank_holder_name ?? '—' }}</td></tr>
                                <tr><th class="ps-3 text-secondary">Tax ID (NPWP)</th><td>{{ $employee->tax_id ?? '—' }}</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($canViewFinancials)
    <!-- 2. SALARY STRUCTURE TAB -->
    <div class="tab-pane fade" id="salary">
        @php $salary = $employee->salaryStructure; @endphp
        @if($salary)
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success bg-opacity-10 text-success fw-bold">
                            <i class="bi bi-plus-circle me-1"></i> Earnings &amp; Allowances
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr><td class="ps-3">Basic Monthly Salary</td><td class="text-end pe-3 fw-bold">IDR {{ number_format($salary->basic_salary, 0, ',', '.') }}</td></tr>
                                    <tr><td class="ps-3">Housing Allowance</td><td class="text-end pe-3">IDR {{ number_format($salary->house_allowance, 0, ',', '.') }}</td></tr>
                                    <tr><td class="ps-3">Transport Allowance</td><td class="text-end pe-3">IDR {{ number_format($salary->transport_allowance, 0, ',', '.') }}</td></tr>
                                    <tr><td class="ps-3">Meal Allowance</td><td class="text-end pe-3">IDR {{ number_format($salary->meal_allowance, 0, ',', '.') }}</td></tr>
                                    <tr><td class="ps-3">Other Allowances</td><td class="text-end pe-3">IDR {{ number_format($salary->other_allowances, 0, ',', '.') }}</td></tr>
                                    <tr class="table-light"><th class="ps-3">Total Gross Earnings</th><th class="text-end pe-3 text-success">IDR {{ number_format($salary->gross_salary, 0, ',', '.') }}</th></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-danger bg-opacity-10 text-danger fw-bold">
                            <i class="bi bi-dash-circle me-1"></i> Deductions
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr><td class="ps-3">Tax Deduction (PPh 21)</td><td class="text-end pe-3">IDR {{ number_format($salary->tax_deduction, 0, ',', '.') }}</td></tr>
                                    <tr><td class="ps-3">Social &amp; Health Insurance</td><td class="text-end pe-3">IDR {{ number_format($salary->insurance_deduction, 0, ',', '.') }}</td></tr>
                                    <tr><td class="ps-3">Other Deductions</td><td class="text-end pe-3">IDR {{ number_format($salary->other_deductions, 0, ',', '.') }}</td></tr>
                                    <tr class="table-light"><th class="ps-3">Total Deductions</th><th class="text-end pe-3 text-danger">IDR {{ number_format($salary->total_deduction, 0, ',', '.') }}</th></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Net Salary Callout Box -->
                    <div class="card border-primary border-2 bg-primary bg-opacity-10 mt-3">
                        <div class="card-body py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-uppercase small fw-bold text-primary">Estimated Net Monthly Payout</span>
                                <h3 class="fw-bold text-dark mb-0">IDR {{ number_format($salary->net_salary, 0, ',', '.') }}</h3>
                            </div>
                            <i class="bi bi-cash-stack fs-1 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="card p-4 text-center text-muted">
                <i class="bi bi-wallet2 fs-2 d-block mb-1"></i>
                No salary structure configured for this employee.
            </div>
        @endif
    </div>
    @endif

    <!-- 3. ATTENDANCE LOGS TAB -->
    <div class="tab-pane fade" id="attendance">
        <div class="card">
            <div class="card-header bg-light fw-bold">
                <i class="bi bi-calendar3 me-1"></i> Recent Attendance Records (Past 30 Days)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Date</th>
                                <th>Clock In</th>
                                <th>Clock Out</th>
                                <th>Status</th>
                                <th>Work Hours</th>
                                <th>Overtime</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employee->attendances as $att)
                                <tr>
                                    <td class="ps-3">{{ $att->date->format('D, d M Y') }}</td>
                                    <td>{{ $att->clock_in ? $att->clock_in->format('H:i:s') : '—' }}</td>
                                    <td>{{ $att->clock_out ? $att->clock_out->format('H:i:s') : '—' }}</td>
                                    <td><span class="badge {{ $att->status_badge }} text-capitalize">{{ $att->status }}</span></td>
                                    <td>{{ $att->work_hours }} hrs</td>
                                    <td>{{ $att->overtime_hours > 0 ? $att->overtime_hours . ' hrs' : '—' }}</td>
                                    <td class="small text-muted">{{ $att->notes ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-3 text-muted">No attendance records yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. LEAVES TAB -->
    <div class="tab-pane fade" id="leaves">
        <div class="row g-3 mb-3">
            @foreach($employee->leaveBalances as $balance)
                <div class="col-sm-6 col-md-4 col-xl-2">
                    <div class="card text-center p-3 h-100">
                        <div class="text-muted fs-8 text-uppercase fw-semibold">{{ $balance->leaveType->name }}</div>
                        <h3 class="fw-bold text-primary mb-1">{{ $balance->remaining_days }}</h3>
                        <div class="text-muted fs-8">{{ $balance->used_days }} used / {{ $balance->allocated_days }} total</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card">
            <div class="card-header bg-light fw-bold"><i class="bi bi-clock-history me-1"></i> Leave Applications History</div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Leave Type</th>
                            <th>Dates</th>
                            <th>Total Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->leaveRequests as $req)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $req->leaveType->name }}</td>
                                <td>{{ $req->start_date->format('d M') }} &ndash; {{ $req->end_date->format('d M Y') }}</td>
                                <td>{{ $req->total_days }} days</td>
                                <td class="small">{{ $req->reason }}</td>
                                <td><span class="badge {{ $req->status_badge }} text-capitalize">{{ $req->status }}</span></td>
                                <td class="small text-muted">{{ $req->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-3 text-muted">No leave applications found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($canViewFinancials)
    <!-- 5. PAYSLIPS TAB -->
    <div class="tab-pane fade" id="payslips">
        <div class="card">
            <div class="card-header bg-light fw-bold"><i class="bi bi-receipt me-1"></i> Issued Monthly Payslips</div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Slip Number</th>
                            <th>Period</th>
                            <th>Basic Salary</th>
                            <th>Total Allowances</th>
                            <th>Total Deductions</th>
                            <th>Net Pay</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->payslips ?? [] as $ps)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $ps->slip_number }}</td>
                                <td>{{ $ps->period_name }}</td>
                                <td>IDR {{ number_format($ps->basic_salary, 0, ',', '.') }}</td>
                                <td class="text-success">+{{ number_format($ps->total_allowance, 0, ',', '.') }}</td>
                                <td class="text-danger">-{{ number_format($ps->total_deduction, 0, ',', '.') }}</td>
                                <td class="fw-bold">IDR {{ number_format($ps->net_salary, 0, ',', '.') }}</td>
                                <td><span class="badge {{ $ps->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }} text-capitalize">{{ $ps->payment_status }}</span></td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('payroll.payslip', $ps) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View Slip
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-3 text-muted">No payslips generated yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
