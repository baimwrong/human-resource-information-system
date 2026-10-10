@extends('layouts.app')

@section('title', 'Apply for Leave')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-calendar-plus text-primary me-2"></i>Submit Leave Application</h4>
        <p class="text-muted small mb-0">Select your leave type, dates, and provide justification.</p>
    </div>
    <a href="{{ route('leaves.my') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to My Leaves
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('leaves.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type_id" id="leaveTypeSelect" class="form-select form-select-sm" required>
                            <option value="">Select Leave Category</option>
                            @foreach($balances as $bal)
                                <option value="{{ $bal->leave_type_id }}" data-remaining="{{ $bal->remaining_days }}" {{ old('leave_type_id') == $bal->leave_type_id ? 'selected' : '' }}>
                                    {{ $bal->leaveType->name }} (Available: {{ $bal->remaining_days }} days left)
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text fs-8 text-primary" id="balanceHint"></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="startDate" class="form-control form-control-sm" value="{{ old('start_date', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="endDate" class="form-control form-control-sm" value="{{ old('end_date', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Reason for Leave <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="3" class="form-control form-control-sm" placeholder="Please provide specific reason or handover plan..." required>{{ old('reason') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Supporting Document / Medical Certificate (Optional)</label>
                        <input type="file" name="attachment" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                        <div class="form-text fs-8">Accepted formats: PDF, JPG, PNG (Max 5MB). Mandatory for medical sick leave.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('leaves.my') }}" class="btn btn-light border btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-send me-1"></i> Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
