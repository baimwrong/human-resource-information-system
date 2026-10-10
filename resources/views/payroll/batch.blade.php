@extends('layouts.app')

@section('title', 'Payroll Batch - ' . $batch->batch_number)

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h4 class="fw-bold mb-0">Payroll Run: {{ $batch->period_name }}</h4>
            <span class="badge bg-light text-dark border font-monospace">{{ $batch->batch_number }}</span>
            <span class="badge {{ $batch->status_badge }} text-capitalize">{{ $batch->status }}</span>
        </div>
        <p class="text-muted small mb-0 mt-1">Processed on {{ $batch->created_at->format('d M Y H:i') }} by {{ $batch->creator->name }}</p>
    </div>

    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Batches List
        </a>
        @if($batch->status !== 'paid')
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#markPaidModal">
                <i class="bi bi-check2-all me-1"></i> Mark Batch as Paid
            </button>

            <!-- Mark Paid Modal -->
            <div class="modal fade" id="markPaidModal" tabindex="-1">
                <div class="modal-dialog">
                    <form action="{{ route('payroll.mark-paid', $batch) }}" method="POST">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title fw-bold">Disburse &amp; Mark Payroll as Paid</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body small">
                                <p>Are you sure you want to mark this batch as <strong>PAID</strong>? All {{ $batch->total_employees }} payslips will be marked as paid.</p>
                                <div class="mb-2">
                                    <label class="form-label fw-semibold">Disbursement Date</label>
                                    <input type="date" name="payment_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold">Disbursement Method</label>
                                    <select name="payment_method" class="form-select form-select-sm">
                                        <option value="bank_transfer">Corporate Bank Transfer (BCA/Mandiri)</option>
                                        <option value="cash">Cashier Disbursement</option>
                                        <option value="cheque">Cheque</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-success">Confirm Disbursement</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Financial Summary Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Total Employees</div>
            <h3 class="fw-bold text-dark mb-0">{{ $batch->total_employees }}</h3>
            <div class="text-muted fs-8">Covered staff</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Gross Salaries</div>
            <h3 class="fw-bold text-primary mb-0">IDR {{ number_format($batch->total_gross, 0, ',', '.') }}</h3>
            <div class="text-muted fs-8">Base + All Allowances</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Total Deductions</div>
            <h3 class="fw-bold text-danger mb-0">-IDR {{ number_format($batch->total_deductions, 0, ',', '.') }}</h3>
            <div class="text-muted fs-8">Tax, BPJS &amp; Unpaid Leaves</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Net Payout Amount</div>
            <h3 class="fw-bold text-success mb-0">IDR {{ number_format($batch->total_net, 0, ',', '.') }}</h3>
            <div class="text-muted fs-8">Total funds transferred</div>
        </div>
    </div>
</div>

<!-- Payslips Table Card -->
<div class="card">
    <div class="card-header bg-white fw-bold">
        <i class="bi bi-receipt me-1"></i> Individual Payslips in Batch ({{ $batch->payslips->count() }})
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Slip Number</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Basic Salary</th>
                        <th>Allowances</th>
                        <th>Deductions</th>
                        <th>Net Salary</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batch->payslips as $slip)
                        <tr>
                            <td class="ps-3 font-monospace fw-semibold">{{ $slip->slip_number }}</td>
                            <td>
                                <div class="fw-semibold">{{ $slip->employee->full_name }}</div>
                                <div class="text-muted fs-8">{{ $slip->employee->employee_code }}</div>
                            </td>
                            <td>{{ $slip->employee->department?->name ?? '—' }}</td>
                            <td>IDR {{ number_format($slip->basic_salary, 0, ',', '.') }}</td>
                            <td class="text-success">+IDR {{ number_format($slip->total_allowance, 0, ',', '.') }}</td>
                            <td class="text-danger">-IDR {{ number_format($slip->total_deduction, 0, ',', '.') }}</td>
                            <td class="fw-bold">IDR {{ number_format($slip->net_salary, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $slip->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }} text-capitalize">
                                    {{ $slip->payment_status }}
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="{{ route('payroll.payslip', $slip) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="bi bi-printer me-1"></i> Print / View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No payslips in this batch.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
