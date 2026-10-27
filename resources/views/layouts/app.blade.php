<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'HRIS Enterprise') }}</title>
    
    <!-- 100% Offline Local CSS Assets (No Internet Required) -->
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
    @stack('styles')
</head>
<body>

    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="brand">
            <i class="bi bi-diagram-3-fill text-primary"></i>
            <span>HRIS Enterprise</span>
        </div>

        <nav class="nav flex-column py-2">
            <div class="sidebar-heading">Main</div>
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>

            <div class="sidebar-heading">Self-Service</div>
            @if(Auth::user()->employee)
                <a class="nav-link {{ request()->routeIs('employees.show') && request()->route('employee') && (is_object(request()->route('employee')) ? request()->route('employee')->id : request()->route('employee')) == Auth::user()->employee->id ? 'active' : '' }}" href="{{ route('employees.show', Auth::user()->employee) }}">
                    <i class="bi bi-person-badge"></i> My 360° Profile
                </a>
            @endif
            <a class="nav-link {{ request()->routeIs('attendance.my') ? 'active' : '' }}" href="{{ route('attendance.my') }}">
                <i class="bi bi-clock-history"></i> My Attendance
            </a>
            <a class="nav-link {{ request()->routeIs('leaves.my') || request()->routeIs('leaves.create') ? 'active' : '' }}" href="{{ route('leaves.my') }}">
                <i class="bi bi-calendar2-check"></i> My Leaves
            </a>

            @if(Auth::user()->isManager() || Auth::user()->isHR())
                <div class="sidebar-heading">Workforce</div>
                <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}" href="{{ route('employees.index') }}">
                    <i class="bi bi-people"></i> Employees
                </a>
                <a class="nav-link {{ request()->routeIs('attendance.index') ? 'active' : '' }}" href="{{ route('attendance.index') }}">
                    <i class="bi bi-calendar-week"></i> Daily Attendance
                </a>
                <a class="nav-link {{ request()->routeIs('leaves.index') ? 'active' : '' }}" href="{{ route('leaves.index') }}">
                    <i class="bi bi-envelope-paper"></i> Leave Requests
                </a>
                <a class="nav-link {{ request()->routeIs('performance.*') ? 'active' : '' }}" href="{{ route('performance.index') }}">
                    <i class="bi bi-graph-up-arrow"></i> Performance
                </a>
                <a class="nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}" href="{{ route('announcements.index') }}">
                    <i class="bi bi-megaphone"></i> Announcements
                </a>
            @endif

            @if(Auth::user()->isHR())
                <div class="sidebar-heading">Organization &amp; Payroll</div>
                <a class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}" href="{{ route('departments.index') }}">
                    <i class="bi bi-building"></i> Departments
                </a>
                <a class="nav-link {{ request()->routeIs('designations.*') ? 'active' : '' }}" href="{{ route('designations.index') }}">
                    <i class="bi bi-award"></i> Designations
                </a>
                <a class="nav-link {{ request()->routeIs('leave-types.*') ? 'active' : '' }}" href="{{ route('leave-types.index') }}">
                    <i class="bi bi-card-checklist"></i> Leave Types
                </a>
                <a class="nav-link {{ request()->routeIs('payroll.index') || request()->routeIs('payroll.batch') ? 'active' : '' }}" href="{{ route('payroll.index') }}">
                    <i class="bi bi-cash-stack"></i> Payroll Batches
                </a>
                <a class="nav-link {{ request()->routeIs('payroll.structures') ? 'active' : '' }}" href="{{ route('payroll.structures') }}">
                    <i class="bi bi-wallet2"></i> Salary Structures
                </a>
            @endif

            @if(Auth::user()->isAdmin())
                <div class="sidebar-heading">Administration</div>
                <a class="nav-link {{ request()->routeIs('bulk-import.*') ? 'active' : '' }}" href="{{ route('bulk-import.index') }}">
                    <i class="bi bi-cloud-arrow-up"></i> Bulk Data Import
                </a>
                <a class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}" href="{{ route('audit-logs.index') }}">
                    <i class="bi bi-shield-check"></i> System Audit Logs
                </a>
                <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                    <i class="bi bi-gear"></i> System Settings
                </a>
            @endif
        </nav>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <!-- Top Header Bar -->
        <header class="top-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-light border d-lg-none" id="sidebarToggle" type="button">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-md-flex align-items-center gap-2 text-secondary small">
                    <i class="bi bi-clock"></i>
                    <span id="liveClock" class="digital-clock fw-bold text-dark">--:--:--</span>
                    <span class="text-muted ms-1">&bull; {{ date('l, d M Y') }}</span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary text-uppercase">{{ str_replace('_', ' ', Auth::user()->role) }}</span>

                <div class="dropdown">
                    <button class="btn btn-sm btn-link text-decoration-none dropdown-toggle d-flex align-items-center gap-2 p-0 text-dark" type="button" data-bs-dismiss="dropdown" data-bs-toggle="dropdown">
                        <img src="{{ Auth::user()->employee ? Auth::user()->employee->avatar_url : 'data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'40\' height=\'40\'><rect width=\'40\' height=\'40\' fill=\'%230d6efd\'/><text x=\'50%\' y=\'55%\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%23fff\' font-weight=\'bold\'>' . substr(Auth::user()->name, 0, 1) . '</text></svg>' }}" class="avatar-circle" alt="{{ Auth::user()->name }}">
                        <span class="d-none d-md-inline fw-semibold small">{{ Auth::user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><h6 class="dropdown-header">{{ Auth::user()->email }}</h6></li>
                        @if(Auth::user()->employee)
                            <li><a class="dropdown-item small" href="{{ route('employees.show', Auth::user()->employee) }}"><i class="bi bi-person-badge me-2"></i>My 360° Profile</a></li>
                        @endif
                        <li><a class="dropdown-item small" href="{{ route('profile') }}"><i class="bi bi-gear me-2"></i>Account Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger small"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Main Content -->
        <main class="page-content">
            <!-- Flash Notification Alerts -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                        <div>{{ session('warning') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-x-circle-fill fs-5 me-2"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <div class="d-flex align-items-center mb-1">
                        <i class="bi bi-exclamation-octagon-fill fs-5 me-2"></i>
                        <span class="fw-semibold">Please check the form errors below:</span>
                    </div>
                    <ul class="mb-0 ps-4 small">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="text-center py-3 text-muted small bg-white border-top mt-auto no-print">
            HRIS Enterprise &bull; Running on Laravel 13 &amp; PostgreSQL 18 &bull; Offline Network Ready
        </footer>
    </div>

    <!-- 100% Offline Local JS Assets (No Internet Required) -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/chartjs/chart.umd.min.js') }}"></script>
    <script src="{{ asset('assets/js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
