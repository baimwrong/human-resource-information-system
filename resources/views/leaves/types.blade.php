@extends('layouts.app')

@section('title', 'Leave Types Configuration')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-card-checklist text-primary me-2"></i>Leave Types &amp; Policies</h4>
        <p class="text-muted small mb-0">Configure annual statutory time-off entitlements, paid status, and documentation policies.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTypeModal">
        <i class="bi bi-plus-circle me-1"></i> Add Leave Type
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Code</th>
                        <th>Leave Type Name</th>
                        <th>Days Allowed (Annual)</th>
                        <th>Paid Status</th>
                        <th>Doctor / Document Proof</th>
                        <th>Total Requests</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveTypes as $lt)
                        <tr>
                            <td class="ps-3"><span class="badge bg-light text-dark border">{{ $lt->code }}</span></td>
                            <td class="fw-semibold">{{ $lt->name }}</td>
                            <td><strong>{{ $lt->days_allowed }}</strong> days / year</td>
                            <td>
                                @if($lt->is_paid)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Paid Leave</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Unpaid</span>
                                @endif
                            </td>
                            <td>
                                @if($lt->requires_attachment)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-paperclip"></i> Mandatory</span>
                                @else
                                    <span class="text-muted small">Optional</span>
                                @endif
                            </td>
                            <td>{{ $lt->requests_count }} applications</td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editTypeModal{{ $lt->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if($lt->requests_count == 0)
                                    <form action="{{ route('leave-types.destroy', $lt) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete leave type {{ $lt->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif

                                <!-- Edit Leave Type Modal -->
                                <div class="modal fade text-start" id="editTypeModal{{ $lt->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('leave-types.update', $lt) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold">Edit Leave Policy - {{ $lt->name }}</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-2">
                                                        <div class="col-4">
                                                            <label class="form-label small fw-semibold">Code</label>
                                                            <input type="text" name="code" class="form-control form-control-sm" value="{{ $lt->code }}" required>
                                                        </div>
                                                        <div class="col-8">
                                                            <label class="form-label small fw-semibold">Leave Name</label>
                                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $lt->name }}" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Days Allowed / Year</label>
                                                            <input type="number" name="days_allowed" class="form-control form-control-sm" value="{{ $lt->days_allowed }}" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Paid Status</label>
                                                            <select name="is_paid" class="form-select form-select-sm">
                                                                <option value="1" {{ $lt->is_paid ? 'selected' : '' }}>Paid</option>
                                                                <option value="0" {{ !$lt->is_paid ? 'selected' : '' }}>Unpaid</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label small fw-semibold">Require Supporting Attachment?</label>
                                                            <select name="requires_attachment" class="form-select form-select-sm">
                                                                <option value="0" {{ !$lt->requires_attachment ? 'selected' : '' }}>No</option>
                                                                <option value="1" {{ $lt->requires_attachment ? 'selected' : '' }}>Yes (e.g. Doctor note)</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label small fw-semibold">Description</label>
                                                            <textarea name="description" rows="2" class="form-control form-control-sm">{{ $lt->description }}</textarea>
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
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No leave types configured yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createTypeModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('leave-types.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Create Leave Type</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control form-control-sm" placeholder="AL" required>
                        </div>
                        <div class="col-8">
                            <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="Annual Leave" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Annual Days Allowed <span class="text-danger">*</span></label>
                            <input type="number" name="days_allowed" class="form-control form-control-sm" value="12" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Paid Status</label>
                            <select name="is_paid" class="form-select form-select-sm">
                                <option value="1">Paid</option>
                                <option value="0">Unpaid</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Require Supporting Document?</label>
                            <select name="requires_attachment" class="form-select form-select-sm">
                                <option value="0">No</option>
                                <option value="1">Yes (Mandatory document proof)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Policy terms and conditions"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Create Type</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
