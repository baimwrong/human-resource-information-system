@extends('layouts.app')

@section('title', 'Admin Bulk Data Import')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-cloud-arrow-up text-primary me-2"></i>Admin Bulk Data Import Center</h4>
        <p class="text-muted small mb-0">High-volume transactional CSV ingestion for employees, organizational units, attendance logs, and compensation structures.</p>
    </div>

    <div class="d-flex gap-2 mt-2 mt-md-0">
        <a href="{{ route('bulk-import.logs') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clock-history me-1"></i> View Import History
        </a>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Total Import Jobs</div>
            <h3 class="fw-bold text-dark mb-0">{{ $stats['total_imports'] }}</h3>
            <div class="text-muted fs-8">Execution history</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Completed Perfectly</div>
            <h3 class="fw-bold text-success mb-0">{{ $stats['successful_imports'] }}</h3>
            <div class="text-success fs-8">100% rows ingested</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Partial Imports</div>
            <h3 class="fw-bold text-warning mb-0">{{ $stats['partial_imports'] }}</h3>
            <div class="text-muted fs-8">Some invalid rows skipped</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card text-center p-3 mb-0">
            <div class="text-muted small text-uppercase fw-semibold">Failed Imports</div>
            <h3 class="fw-bold text-danger mb-0">{{ $stats['failed_imports'] }}</h3>
            <div class="text-muted fs-8">Rolled back or fatal syntax</div>
        </div>
    </div>
</div>

<!-- Step 1: Download Templates Cards -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white fw-bold d-flex align-items-center">
        <span class="badge bg-primary rounded-circle me-2 p-2">1</span>
        <span>Download Official CSV Format Templates</span>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">Download pre-formatted CSV templates containing the exact required headers and realistic dummy data rows:</p>
        
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="border rounded p-3 text-center h-100 bg-light">
                    <i class="bi bi-person-badge fs-2 text-primary d-block mb-2"></i>
                    <h6 class="fw-bold mb-1">Employees Template</h6>
                    <p class="text-muted fs-8 mb-3">Code, name, email, department, job role, basic salary.</p>
                    <a href="{{ route('bulk-import.template', 'employees') }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-download me-1"></i> template_employees.csv
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="border rounded p-3 text-center h-100 bg-light">
                    <i class="bi bi-building fs-2 text-info d-block mb-2"></i>
                    <h6 class="fw-bold mb-1">Departments Template</h6>
                    <p class="text-muted fs-8 mb-3">Code, department name, budget, status.</p>
                    <a href="{{ route('bulk-import.template', 'departments') }}" class="btn btn-sm btn-outline-info w-100">
                        <i class="bi bi-download me-1"></i> template_departments.csv
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="border rounded p-3 text-center h-100 bg-light">
                    <i class="bi bi-clock-history fs-2 text-warning d-block mb-2"></i>
                    <h6 class="fw-bold mb-1">Attendance Template</h6>
                    <p class="text-muted fs-8 mb-3">Employee code, date, clock-in, clock-out, status.</p>
                    <a href="{{ route('bulk-import.template', 'attendance') }}" class="btn btn-sm btn-outline-warning text-dark w-100">
                        <i class="bi bi-download me-1"></i> template_attendance.csv
                    </a>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="border rounded p-3 text-center h-100 bg-light">
                    <i class="bi bi-wallet2 fs-2 text-success d-block mb-2"></i>
                    <h6 class="fw-bold mb-1">Salaries Template</h6>
                    <p class="text-muted fs-8 mb-3">Employee code, basic salary, allowances, deductions.</p>
                    <a href="{{ route('bulk-import.template', 'salaries') }}" class="btn btn-sm btn-outline-success w-100">
                        <i class="bi bi-download me-1"></i> template_salaries.csv
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Step 2: Upload CSV Form -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white fw-bold d-flex align-items-center">
        <span class="badge bg-primary rounded-circle me-2 p-2">2</span>
        <span>Upload &amp; Execute Ingestion Pipeline</span>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('bulk-import.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Target Data Module <span class="text-danger">*</span></label>
                    <select name="import_type" class="form-select form-select-sm" required>
                        <option value="employees" selected>Employees Directory (Auto-creates users &amp; leave balance)</option>
                        <option value="departments">Departments &amp; Organizational Units</option>
                        <option value="attendance">Daily Attendance Check-Ins</option>
                        <option value="salaries">Salary Structures &amp; Allowances</option>
                    </select>
                    <div class="form-text fs-8 mt-2">
                        For employee import, system accounts are generated with default initial password <code>Password123!</code>.
                    </div>

                    <div class="mt-4 pt-2 border-top">
                        <label class="form-label small fw-semibold">Transaction Safety Mode</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="rollback_on_error" id="rollbackOnError" value="1">
                            <label class="form-check-label small" for="rollbackOnError">
                                <strong>Strict Rollback:</strong> Fail &amp; rollback entire file if any row has an error.
                            </label>
                        </div>
                        <div class="form-text fs-8 text-muted">
                            If unchecked, valid rows will be committed and invalid rows will be reported in the error log.
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <label class="form-label small fw-semibold">Select CSV File <span class="text-danger">*</span></label>
                    <div class="file-drop-area position-relative" onclick="document.getElementById('csvFileInput').click()">
                        <i class="bi bi-filetype-csv fs-1 text-primary d-block mb-2"></i>
                        <h6 class="fw-bold mb-1">Click to browse or drag &amp; drop CSV file here</h6>
                        <p class="text-muted small mb-2">Supports .csv or .txt (Comma-separated, UTF-8, max 10MB)</p>
                        <span id="selectedFileName" class="badge bg-primary px-3 py-2 fs-7 font-monospace">No file selected</span>
                        <input type="file" name="csv_file" id="csvFileInput" class="d-none" accept=".csv,.txt" required>
                    </div>

                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary px-4 py-2">
                            <i class="bi bi-play-circle-fill me-1"></i> Ingest &amp; Process CSV
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Step 3: Recent Execution Logs -->
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-bold"><i class="bi bi-clock-history me-1"></i> Recent Bulk Import Executions</span>
        <a href="{{ route('bulk-import.logs') }}" class="btn btn-sm btn-link text-decoration-none">View All Logs</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Execution Time</th>
                        <th>Type</th>
                        <th>File Name</th>
                        <th>Total Rows</th>
                        <th>Success</th>
                        <th>Failed</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLogs as $log)
                        <tr>
                            <td class="ps-3 small text-muted">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td><span class="badge bg-light text-dark border text-capitalize">{{ $log->import_type }}</span></td>
                            <td class="font-monospace small">{{ $log->file_name }}</td>
                            <td>{{ $log->total_rows }}</td>
                            <td class="text-success fw-bold">{{ $log->successful_rows }}</td>
                            <td class="{{ $log->failed_rows > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ $log->failed_rows }}</td>
                            <td><span class="badge {{ $log->status_badge }} text-capitalize">{{ $log->status }}</span></td>
                            <td class="text-end pe-3">
                                @if($log->failed_rows > 0)
                                    <a href="{{ route('bulk-import.errors', $log) }}" class="btn btn-sm btn-outline-danger" title="Download Error CSV">
                                        <i class="bi bi-download me-1"></i> Error Report
                                    </a>
                                @else
                                    <span class="text-muted small"><i class="bi bi-check-circle text-success"></i> Clean</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No previous import history recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
