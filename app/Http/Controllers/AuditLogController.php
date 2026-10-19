<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        if ($search = $request->input('search')) {
            $query->whereRaw('LOWER(action) LIKE ?', ['%' . strtolower($search) . '%']);
        }

        $logs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
        $modules = AuditLog::select('module')->distinct()->pluck('module');

        return view('audit-logs.index', compact('logs', 'modules'));
    }
}
