@extends('layouts.app')

@section('title', 'Designations & Job Titles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-award text-primary me-2"></i>Job Designations &amp; Pay Grades</h4>
        <p class="text-muted small mb-0">Define organizational titles, seniority grades, and salary compensation bands.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createDesigModal">
        <i class="bi bi-plus-circle me-1"></i> Add Designation
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">Code</th>
                        <th>Job Title</th>
                        <th>Department</th>
                        <th>Pay Grade</th>
                        <th>Salary Range Band</th>
                        <th>Employees</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($designations as $des)
                        <tr>
                            <td class="ps-3"><span class="badge bg-light text-dark border">{{ $des->code }}</span></td>
                            <td class="fw-semibold">{{ $des->title }}</td>
                            <td>{{ $des->department ? $des->department->name : 'All / Cross-functional' }}</td>
                            <td><span class="badge bg-info-subtle text-info border border-info-subtle">{{ $des->pay_grade }}</span></td>
                            <td>IDR {{ number_format($des->min_salary, 0, ',', '.') }} &ndash; {{ number_format($des->max_salary, 0, ',', '.') }}</td>
                            <td>{{ $des->employees_count }} staff</td>
                            <td class="text-end pe-3">
                                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editDesModal{{ $des->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                @if($des->employees_count == 0)
                                    <form action="{{ route('designations.destroy', $des) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete designation {{ $des->title }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border text-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif

                                <!-- Edit Modal -->
                                <div class="modal fade text-start" id="editDesModal{{ $des->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('designations.update', $des) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold">Edit Designation - {{ $des->title }}</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-2">
                                                        <div class="col-4">
                                                            <label class="form-label small fw-semibold">Code</label>
                                                            <input type="text" name="code" class="form-control form-control-sm" value="{{ $des->code }}" required>
                                                        </div>
                                                        <div class="col-8">
                                                            <label class="form-label small fw-semibold">Job Title</label>
                                                            <input type="text" name="title" class="form-control form-control-sm" value="{{ $des->title }}" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Department</label>
                                                            <select name="department_id" class="form-select form-select-sm">
                                                                <option value="">Cross-Functional</option>
                                                                @foreach($departments as $dept)
                                                                    <option value="{{ $dept->id }}" {{ $des->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Pay Grade</label>
                                                            <input type="text" name="pay_grade" class="form-control form-control-sm" value="{{ $des->pay_grade }}" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Min Monthly Salary (IDR)</label>
                                                            <input type="number" name="min_salary" class="form-control form-control-sm" value="{{ $des->min_salary }}" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label small fw-semibold">Max Monthly Salary (IDR)</label>
                                                            <input type="number" name="max_salary" class="form-control form-control-sm" value="{{ $des->max_salary }}" required>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label small fw-semibold">Description</label>
                                                            <textarea name="description" rows="2" class="form-control form-control-sm">{{ $des->description }}</textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-award fs-2 d-block mb-1"></i>
                                No designations created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createDesigModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('designations.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Create Designation</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control form-control-sm" placeholder="SR-DEV" required>
                        </div>
                        <div class="col-8">
                            <label class="form-label small fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-sm" placeholder="Senior Software Engineer" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Department</label>
                            <select name="department_id" class="form-select form-select-sm">
                                <option value="">Cross-Functional</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Pay Grade <span class="text-danger">*</span></label>
                            <input type="text" name="pay_grade" class="form-control form-control-sm" placeholder="Grade A / Executive" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Min Salary (IDR) <span class="text-danger">*</span></label>
                            <input type="number" name="min_salary" class="form-control form-control-sm" value="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Max Salary (IDR) <span class="text-danger">*</span></label>
                            <input type="number" name="max_salary" class="form-control form-control-sm" value="0" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Job responsibilities overview"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Create Designation</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
