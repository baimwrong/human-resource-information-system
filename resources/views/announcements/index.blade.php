@extends('layouts.app')

@section('title', 'Company Announcements')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-megaphone text-primary me-2"></i>Company Announcements</h4>
        <p class="text-muted small mb-0">Publish internal company-wide or department-specific communications.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createNoticeModal">
        <i class="bi bi-plus-circle me-1"></i> Post Announcement
    </button>
</div>

<div class="row g-4">
    <!-- Announcements List -->
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-bell me-1"></i> All Published Notices ({{ $announcements->total() }})
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-3">Notice Title</th>
                                <th>Target Audience</th>
                                <th>Priority</th>
                                <th>Author</th>
                                <th>Published At</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($announcements as $notice)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold">
                                            @if($notice->is_pinned)
                                                <i class="bi bi-pin-angle-fill text-danger me-1" title="Pinned Announcement"></i>
                                            @endif
                                            {{ $notice->title }}
                                        </div>
                                        <div class="text-muted small">{{ Str::limit($notice->content, 90) }}</div>
                                    </td>
                                    <td>
                                        @if($notice->targetDepartment)
                                            <span class="badge bg-light text-dark border">{{ $notice->targetDepartment->name }}</span>
                                        @else
                                            <span class="badge bg-primary bg-opacity-10 text-primary">All Company</span>
                                        @endif
                                    </td>
                                    <td><span class="badge {{ $notice->priority_badge }} text-uppercase">{{ $notice->priority }}</span></td>
                                    <td>{{ $notice->author->name }}</td>
                                    <td class="small text-muted">{{ $notice->published_at ? $notice->published_at->format('d M Y H:i') : $notice->created_at->format('d M Y') }}</td>
                                    <td class="text-end pe-3">
                                        <form action="{{ route('announcements.destroy', $notice) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this announcement?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No announcements posted yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($announcements->hasPages())
                <div class="card-footer bg-white border-0 py-2">
                    {{ $announcements->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Create Notice Modal -->
<div class="modal fade" id="createNoticeModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('announcements.store') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title fw-bold">Publish New Announcement</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Office Holiday Notice" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Priority Level <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select form-select-sm" required>
                                <option value="normal" selected>Normal</option>
                                <option value="urgent">Urgent</option>
                                <option value="info">Information</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Target Audience</label>
                            <select name="target_department_id" class="form-select form-select-sm">
                                <option value="">All Company (Everyone)</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Announcement Content <span class="text-danger">*</span></label>
                        <textarea name="content" rows="4" class="form-control form-control-sm" placeholder="Write announcement details..." required></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_pinned" id="isPinned" value="1">
                        <label class="form-check-label small" for="isPinned">
                            Pin this announcement to top of dashboard
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Publish Announcement</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
