@extends('layouts.app')

@section('title', 'Conduct Performance Review')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-star text-primary me-2"></i>Conduct Performance Appraisal</h4>
        <p class="text-muted small mb-0">Evaluate employee core competencies, strengths, and professional growth goals.</p>
    </div>
    <a href="{{ route('performance.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Reviews List
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('performance.store') }}" method="POST">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select form-select-sm" required>
                                <option value="">Select Employee</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->full_name }} ({{ $emp->employee_code }} &bull; {{ $emp->department?->name ?? 'No Dept' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Review Period <span class="text-danger">*</span></label>
                            <input type="text" name="review_period" class="form-control form-control-sm" value="{{ old('review_period', 'Q3 ' . date('Y')) }}" placeholder="e.g. Q3 2026 or Annual 2026" required>
                        </div>
                    </div>

                    <div class="border rounded p-3 mb-3 bg-light">
                        <h6 class="fw-bold mb-3"><i class="bi bi-sliders me-1"></i> Core Competency Ratings (1 to 5)</h6>
                        
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Technical / Job Mastery <span class="text-danger">*</span></label>
                                <select name="technical_rating" class="form-select form-select-sm" required>
                                    <option value="5">5 - Exceptional</option>
                                    <option value="4" selected>4 - Exceeds Expectations</option>
                                    <option value="3">3 - Meets Expectations</option>
                                    <option value="2">2 - Needs Improvement</option>
                                    <option value="1">1 - Unsatisfactory</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Communication &amp; Influence <span class="text-danger">*</span></label>
                                <select name="communication_rating" class="form-select form-select-sm" required>
                                    <option value="5">5 - Exceptional</option>
                                    <option value="4" selected>4 - Exceeds Expectations</option>
                                    <option value="3">3 - Meets Expectations</option>
                                    <option value="2">2 - Needs Improvement</option>
                                    <option value="1">1 - Unsatisfactory</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Teamwork &amp; Culture <span class="text-danger">*</span></label>
                                <select name="teamwork_rating" class="form-select form-select-sm" required>
                                    <option value="5">5 - Exceptional</option>
                                    <option value="4" selected>4 - Exceeds Expectations</option>
                                    <option value="3">3 - Meets Expectations</option>
                                    <option value="2">2 - Needs Improvement</option>
                                    <option value="1">1 - Unsatisfactory</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Demonstrated Key Strengths</label>
                        <textarea name="strengths" rows="3" class="form-control form-control-sm" placeholder="Notable achievements, standout qualities, project milestones...">{{ old('strengths') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Areas for Development &amp; Improvement</label>
                        <textarea name="areas_for_improvement" rows="3" class="form-control form-control-sm" placeholder="Skills or behaviors to strengthen in the next cycle...">{{ old('areas_for_improvement') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Review Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="completed" selected>Completed &amp; Published</option>
                            <option value="submitted">Submitted for HR Review</option>
                            <option value="draft">Save as Draft</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('performance.index') }}" class="btn btn-light border btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="bi bi-check2-circle me-1"></i> Save Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
