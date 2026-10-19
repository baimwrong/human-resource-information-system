<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PerformanceReview;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PerformanceReviewController extends Controller
{
    public function index()
    {
        $reviews = PerformanceReview::with(['employee.department', 'employee.designation', 'reviewer'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('performance.index', compact('reviews'));
    }

    public function create()
    {
        $employees = Employee::where('employment_status', 'active')->orderBy('first_name')->get();
        return view('performance.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'review_period' => ['required', 'string', 'max:50'],
            'technical_rating' => ['required', 'integer', 'between:1,5'],
            'communication_rating' => ['required', 'integer', 'between:1,5'],
            'teamwork_rating' => ['required', 'integer', 'between:1,5'],
            'strengths' => ['nullable', 'string'],
            'areas_for_improvement' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,submitted,completed'],
        ]);

        $overall = round(($validated['technical_rating'] + $validated['communication_rating'] + $validated['teamwork_rating']) / 3, 2);

        $review = PerformanceReview::create([
            'employee_id' => $validated['employee_id'],
            'reviewer_id' => Auth::id(),
            'review_period' => $validated['review_period'],
            'technical_rating' => $validated['technical_rating'],
            'communication_rating' => $validated['communication_rating'],
            'teamwork_rating' => $validated['teamwork_rating'],
            'overall_rating' => $overall,
            'strengths' => $validated['strengths'],
            'areas_for_improvement' => $validated['areas_for_improvement'],
            'status' => $validated['status'],
        ]);

        AuditLogger::log(
            action: 'created_performance_review',
            module: 'performance',
            modelType: PerformanceReview::class,
            modelId: $review->id
        );

        return redirect()->route('performance.index')->with('success', 'Performance review saved successfully.');
    }

    public function show(PerformanceReview $performanceReview)
    {
        $performanceReview->load(['employee.department', 'employee.designation', 'reviewer']);
        return view('performance.show', compact('performanceReview'));
    }

    public function destroy(PerformanceReview $performanceReview)
    {
        $id = $performanceReview->id;
        $performanceReview->delete();

        AuditLogger::log(
            action: 'deleted_performance_review',
            module: 'performance',
            modelType: PerformanceReview::class,
            modelId: $id
        );

        return redirect()->route('performance.index')->with('success', 'Performance review deleted.');
    }
}
