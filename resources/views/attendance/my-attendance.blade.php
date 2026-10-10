@extends('layouts.app')

@section('title', 'My Attendance')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-clock-history text-primary me-2"></i>My Attendance Tracker</h4>
        <p class="text-muted small mb-0">Record daily check-in/out and review monthly timesheet.</p>
    </div>

    <!-- Month Filter Form -->
    <form method="GET" action="{{ route('attendance.my') }}" class="d-flex align-items-center gap-2 mt-2 mt-md-0">
        <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                </option>
            @endfor
        </select>
        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
            @for($y = 2024; $y <= 2030; $y++)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </form>
</div>

<!-- Clock In/Out Hero Card -->
<div class="card bg-primary text-white mb-4 border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="row align-items-center g-3">
            <div class="col-md-7">
                <div class="text-white-50 text-uppercase small fw-semibold">Today's Shift: {{ $employee->shift ? $employee->shift->name : 'Regular (09:00 - 18:00)' }}</div>
                <h1 class="display-4 fw-bold digital-clock mb-1" id="liveHeroClock">--:--:--</h1>
                <p class="text-white-50 mb-0"><i class="bi bi-calendar3 me-1"></i> {{ date('l, d F Y') }}</p>
            </div>

            <div class="col-md-5 text-md-end">
                @if(! $todayAttendance || ! $todayAttendance->clock_in)
                    <form action="{{ route('attendance.clock-in') }}" method="POST">
                        @csrf
                        <div class="input-group input-group-sm mb-2 justify-content-md-end">
                            <input type="text" name="notes" class="form-control form-control-sm w-auto" placeholder="Optional notes/location...">
                        </div>
                        <button type="submit" class="btn btn-light text-primary btn-lg px-4 fw-bold shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Clock In Now
                        </button>
                    </form>
                @elseif(! $todayAttendance->clock_out)
                    <div class="mb-2 text-white">
                        <i class="bi bi-check-circle-fill text-warning me-1"></i> Clocked In at <strong>{{ $todayAttendance->clock_in->format('H:i:s') }}</strong>
                        @if($todayAttendance->status === 'late')
                            <span class="badge bg-warning text-dark ms-1">Late Arrival</span>
                        @endif
                    </div>
                    <form action="{{ route('attendance.clock-out') }}" method="POST">
                        @csrf
                        <div class="input-group input-group-sm mb-2 justify-content-md-end">
                            <input type="text" name="notes" class="form-control form-control-sm w-auto" placeholder="Optional end-of-day notes...">
                        </div>
                        <button type="submit" class="btn btn-danger btn-lg px-4 fw-bold shadow-sm">
                            <i class="bi bi-box-arrow-left me-1"></i> Clock Out Now
                        </button>
                    </form>
                @else
                    <div class="bg-white bg-opacity-25 rounded-3 p-3 d-inline-block text-start">
                        <div class="text-white fw-bold"><i class="bi bi-check2-all me-1"></i> Shift Completed Today</div>
                        <div class="text-white-50 small">
                            In: {{ $todayAttendance->clock_in->format('H:i') }} &bull; Out: {{ $todayAttendance->clock_out->format('H:i') }}<br>
                            Work: <strong>{{ $todayAttendance->work_hours }} hrs</strong>
                            @if($todayAttendance->overtime_hours > 0)
                                &bull; OT: <strong>{{ $todayAttendance->overtime_hours }} hrs</strong>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Monthly Totals Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Days Present</div>
            <h3 class="fw-bold text-success mb-0">{{ $totalPresent }}</h3>
            <div class="text-muted fs-8">This month</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Late Arrivals</div>
            <h3 class="fw-bold text-warning mb-0">{{ $totalLate }}</h3>
            <div class="text-muted fs-8">Times grace period exceeded</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Total Work Hours</div>
            <h3 class="fw-bold text-primary mb-0">{{ $totalHours }}</h3>
            <div class="text-muted fs-8">Hours tracked</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Overtime Hours</div>
            <h3 class="fw-bold text-dark mb-0">{{ $totalOvertime }}</h3>
            <div class="text-muted fs-8">Beyond 8h standard</div>
        </div>
    </div>
</div>

<!-- Monthly Attendance Records Table -->
<div class="card">
    <div class="card-header bg-white fw-bold">
        <i class="bi bi-calendar3 me-1"></i> Timesheet for {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Date</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Status</th>
                        <th>Work Hours</th>
                        <th>Overtime</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $att)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $att->date->format('D, d M Y') }}</td>
                            <td class="font-monospace">{{ $att->clock_in ? $att->clock_in->format('H:i:s') : '—' }}</td>
                            <td class="font-monospace">{{ $att->clock_out ? $att->clock_out->format('H:i:s') : '—' }}</td>
                            <td><span class="badge {{ $att->status_badge }} text-capitalize">{{ str_replace('_', ' ', $att->status) }}</span></td>
                            <td>{{ $att->work_hours }} hrs</td>
                            <td>{{ $att->overtime_hours > 0 ? $att->overtime_hours . ' hrs' : '—' }}</td>
                            <td class="small text-muted">{{ $att->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No attendance records found for this month.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const heroClock = document.getElementById('liveHeroClock');
        if (heroClock) {
            function updateHeroClock() {
                const now = new Date();
                const h = String(now.getHours()).padStart(2, '0');
                const m = String(now.getMinutes()).padStart(2, '0');
                const s = String(now.getSeconds()).padStart(2, '0');
                heroClock.textContent = `${h}:${m}:${s}`;
            }
            updateHeroClock();
            setInterval(updateHeroClock, 1000);
        }
    });
</script>
@endpush
