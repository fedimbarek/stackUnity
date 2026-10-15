<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AdminAuditLog::with('admin')->latest()->paginate(20);

        return view('audit-logs.index', compact('logs'));
    }
}