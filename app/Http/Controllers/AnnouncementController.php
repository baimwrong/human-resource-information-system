<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Department;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with(['author', 'targetDepartment'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate(15);

        $departments = Department::where('status', 'active')->orderBy('name')->get();

        return view('announcements.index', compact('announcements', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'priority' => ['required', 'in:normal,urgent,info'],
            'target_department_id' => ['nullable', 'exists:departments,id'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'priority' => $validated['priority'],
            'target_department_id' => $validated['target_department_id'] ?? null,
            'is_pinned' => $request->boolean('is_pinned'),
            'published_at' => now(),
            'created_by' => Auth::id(),
        ]);

        AuditLogger::log(
            action: 'published_announcement',
            module: 'announcements',
            modelType: Announcement::class,
            modelId: $announcement->id
        );

        return back()->with('success', 'Announcement published successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        if (! Auth::user()->isHR() && $announcement->created_by !== Auth::id()) {
            abort(403, 'Unauthorized. You cannot delete announcements created by another user.');
        }

        $id = $announcement->id;
        $announcement->delete();

        AuditLogger::log(
            action: 'deleted_announcement',
            module: 'announcements',
            modelType: Announcement::class,
            modelId: $id
        );

        return back()->with('success', 'Announcement deleted.');
    }
}
