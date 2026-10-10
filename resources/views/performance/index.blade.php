@extends('layouts.app')

@section('title', 'Performance Reviews')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Performance Appraisals</h4>
        <p class="text-muted small mb-0">Track talent performance evaluations, core competencies, and feedback.</p>
    </div>
    <a href="{{ route('performance.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> New Review
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Period</th>
                        <th>Technical</th>
                        <th>Communication</th>
                        <th>Teamwork</th>
                        <th>Overall Score</th>
                        <th>Reviewer</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $rev)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $rev->employee->avatar_url }}" class="avatar-circle me-2" alt="">
                                    <div>
                                        <div class="fw-semibold">{{ $rev->employee->full_name }}</div>
                                        <div class="text-muted fs-8">{{ $rev->employee->department?->name ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $rev->review_period }}</span></td>
                            <td>{{ $rev->technical_rating }} / 5</td>
                            <td>{{ $rev->communication_rating }} / 5</td>
                            <td>{{ $rev->teamwork_rating }} / 5</td>
                            <td>
                                <span class="badge {{ $rev->overall_rating >= 4 ? 'bg-success' : ($rev->overall_rating >= 3 ? 'bg-primary' : 'bg-warning text-dark') }} fs-7">
                                    ★ {{ $rev->overall_rating }} / 5.0
                                </span>
                            </td>
                            <td>{{ $rev->reviewer->name }}</td>
                            <td><span class="badge {{ $rev->status_badge }} text-capitalize">{{ $rev->status }}</span></td>
                            <td class="text-end pe-3">
                                <a href="{{ route('performance.show', $rev) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                No performance appraisals conducted yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($reviews->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $reviews->links() }}
        </div>
    @endif
</div>
@endsection
