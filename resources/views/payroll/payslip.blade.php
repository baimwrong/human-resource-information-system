@extends('layouts.app')

@section('title', 'Payslip - ' . $payslip->slip_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-receipt text-primary me-2"></i>Official Salary Payslip</h4>
        <p class="text-muted small mb-0">Generated compensation statement for <strong>{{ $payslip->employee->full_name }}</strong>.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
            <i class="bi bi-printer-fill me-1"></i> Print Payslip / Save PDF
        </button>
    </div>
</div>

@php
    $currency = $payslip->breakdown['metadata']['currency_symbol'] ?? \App\Models\Setting::get('currency_symbol', 'IDR');
    $companyName = $payslip->breakdown['metadata']['company_name'] ?? \App\Models\Setting::get('company_name', 'Acme Global Technologies Inc.');
    $companyAddress = $payslip->breakdown['metadata']['company_address'] ?? \App\Models\Setting::get('company_address', 'Level 24, Cyber Tower One, Jl. HR Rasuna Said, Jakarta 12950');
    $companyPhone = \App\Models\Setting::get('company_phone', '+62 21 555 0199');
    $companyEmail = \App\Models\Setting::get('company_email', 'payroll@acmeglobal.com');
@endphp

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card border p-4 shadow-sm bg-white" style="border-radius: 0.5rem;">
            
            <!-- Company Letterhead Header -->
            <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="bg-primary text-white rounded p-2 d-inline-flex">
                            <i class="bi bi-diagram-3-fill fs-4"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-dark">{{ strtoupper($companyName) }}</h4>
                            <div class="text-secondary small">People Operations &amp; Payroll Department</div>
                        </div>
                    </div>
                    <div class="text-muted fs-8 mt-1">
                        {{ $companyAddress }}<br>
                        Phone: {{ $companyPhone }} &bull; Email: {{ $companyEmail }}
                    </div>
                </div>

                <div class="text-end">
                    <span class="badge bg-light text-dark border font-monospace fs-7 mb-1">{{ $payslip->slip_number }}</span>
                    <h5 class="fw-bold text-primary mb-0">SALARY PAYSLIP</h5>
                    <div class="text-muted small">Period: <strong>{{ $payslip->period_name }}</strong></div>
                    <div class="text-muted fs-8">Status: <span class="badge {{ $payslip->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }} text-uppercase">{{ $payslip->payment_status }}</span></div>
                </div>
            </div>

            <!-- Employee & Disbursement Info Grid -->
            <div class="row g-2 mb-4 bg-light p-3 rounded small">
                <div class="col-sm-6 col-md-3">
                    <span class="text-muted d-block fs-8">Employee Code</span>
                    <strong>{{ $payslip->employee->employee_code }}</strong>
                </div>
                <div class="col-sm-6 col-md-3">
                    <span class="text-muted d-block fs-8">Employee Full Name</span>
                    <strong>{{ $payslip->employee->full_name }}</strong>
                </div>
                <div class="col-sm-6 col-md-3">
                    <span class="text-muted d-block fs-8">Department</span>
                    <strong>{{ $payslip->breakdown['metadata']['department'] ?? ($payslip->employee->department?->name ?? 'N/A') }}</strong>
                </div>
                <div class="col-sm-6 col-md-3">
                    <span class="text-muted d-block fs-8">Designation / Role</span>
                    <strong>{{ $payslip->breakdown['metadata']['designation'] ?? ($payslip->employee->designation?->title ?? 'N/A') }}</strong>
                </div>

                <div class="col-sm-6 col-md-3 mt-2">
                    <span class="text-muted d-block fs-8">Bank Name</span>
                    <strong>{{ $payslip->breakdown['metadata']['bank_name'] ?? ($payslip->employee->bank_name ?? 'N/A') }}</strong>
                </div>
                <div class="col-sm-6 col-md-3 mt-2">
                    <span class="text-muted d-block fs-8">Bank Account</span>
                    <strong class="font-monospace">{{ $payslip->breakdown['metadata']['bank_account'] ?? ($payslip->employee->bank_account_number ?? 'N/A') }}</strong>
                </div>
                <div class="col-sm-6 col-md-3 mt-2">
                    <span class="text-muted d-block fs-8">Tax ID (NPWP)</span>
                    <strong>{{ $payslip->employee->tax_id ?? 'N/A' }}</strong>
                </div>
                <div class="col-sm-6 col-md-3 mt-2">
                    <span class="text-muted d-block fs-8">Payment Date</span>
                    <strong>{{ $payslip->paid_at ? $payslip->paid_at->format('d M Y') : date('d M Y') }}</strong>
                </div>
            </div>

            <!-- Itemized Earnings & Deductions Tables -->
            <div class="row g-4 mb-4">
                <!-- Earnings Column -->
                <div class="col-md-6">
                    <div class="border rounded h-100">
                        <div class="bg-light p-2 border-bottom fw-bold text-success small">
                            <i class="bi bi-plus-circle me-1"></i> A. EARNINGS &amp; ALLOWANCES
                        </div>
                        <table class="table table-sm mb-0">
                            <tbody>
                                @if(isset($payslip->breakdown['earnings']))
                                    @foreach($payslip->breakdown['earnings'] as $earning)
                                        @if($earning['amount'] > 0)
                                            <tr>
                                                <td class="ps-3 text-secondary">{{ $earning['label'] }}</td>
                                                <td class="text-end pe-3 font-monospace">{{ $currency }} {{ number_format($earning['amount'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="ps-3 text-secondary">Basic Salary</td>
                                        <td class="text-end pe-3 font-monospace">{{ $currency }} {{ number_format($payslip->basic_salary, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="ps-3 text-secondary">Allowances</td>
                                        <td class="text-end pe-3 font-monospace">{{ $currency }} {{ number_format($payslip->total_allowance, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th class="ps-3">Total Gross Earnings (A)</th>
                                    <th class="text-end pe-3 font-monospace text-success">{{ $currency }} {{ number_format($payslip->gross_salary, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Deductions Column -->
                <div class="col-md-6">
                    <div class="border rounded h-100">
                        <div class="bg-light p-2 border-bottom fw-bold text-danger small">
                            <i class="bi bi-dash-circle me-1"></i> B. STATUTORY &amp; DEDUCTIONS
                        </div>
                        <table class="table table-sm mb-0">
                            <tbody>
                                @if(isset($payslip->breakdown['deductions']))
                                    @foreach($payslip->breakdown['deductions'] as $deduction)
                                        @if($deduction['amount'] > 0)
                                            <tr>
                                                <td class="ps-3 text-secondary">{{ $deduction['label'] }}</td>
                                                <td class="text-end pe-3 font-monospace text-danger">-{{ $currency }} {{ number_format($deduction['amount'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="ps-3 text-secondary">Total Deductions</td>
                                        <td class="text-end pe-3 font-monospace text-danger">-{{ $currency }} {{ number_format($payslip->total_deduction, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th class="ps-3">Total Deductions (B)</th>
                                    <th class="text-end pe-3 font-monospace text-danger">-{{ $currency }} {{ number_format($payslip->total_deduction, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Net Salary Summary Banner -->
            <div class="bg-primary bg-opacity-10 border border-primary rounded p-3 mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-uppercase small fw-bold text-primary">TOTAL TAKE HOME PAY (NET SALARY = A &minus; B)</span>
                    <h2 class="fw-bold text-dark mb-0 font-monospace">{{ $currency }} {{ number_format($payslip->net_salary, 0, ',', '.') }}</h2>
                </div>
                <div class="text-end">
                    <span class="badge bg-success py-2 px-3 fs-7">
                        <i class="bi bi-check-circle-fill me-1"></i> Transferred to {{ $payslip->payment_method }}
                    </span>
                </div>
            </div>

            <!-- Signatures Section -->
            <div class="row text-center mt-5 pt-3">
                <div class="col-6">
                    <div class="mb-5 pb-2 text-muted small">Prepared &amp; Verified By:</div>
                    <div class="fw-bold border-top d-inline-block pt-1" style="min-width: 200px;">
                        Finance &amp; Payroll Controller
                    </div>
                </div>
                <div class="col-6">
                    <div class="mb-5 pb-2 text-muted small">Received &amp; Acknowledged By:</div>
                    <div class="fw-bold border-top d-inline-block pt-1" style="min-width: 200px;">
                        {{ $payslip->employee->full_name }}
                    </div>
                </div>
            </div>

            <div class="text-center text-muted fs-8 mt-5 pt-3 border-top">
                This is a computer-generated official payslip from HRIS Enterprise. No physical stamp is legally required.
            </div>

        </div>
    </div>
</div>
@endsection
