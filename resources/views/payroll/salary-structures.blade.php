@extends('layouts.app')

@section('title', 'Salary Structures')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-wallet2 text-primary me-2"></i>Workforce Salary Structures</h4>
        <p class="text-muted small mb-0">Overview of basic salaries, allowances, and configured statutory deductions.</p>
    </div>
    <a href="{{ route('payroll.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Payroll Runs
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Department</th>
                        <th>Basic Salary</th>
                        <th>Housing</th>
                        <th>Transport</th>
                        <th>Meal</th>
                        <th>Total Gross</th>
                        <th>Total Deductions</th>
                        <th>Net Monthly</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        @php $sal = $emp->salaryStructure; @endphp
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $emp->avatar_url }}" class="avatar-circle me-2" alt="">
                                    <div>
                                        <a href="{{ route('employees.show', $emp) }}" class="fw-semibold text-dark text-decoration-none">
                                            {{ $emp->full_name }}
                                        </a>
                                        <div class="text-muted fs-8">{{ $emp->employee_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $emp->department?->name ?? '—' }}</td>
                            <td class="font-monospace fw-semibold">{{ $sal ? 'IDR ' . number_format($sal->basic_salary, 0, ',', '.') : 'Not Set' }}</td>
                            <td>{{ $sal ? 'IDR ' . number_format($sal->house_allowance, 0, ',', '.') : '—' }}</td>
                            <td>{{ $sal ? 'IDR ' . number_format($sal->transport_allowance, 0, ',', '.') : '—' }}</td>
                            <td>{{ $sal ? 'IDR ' . number_format($sal->meal_allowance, 0, ',', '.') : '—' }}</td>
                            <td class="font-monospace text-success fw-bold">{{ $sal ? 'IDR ' . number_format($sal->gross_salary, 0, ',', '.') : '—' }}</td>
                            <td class="font-monospace text-danger">{{ $sal ? '-IDR ' . number_format($sal->total_deduction, 0, ',', '.') : '—' }}</td>
                            <td class="font-monospace text-primary fw-bold">{{ $sal ? 'IDR ' . number_format($sal->net_salary, 0, ',', '.') : '—' }}</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('employees.edit', $emp) }}" class="btn btn-sm btn-light border" title="Edit Salary Structure">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">No employees found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($employees->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $employees->links() }}
        </div>
    @endif
</div>
@endsection
