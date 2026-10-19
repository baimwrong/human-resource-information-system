@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Welcome back, {{ Auth::user()->name }}!</h4>
        <p class="text-muted small mb-0">Here is what is happening across the organization today.</p>
    </div>

    <!-- Quick Clock-In / Out Button Header Area -->
    @if(Auth::user()->employee)
        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
            @if(! $userAttendance || ! $userAttendance->clock_in)
                <form action="{{ route('attendance.clock-in') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success d-flex align-items-center gap-1 shadow-sm">
                        <i class="bi bi-box-arrow-in-right"></i> Clock In Now
                    </button>
                </form>
            @elseif(! $userAttendance->clock_out)
                <form action="{{ route('attendance.clock-out') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger d-flex align-items-center gap-1 shadow-sm">
                        <i class="bi bi-box-arrow-left"></i> Clock Out Now
                    </button>
                </form>
            @else
                <span class="badge bg-success py-2 px-3 fs-7">
                    <i class="bi bi-check-circle-fill me-1"></i> Shift Completed ({{ $userAttendance->work_hours }} hrs)
                </span>
            @endif
        </div>
    @endif
</div>

@if(Auth::user()->isHR() || Auth::user()->isManager())
    <!-- Executive / HR Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card mb-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Employees</div>
                        <h3 class="fw-bold mb-0">{{ $totalEmployees ?? 0 }}</h3>
                        <div class="text-success small"><i class="bi bi-check-circle"></i> {{ $activeEmployees ?? 0 }} active</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card mb-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Present Today</div>
                        <h3 class="fw-bold mb-0">{{ $todayPresent ?? 0 }}</h3>
                        <div class="text-muted small">{{ $totalEmployees > 0 ? round(($todayPresent / $totalEmployees) * 100) : 0 }}% attendance rate</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card mb-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                        <i class="bi bi-calendar2-week-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">On Leave Today</div>
                        <h3 class="fw-bold mb-0">{{ $todayOnLeave ?? 0 }}</h3>
                        <div class="text-warning small"><i class="bi bi-clock"></i> {{ $pendingLeaves ?? 0 }} pending approvals</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card stat-card mb-0 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                        <i class="bi bi-building-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Departments</div>
                        <h3 class="fw-bold mb-0">{{ $totalDepartments ?? 0 }}</h3>
                        <div class="text-muted small">Operational units</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card mb-0 h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-bar-chart-line me-1"></i> Attendance Trends (Past 7 Days)</span>
                    <span class="badge bg-light text-dark border">Daily Present</span>
                </div>
                <div class="card-body">
                    <canvas id="attendanceTrendChart" height="150"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card mb-0 h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-pie-chart me-1"></i> Department Headcounts</span>
                    <a href="{{ route('departments.index') }}" class="btn btn-sm btn-link text-decoration-none p-0">Manage</a>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="departmentChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Lower Split: Announcements & Quick Details -->
