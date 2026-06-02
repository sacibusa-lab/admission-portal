<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Write an audit log entry to the database.
     */
    public static function log(string $action, array $details = [], ?int $userId = null): void
    {
        try {
            AuditLog::create([
                'user_id' => $userId ?? (Auth::check() ? Auth::id() : null),
                'action' => $action,
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'user_agent' => Request::userAgent(),
                'details' => $details
            ]);
        } catch (\Exception $e) {
            // Silently fail or log to Laravel logs so audit logging never crashes the application
            \Illuminate\Support\Facades\Log::error('Audit Logging failed: ' . $e->getMessage());
        }
    }
}
