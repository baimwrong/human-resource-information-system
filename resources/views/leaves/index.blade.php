@extends('layouts.app')

@section('title', 'Leave Requests Management')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-envelope-paper text-primary me-2"></i>Leave Applications</h4>
        <p class="text-muted small mb-0">Review employee leave requests, approve time-off, or record rejection rationale.</p>
    </div>

    <!-- Status filter pills -->
    <div class="d-flex gap-1 mt-2 mt-md-0">
        <a href="{{ route('leaves.index') }}" class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-light border' }}">
            All
        </a>
        <a href="{{ route('leaves.index', ['status' => 'pending']) }}" class="btn btn-sm {{ request('status') === 'pending' ? 'btn-warning text-dark' : 'btn-light border' }}">
            Pending ({{ $pendingCount }})
        </a>
        <a href="{{ route('leaves.index', ['status' => 'approved']) }}" class="btn btn-sm {{ request('status') === 'approved' ? 'btn-success' : 'btn-light border' }}">
            Approved
        </a>
        <a href="{{ route('leaves.index', ['status' => 'rejected']) }}" class="btn btn-sm {{ request('status') === 'rejected' ? 'btn-danger' : 'btn-light border' }}">
            Rejected
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Leave Type</th>
                        <th>Dates</th>
                        <th>Duration</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Approver Note</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $req->employee->avatar_url }}" class="avatar-circle me-2" alt="">
                                    <div>
                                        <div class="fw-semibold">{{ $req->employee->full_name }}</div>
                                        <div class="text-muted fs-8">{{ $req->employee->department?->name ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $req->leaveType->code }}</span>
                                <span class="small fw-semibold ms-1">{{ $req->leaveType->name }}</span>
                            </td>
                            <td>
                                <span class="fw-semibold">{{ $req->start_date->format('d M') }} &ndash; {{ $req->end_date->format('d M Y') }}</span>
                            </td>
                            <td><strong>{{ $req->total_days }}</strong> days</td>
                            <td class="small text-secondary" style="max-width: 200px;">
                                {{ $req->reason }}
                                @if($req->attachment_path)
                                    <a href="{{ asset('storage/' . $req->attachment_path) }}" target="_blank" class="d-block text-primary small">
                                        <i class="bi bi-paperclip"></i> Attachment
                                    </a>
                                @endif
                            </td>
                            <td><span class="badge {{ $req->status_badge }} text-capitalize">{{ $req->status }}</span></td>
                            <td class="small text-muted">
                                @if($req->approver)
                                    <div><i class="bi bi-person-check"></i> {{ $req->approver->name }}</div>
                                    <div>{{ $req->action_reason }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @if($req->status === 'pending')
                                    <div class="btn-group btn-group-sm">
                                        <!-- Quick Approve Button / Modal trigger -->
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $req->id }}">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                        <!-- Quick Reject Button / Modal trigger -->
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $req->id }}">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </div>

                                    <!-- Approve Modal -->
                                    <div class="modal fade text-start" id="approveModal{{ $req->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form action="{{ route('leaves.approve', $req) }}" method="POST">
                                                @csrf
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold">Approve Leave Application</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body small">
                                                        <p>Approve leave request for <strong>{{ $req->employee->full_name }}</strong> from <strong>{{ $req->start_date->format('d M Y') }}</strong> to <strong>{{ $req->end_date->format('d M Y') }}</strong> ({{ $req->total_days }} days)?</p>
                                                        <label class="form-label fw-semibold">Approval Remarks (Optional)</label>
                                                        <input type="text" name="action_reason" class="form-control form-control-sm" value="Approved by HR / Manager">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-success">Confirm Approval</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Reject Modal -->
                                    <div class="modal fade text-start" id="rejectModal{{ $req->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <form action="{{ route('leaves.reject', $req) }}" method="POST">
                                                @csrf
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h6 class="modal-title fw-bold">Reject Leave Application</h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body small">
                                                        <p>Reject leave request for <strong>{{ $req->employee->full_name }}</strong>? Days will be refunded to employee's remaining balance.</p>
                                                        <label class="form-label fw-semibold">Rejection Reason <span class="text-danger">*</span></label>
                                                        <textarea name="action_reason" rows="2" class="form-control form-control-sm" required placeholder="Explain why the leave is rejected (e.g. project deadline, understaffed)..."></textarea>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-sm btn-danger">Confirm Rejection</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-envelope-paper fs-2 d-block mb-1"></i>
                                No leave requests found in this category.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($requests->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $requests->links() }}
        </div>
    @endif
</div>
@endsection
