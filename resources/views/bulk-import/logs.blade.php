@extends('layouts.app')

@section('title', 'Bulk Import Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Bulk Import Execution History</h4>
        <p class="text-muted small mb-0">Audit history of batch ingestions, parsed row counts, and detailed error reports.</p>
    </div>
    <a href="{{ route('bulk-import.index') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-cloud-arrow-up me-1"></i> New Import Job
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Job ID</th>
                        <th>Imported At</th>
                        <th>User</th>
                        <th>Target Module</th>
                        <th>Source File</th>
                        <th>Total Rows</th>
                        <th>Success</th>
                        <th>Failed</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="ps-3 font-monospace">#{{ $log->id }}</td>
                            <td class="small">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td>{{ $log->user->name ?? 'System' }}</td>
                            <td><span class="badge bg-light text-dark border text-capitalize">{{ $log->import_type }}</span></td>
                            <td class="font-monospace small">{{ $log->file_name }}</td>
                            <td>{{ $log->total_rows }}</td>
                            <td class="text-success fw-bold">{{ $log->successful_rows }}</td>
                            <td class="{{ $log->failed_rows > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $log->failed_rows }}</td>
                            <td><span class="badge {{ $log->status_badge }} text-capitalize">{{ $log->status }}</span></td>
                            <td class="text-end pe-3">
                                @if($log->failed_rows > 0)
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#errorModal{{ $log->id }}">
                                            <i class="bi bi-eye me-1"></i> Inspect Failures
                                        </button>
                                        <a href="{{ route('bulk-import.errors', $log) }}" class="btn btn-danger" title="Download Error CSV">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>

                                    <!-- Error Details Inspection Modal -->
                                    <div class="modal fade text-start" id="errorModal{{ $log->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold text-danger">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Failed Rows Report (Job #{{ $log->id }})
                                                    </h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-0">
                                                    <div class="p-3 bg-light border-bottom small text-muted">
                                                        File: <strong>{{ $log->file_name }}</strong> &bull; Total Failed Rows: <strong>{{ $log->failed_rows }}</strong>
                                                    </div>
                                                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                        <table class="table table-sm table-striped mb-0">
                                                            <thead>
                                                                <tr class="table-light">
                                                                    <th class="ps-3" style="width: 80px;">CSV Row</th>
                                                                    <th style="width: 140px;">Identifier</th>
                                                                    <th>Error Cause / Validation Failure</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($log->error_details as $err)
                                                                    <tr>
                                                                        <td class="ps-3 font-monospace text-muted">#{{ $err['row'] ?? '?' }}</td>
                                                                        <td class="fw-semibold">{{ $err['identifier'] ?? '—' }}</td>
                                                                        <td class="text-danger small">{{ $err['error'] ?? 'Unknown' }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <a href="{{ route('bulk-import.errors', $log) }}" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-download me-1"></i> Download Errors CSV
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-success small fw-semibold"><i class="bi bi-check2-circle"></i> Clean Ingestion</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                No import history recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
