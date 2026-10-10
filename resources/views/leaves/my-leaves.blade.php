@extends('layouts.app')

@section('title', 'My Leaves & Balance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-calendar2-check text-primary me-2"></i>My Leave Balances &amp; Applications</h4>
        <p class="text-muted small mb-0">Check available time-off balances and submit vacation or sick leave requests.</p>
    </div>
    <a href="{{ route('leaves.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Apply for Leave
    </a>
</div>

<!-- Leave Balances Cards -->
<div class="row g-3 mb-4">
    @forelse($balances as $bal)
        <div class="col-sm-6 col-md-4 col-xl-2">
            <div class="card h-100 text-center p-3 mb-0">
                <span class="badge bg-light text-dark border align-self-center mb-2">{{ $bal->leaveType->code }}</span>
                <div class="text-muted small fw-semibold">{{ $bal->leaveType->name }}</div>
                <h2 class="fw-bold text-primary my-1">{{ $bal->remaining_days }}</h2>
                <div class="text-muted fs-8">
                    Used: <strong>{{ $bal->used_days }}</strong> &bull; Total: {{ $bal->allocated_days }}
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info">No leave balance quotas assigned for this year yet. Contact HR.</div>
        </div>
    @endforelse
</div>

<!-- Application History -->
<div class="card">
    <div class="card-header bg-white fw-bold">
        <i class="bi bi-clock-history me-1"></i> My Applications History
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Total Days</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>HR Response</th>
                        <th>Submitted</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $req->leaveType->name }}</td>
                            <td>{{ $req->start_date->format('d M Y') }}</td>
                            <td>{{ $req->end_date->format('d M Y') }}</td>
                            <td><strong>{{ $req->total_days }}</strong> days</td>
                            <td class="small">{{ $req->reason }}</td>
                            <td><span class="badge {{ $req->status_badge }} text-capitalize">{{ $req->status }}</span></td>
                            <td class="small text-muted">{{ $req->action_reason ?? '—' }}</td>
                            <td class="small text-muted">{{ $req->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                You have not submitted any leave requests yet.
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
