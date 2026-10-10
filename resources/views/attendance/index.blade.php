@extends('layouts.app')

@section('title', 'Daily Attendance Sheet')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-calendar-week text-primary me-2"></i>Daily Attendance Sheet</h4>
        <p class="text-muted small mb-0">Track daily employee check-ins, lateness, overtime, and work hours.</p>
    </div>

    <!-- Filter by Date Form -->
    <form method="GET" action="{{ route('attendance.index') }}" class="d-flex align-items-center gap-2 mt-2 mt-md-0">
        <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
        <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
            @endforeach
        </select>
    </form>
</div>

<!-- Daily Summary Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Total Scheduled</div>
            <h3 class="fw-bold text-dark mb-0">{{ $totalEmployees }}</h3>
            <div class="text-muted fs-8">Active employees</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Present Today</div>
            <h3 class="fw-bold text-success mb-0">{{ $totalPresent }}</h3>
            <div class="text-success fs-8">{{ $totalEmployees > 0 ? round(($totalPresent / $totalEmployees) * 100) : 0 }}% of workforce</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Late Arrivals</div>
            <h3 class="fw-bold text-warning mb-0">{{ $totalLate }}</h3>
            <div class="text-muted fs-8">Past shift grace period</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Half Day / Left Early</div>
            <h3 class="fw-bold text-info mb-0">{{ $totalHalfDay }}</h3>
            <div class="text-muted fs-8">Under standard hours</div>
        </div>
    </div>
</div>

<!-- Attendance Sheet Table -->
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-clock me-1"></i> Records for {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</span>
        <span class="badge bg-light text-dark border">{{ $attendances->total() }} recorded</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Employee</th>
                        <th>Department</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Status</th>
                        <th>Work Hours</th>
                        <th>Overtime</th>
                        <th>IP / Source</th>
                        <th>Notes</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $att)
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <img src="{{ $att->employee->avatar_url }}" class="avatar-circle me-2" alt="">
                                    <div>
                                        <a href="{{ route('employees.show', $att->employee) }}" class="fw-semibold text-dark text-decoration-none">
                                            {{ $att->employee->full_name }}
                                        </a>
                                        <div class="text-muted fs-8">{{ $att->employee->employee_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $att->employee->department?->name ?? '—' }}</td>
                            <td class="font-monospace fw-semibold">{{ $att->clock_in ? $att->clock_in->format('H:i:s') : '—' }}</td>
                            <td class="font-monospace fw-semibold">{{ $att->clock_out ? $att->clock_out->format('H:i:s') : '—' }}</td>
                            <td>
                                <span class="badge {{ $att->status_badge }} text-capitalize">{{ str_replace('_', ' ', $att->status) }}</span>
                                @if($att->is_adjusted)
                                    <i class="bi bi-patch-check text-primary ms-1" title="Adjusted by HR"></i>
                                @endif
                            </td>
                            <td>{{ $att->work_hours }} hrs</td>
                            <td>{{ $att->overtime_hours > 0 ? $att->overtime_hours . ' hrs' : '—' }}</td>
                            <td class="small text-muted font-monospace">{{ $att->ip_address ?? 'Local' }}</td>
                            <td class="small text-muted">{{ Str::limit($att->notes ?? '—', 30) }}</td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $att->id }}" title="Adjust Record">
                                    <i class="bi bi-sliders"></i> Adjust
                                </button>

                                <!-- HR Adjustment Modal -->
                                <div class="modal fade text-start" id="adjustModal{{ $att->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('attendance.adjust', $att) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold">Adjust Attendance - {{ $att->employee->full_name }}</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Clock In Time</label>
                                                            <input type="time" name="clock_in" class="form-control form-control-sm" value="{{ $att->clock_in ? $att->clock_in->format('H:i') : '' }}">
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Clock Out Time</label>
                                                            <input type="time" name="clock_out" class="form-control form-control-sm" value="{{ $att->clock_out ? $att->clock_out->format('H:i') : '' }}">
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label small fw-semibold">Attendance Status</label>
                                                            <select name="status" class="form-select form-select-sm">
                                                                <option value="present" {{ $att->status === 'present' ? 'selected' : '' }}>Present</option>
                                                                <option value="late" {{ $att->status === 'late' ? 'selected' : '' }}>Late Arrival</option>
                                                                <option value="half_day" {{ $att->status === 'half_day' ? 'selected' : '' }}>Half Day</option>
                                                                <option value="absent" {{ $att->status === 'absent' ? 'selected' : '' }}>Absent</option>
                                                                <option value="on_leave" {{ $att->status === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                                                                <option value="holiday" {{ $att->status === 'holiday' ? 'selected' : '' }}>Holiday</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label small fw-semibold">Adjustment Reason / Notes</label>
                                                            <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="Reason for manual adjustment...">{{ $att->notes }}</textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-sm btn-primary">Save Adjustment</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x fs-2 d-block mb-1"></i>
                                No attendance entries recorded for this date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($attendances->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $attendances->links() }}
        </div>
    @endif
</div>
@endsection
