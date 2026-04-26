<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('audit.view');

        $logs = AuditLog::with('user')
            ->when($request->action, fn($q, $a) => $q->where('action', 'like', "%{$a}%"))
            ->when($request->user_id, fn($q, $u) => $q->where('user_id', $u))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('AuditLogs/Index', [
            'logs'    => $logs,
            'filters' => $request->only(['action', 'user_id']),
        ]);
    }
}
