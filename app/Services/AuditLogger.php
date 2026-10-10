<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Log a system audit event into PostgreSQL 18.
     */
    public static function log(
        string $action,
        string $module,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $changes = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }
}
