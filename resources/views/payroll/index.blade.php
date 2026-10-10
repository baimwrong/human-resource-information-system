@extends('layouts.app')

@section('title', 'Payroll Batches')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-cash-stack text-primary me-2"></i>Monthly Payroll Batches</h4>
        <p class="text-muted small mb-0">Generate monthly compensation runs, verify earnings &amp; deductions, and track disbursements.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#generatePayrollModal">
        <i class="bi bi-gear-fill me-1"></i> Generate Payroll Run
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Batch Number</th>
                        <th>Period</th>
                        <th>Employees</th>
                        <th>Total Gross (Earnings)</th>
                        <th>Total Deductions</th>
                        <th>Total Net Disbursement</th>
                        <th>Status</th>
                        <th>Payment Date</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td class="ps-3"><span class="badge bg-light text-dark border font-monospace">{{ $batch->batch_number }}</span></td>
                            <td class="fw-bold">{{ $batch->period_name }}</td>
                            <td>{{ $batch->total_employees }} staff</td>
                            <td>IDR {{ number_format($batch->total_gross, 0, ',', '.') }}</td>
                            <td class="text-danger">-IDR {{ number_format($batch->total_deductions, 0, ',', '.') }}</td>
                            <td class="fw-bold text-success">IDR {{ number_format($batch->total_net, 0, ',', '.') }}</td>
                            <td><span class="badge {{ $batch->status_badge }} text-capitalize">{{ $batch->status }}</span></td>
                            <td>{{ $batch->payment_date ? $batch->payment_date->format('d M Y') : '—' }}</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('payroll.batch', $batch) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-card-list me-1"></i> View Payslips
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-wallet2 fs-1 d-block mb-2"></i>
                                No payroll batches generated yet. Click "Generate Payroll Run" to compute your first monthly run.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($batches->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $batches->links() }}
        </div>
    @endif
</div>

<!-- Generate Payroll Modal -->
<div class="modal fade" id="generatePayrollModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('payroll.generate') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Generate Monthly Payroll Run</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        The payroll engine will automatically compute basic salaries, housing, transport, meal allowances, tax, insurance, and deduct unpaid leaves for all active employees.
                    </p>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Month <span class="text-danger">*</span></label>
                            <select name="month" class="form-select form-select-sm" required>
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $currentMonth == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Year <span class="text-danger">*</span></label>
                            <select name="year" class="form-select form-select-sm" required>
                                @for($y = 2024; $y <= 2030; $y++)
                                    <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-lightning-charge me-1"></i> Compute &amp; Generate Run
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