<div class="row g-3">
    <!-- Announcements Column -->
    <div class="col-lg-{{ Auth::user()->isEmployee() ? '8' : '7' }}">
        <div class="card mb-0 h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-megaphone-fill text-primary me-2"></i> Company Notices &amp; Announcements</span>
                @if(Auth::user()->isHR())
                    <a href="{{ route('announcements.index') }}" class="btn btn-sm btn-outline-primary">+ Post Notice</a>
                @endif
            </div>
            <div class="card-body p-0">
                @forelse($announcements as $notice)
                    <div class="p-3 border-bottom {{ $notice->is_pinned ? 'bg-primary bg-opacity-10' : '' }}">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="fw-bold mb-0">
                                @if($notice->is_pinned)
                                    <i class="bi bi-pin-angle-fill text-danger me-1"></i>
                                @endif
                                {{ $notice->title }}
                            </h6>
                            <span class="badge {{ $notice->priority_badge }} text-uppercase">{{ $notice->priority }}</span>
                        </div>
                        <p class="text-secondary small mb-2 text-pre-line">{{ Str::limit($notice->content, 180) }}</p>
                        <div class="text-muted fs-8 d-flex gap-3">
                            <span><i class="bi bi-person"></i> {{ $notice->author->name }}</span>
                            <span><i class="bi bi-clock"></i> {{ $notice->published_at ? $notice->published_at->diffForHumans() : $notice->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                        No announcements published yet.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Right Side Column -->
    <div class="col-lg-{{ Auth::user()->isEmployee() ? '4' : '5' }}">
        @if(Auth::user()->isEmployee() && isset($leaveBalances))
            <!-- Employee Self-Service Leave Balances -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calendar-check me-1"></i> Your Leave Balances ({{ date('Y') }})</span>
                    <a href="{{ route('leaves.create') }}" class="btn btn-sm btn-primary">Apply Leave</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($leaveBalances as $balance)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                <div>
                                    <span class="fw-semibold">{{ $balance->leaveType->name }}</span>
                                    <div class="text-muted fs-8">{{ $balance->used_days }} days used &bull; {{ $balance->allocated_days }} total</div>
                                </div>
                                <span class="badge bg-primary rounded-pill px-2 py-1 fs-7">
                                    {{ $balance->remaining_days }} left
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            @if(isset($latestPayslip) && $latestPayslip)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-cash-coin me-1"></i> Latest Payslip ({{ $latestPayslip->period_name }})</span>
                        <a href="{{ route('payroll.payslip', $latestPayslip) }}" class="btn btn-sm btn-outline-primary">View</a>
                    </div>
                    <div class="card-body">
                        <div class="text-muted small">Net Payout Amount</div>
                        <h4 class="fw-bold text-success mb-2">IDR {{ number_format($latestPayslip->net_salary, 0, ',', '.') }}</h4>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>Status: <strong class="text-dark text-capitalize">{{ $latestPayslip->payment_status }}</strong></span>
                            <span>Slip: {{ $latestPayslip->slip_number }}</span>
                        </div>
                    </div>
                </div>
            @endif

        @else
            <!-- Admin/HR Quick Actions & Recent Audit Trail -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-lightning-charge me-1"></i> Quick HR Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('employees.create') }}" class="btn btn-sm btn-outline-primary text-start">
                            <i class="bi bi-person-plus me-2"></i> Onboard New Employee
                        </a>
                        <a href="{{ route('bulk-import.index') }}" class="btn btn-sm btn-outline-success text-start">
                            <i class="bi bi-cloud-arrow-up me-2"></i> Bulk Import Workforce Data
                        </a>
                        <a href="{{ route('payroll.index') }}" class="btn btn-sm btn-outline-dark text-start">
                            <i class="bi bi-cash-stack me-2"></i> Generate Monthly Payroll Batch
                        </a>
                        <a href="{{ route('leaves.index') }}" class="btn btn-sm btn-outline-warning text-start">
                            <i class="bi bi-check2-circle me-2"></i> Review Pending Leaves ({{ $pendingLeaves ?? 0 }})
                        </a>
                    </div>
                </div>
            </div>

            @if(isset($recentAuditLogs) && $recentAuditLogs->count() > 0)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-shield-lock me-1"></i> Recent Security Audit</span>
                        <a href="{{ route('audit-logs.index') }}" class="btn btn-sm btn-link text-decoration-none p-0">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush fs-8">
                            @foreach($recentAuditLogs as $audit)
                                <li class="list-group-item py-2 px-3">
                                    <div class="d-flex justify-content-between">
                                        <strong class="text-dark">{{ str_replace('_', ' ', $audit->action) }}</strong>
                                        <span class="text-muted">{{ $audit->created_at ? $audit->created_at->diffForHumans() : '' }}</span>
                                    </div>
                                    <div class="text-muted">by {{ $audit->user?->name ?? 'System' }} &bull; {{ $audit->module }}</div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

@endsection

@push('scripts')
@if(Auth::user()->isHR() || Auth::user()->isManager())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Attendance Trend Chart (Offline Chart.js)
        const trendCtx = document.getElementById('attendanceTrendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: @json($trendDates ?? []),
                    datasets: [{
                        label: 'Present Employees',
                        data: @json($trendPresent ?? []),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: '#1d4ed8'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }

        // Department Headcount Chart (Offline Chart.js)
        const deptCtx = document.getElementById('departmentChart');
        if (deptCtx) {
            new Chart(deptCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($deptLabels ?? []),
                    datasets: [{
                        data: @json($deptCounts ?? []),
                        backgroundColor: [
                            '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                    }
                }
            });
        }
    });
</script>
@endif
@endpush
