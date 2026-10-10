@extends('layouts.app')

@section('title', 'Departments')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-building text-primary me-2"></i>Department Management</h4>
        <p class="text-muted small mb-0">Organize company functional units, operational budgets, and leadership.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createDeptModal">
        <i class="bi bi-plus-circle me-1"></i> Add Department
    </button>
</div>

<div class="row g-3">
    @forelse($departments as $dept)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary text-uppercase me-1">{{ $dept->code }}</span>
                        <span class="fw-bold">{{ $dept->name }}</span>
                    </div>
                    <span class="badge {{ $dept->status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ ucfirst($dept->status) }}</span>
                </div>
                <div class="card-body">
                    <p class="text-secondary small mb-3">{{ $dept->description ?? 'No description provided.' }}</p>
                    
                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <span class="text-muted">Headcount:</span>
                        <strong>{{ $dept->employees_count }} employees</strong>
                    </div>
                    <div class="d-flex justify-content-between py-1 border-bottom small">
                        <span class="text-muted">Dept Manager:</span>
                        <span>{{ $dept->manager ? $dept->manager->name : 'Unassigned' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-1 small">
                        <span class="text-muted">Allocated Budget:</span>
                        <strong class="text-success">IDR {{ number_format($dept->budget, 0, ',', '.') }}</strong>
                    </div>
                </div>
                <div class="card-footer bg-white border-top-0 d-flex justify-content-end gap-1 pb-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editDeptModal{{ $dept->id }}">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </button>
                    @if($dept->employees_count == 0)
                        <form action="{{ route('departments.destroy', $dept) }}" method="POST" onsubmit="return confirm('Delete department {{ $dept->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Edit Department Modal -->
            <div class="modal fade" id="editDeptModal{{ $dept->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form action="{{ route('departments.update', $dept) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title fw-bold">Edit Department - {{ $dept->name }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label small fw-semibold">Code</label>
                                        <input type="text" name="code" class="form-control form-control-sm" value="{{ $dept->code }}" required>
                                    </div>
                                    <div class="col-8">
                                        <label class="form-label small fw-semibold">Name</label>
                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $dept->name }}" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Manager</label>
                                        <select name="manager_id" class="form-select form-select-sm">
                                            <option value="">None</option>
                                            @foreach($managers as $mgr)
                                                <option value="{{ $mgr->id }}" {{ $dept->manager_id == $mgr->id ? 'selected' : '' }}>{{ $mgr->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Annual Budget (IDR)</label>
                                        <input type="number" name="budget" class="form-control form-control-sm" value="{{ $dept->budget }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-semibold">Status</label>
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="active" {{ $dept->status === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ $dept->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Description</label>
                                        <textarea name="description" rows="2" class="form-control form-control-sm">{{ $dept->description }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card p-5 text-center text-muted">
                <i class="bi bi-building fs-1 d-block mb-2"></i>
                No departments configured yet. Click "Add Department" to start.
            </div>
        </div>
    @endforelse
</div>

<!-- Create Department Modal -->
<div class="modal fade" id="createDeptModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('departments.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Create New Department</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control form-control-sm" placeholder="ENG" required>
                        </div>
                        <div class="col-8">
                            <label class="form-label small fw-semibold">Department Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Engineering & Technology" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Manager / Supervisor</label>
                            <select name="manager_id" class="form-select form-select-sm">
                                <option value="">Select Manager</option>
                                @foreach($managers as $mgr)
                                    <option value="{{ $mgr->id }}">{{ $mgr->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Budget (IDR)</label>
                            <input type="number" name="budget" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Scope and purpose"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Create Department</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
