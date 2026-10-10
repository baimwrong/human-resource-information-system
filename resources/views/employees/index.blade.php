@extends('layouts.app')

@section('title', 'Employee Directory')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i>Employee Directory</h4>
        <p class="text-muted small mb-0">Manage workforce profiles, contracts, and organizational roles.</p>
    </div>

    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('employees.export') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-download me-1"></i> Export CSV
        </a>
        <a href="{{ route('bulk-import.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-cloud-arrow-up me-1"></i> Bulk Import
        </a>
        <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus-fill me-1"></i> Add Employee
        </a>
    </div>
</div>

<!-- Filters Card -->
<div class="card mb-3">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('employees.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, employee code, or email..." value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-md-3">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="on_leave" {{ request('status') == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                    <option value="resigned" {{ request('status') == 'resigned' ? 'selected' : '' }}>Resigned</option>
                    <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>Terminated</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                @if(request()->hasAny(['search', 'department_id', 'status']))
                    <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Employee Table Card -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Code</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Join Date</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $emp->avatar_url }}" class="avatar-circle me-2" alt="{{ $emp->full_name }}">
                                    <div>
                                        <a href="{{ route('employees.show', $emp) }}" class="fw-semibold text-dark text-decoration-none">
                                            {{ $emp->full_name }}
                                        </a>
                                        <div class="text-muted fs-8">{{ $emp->user?->email ?? 'No portal account' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $emp->employee_code }}</span></td>
                            <td>{{ $emp->department?->name ?? '—' }}</td>
                            <td>{{ $emp->designation?->title ?? '—' }}</td>
                            <td><span class="text-capitalize">{{ $emp->employment_type }}</span></td>
                            <td>
                                @if($emp->employment_status === 'active')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                @elseif($emp->employment_status === 'on_leave')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">On Leave</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ ucfirst($emp->employment_status) }}</span>
                                @endif
                            </td>
                            <td>{{ $emp->join_date ? $emp->join_date->format('d M Y') : '—' }}</td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('employees.show', $emp) }}" class="btn btn-light border" title="View Profile">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('employees.edit', $emp) }}" class="btn btn-light border" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-light border text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $emp->id }}" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>

                                <!-- Delete Confirmation Modal -->
                                <div class="modal fade text-start" id="deleteModal{{ $emp->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title fw-bold">Delete Employee</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body small">
                                                Are you sure you want to remove <strong>{{ $emp->full_name }}</strong> ({{ $emp->employee_code }})? This will deactivate their portal login.
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                <form action="{{ route('employees.destroy', $emp) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">Confirm Delete</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-people fs-2 d-block mb-1"></i>
                                No employees found matching the filter criteria.
                            </td>
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
