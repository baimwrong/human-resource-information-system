@extends('layouts.app')

@section('title', 'Review Details - ' . $performanceReview->employee->full_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-star-fill text-warning me-2"></i>Appraisal Report: {{ $performanceReview->employee->full_name }}</h4>
        <p class="text-muted small mb-0">Period: <strong>{{ $performanceReview->review_period }}</strong> &bull; Reviewer: <strong>{{ $performanceReview->reviewer->name }}</strong></p>
    </div>
    <a href="{{ route('performance.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card text-center p-4">
            <img src="{{ $performanceReview->employee->avatar_url }}" class="rounded-circle mx-auto mb-3" style="width: 80px; height: 80px;" alt="">
            <h5 class="fw-bold mb-0">{{ $performanceReview->employee->full_name }}</h5>
            <div class="text-muted small mb-2">{{ $performanceReview->employee->designation?->title ?? 'N/A' }}</div>
            <span class="badge bg-light text-dark border align-self-center">{{ $performanceReview->employee->employee_code }}</span>

            <hr class="my-3">

            <div class="text-muted small text-uppercase fw-semibold">Overall Composite Rating</div>
            <h1 class="display-4 fw-bold text-primary my-2">{{ $performanceReview->overall_rating }}</h1>
            <div class="text-warning fs-5">
                @for($s = 1; $s <= 5; $s++)
                    <i class="bi bi-star{{ $s <= round($performanceReview->overall_rating) ? '-fill' : '' }}"></i>
                @endfor
            </div>
            <div class="mt-2">
                <span class="badge {{ $performanceReview->status_badge }} text-capitalize">{{ $performanceReview->status }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header bg-light fw-bold"><i class="bi bi-bar-chart me-1"></i> Competency Breakdown</div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                        <span>Technical Mastery</span>
                        <span>{{ $performanceReview->technical_rating }} / 5</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" style="width: {{ ($performanceReview->technical_rating / 5) * 100 }}%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                        <span>Communication &amp; Influence</span>
                        <span>{{ $performanceReview->communication_rating }} / 5</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: {{ ($performanceReview->communication_rating / 5) * 100 }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                        <span>Teamwork &amp; Organizational Culture</span>
                        <span>{{ $performanceReview->teamwork_rating }} / 5</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-info" style="width: {{ ($performanceReview->teamwork_rating / 5) * 100 }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light fw-bold text-success"><i class="bi bi-hand-thumbs-up me-1"></i> Key Strengths</div>
            <div class="card-body">
                <p class="mb-0 small text-secondary">{{ $performanceReview->strengths ?? 'No notes recorded.' }}</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-light fw-bold text-warning"><i class="bi bi-arrow-up-right-circle me-1"></i> Areas for Development</div>
            <div class="card-body">
                <p class="mb-0 small text-secondary">{{ $performanceReview->areas_for_improvement ?? 'No notes recorded.' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
