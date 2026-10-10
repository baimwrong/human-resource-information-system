@extends('layouts.app')

@section('title', 'System Audit Trail')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-2"></i>System Audit Trail</h4>
        <p class="text-muted small mb-0">Immutable compliance log recording user authentications, data updates, and administrative changes.</p>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="{{ route('audit-logs.index') }}" class="d-flex align-items-center gap-2 mt-2 mt-md-0">
        <select name="module" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Modules</option>
            @foreach($modules as $mod)
                <option value="{{ $mod }}" {{ request('module') == $mod ? 'selected' : '' }}>{{ ucfirst($mod) }}</option>
            @endforeach
        </select>
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search action..." value="{{ request('search') }}">
        <button type="submit" class="btn btn-sm btn-primary">Search</button>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>IP Address</th>
                        <th>Entity ID</th>
                        <th class="text-end pe-3">Changes (JSONB)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="ps-3 small font-monospace text-muted">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td>
                                @if($log->user)
                                    <div class="fw-semibold">{{ $log->user->name }}</div>
                                    <div class="text-muted fs-8">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-muted">System Automator</span>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark border font-monospace">{{ $log->action }}</span></td>
                            <td><span class="badge bg-primary bg-opacity-10 text-primary text-uppercase fs-8">{{ $log->module }}</span></td>
                            <td class="small font-monospace">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                            <td>{{ $log->model_id ? '#' . $log->model_id : '—' }}</td>
                            <td class="text-end pe-3">
                                @if($log->changes)
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#changeModal{{ $log->id }}">
                                        <i class="bi bi-code-slash me-1"></i> View Diff
                                    </button>

                                    <!-- JSON Viewer Modal -->
                                    <div class="modal fade text-start" id="changeModal{{ $log->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold">Audit Payload Diff - Log #{{ $log->id }}</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-0">
                                                    <pre class="bg-dark text-light p-3 mb-0 small rounded-0" style="max-height: 350px; overflow-y: auto;"><code>{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No audit trail events found.</td>
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
