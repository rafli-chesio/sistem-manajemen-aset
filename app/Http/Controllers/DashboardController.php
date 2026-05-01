<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BorrowRequest;
use App\Models\User;
use App\Models\AuditLog;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $isKajur = $user->hasRole('kajur');

        $stats = [];
        $auditLogs = [];

        if ($isSuperAdmin || $user->hasRole('viewer')) {
            $stats = [
                'total_assets'        => Asset::count(),
                'available_assets'    => Asset::where('type', 'UNIQUE')->where('status', 'AVAILABLE')->count(),
                'borrowed_assets'     => Asset::where('type', 'UNIQUE')->where('status', 'BORROWED')->count(),
                'damaged_assets'      => Asset::where('type', 'UNIQUE')->where('status', 'DAMAGED')->count(),
                'pending_borrows'     => BorrowRequest::where('status', 'PENDING')->count(),
                'overdue_borrows'     => BorrowRequest::where('status', 'OVERDUE')->count(),
                'total_users'         => User::count(),
            ];
        }

        if ($isSuperAdmin) {
            $auditLogs = \App\Models\AuditLog::with('user')->latest()->take(6)->get()->map(fn($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'user' => $log->user ? $log->user->name : 'System',
                'created_at' => $log->created_at->diffForHumans(),
                'time' => $log->created_at->format('H:i')
            ]);
        }

        $borrowsQuery = BorrowRequest::with(['user', 'items.asset'])->latest();
        
        if ($isKajur) {
            $borrowsQuery->where('user_id', $user->id);
            // Kajur stats
            $stats = [
                'my_pending' => BorrowRequest::where('user_id', $user->id)->where('status', 'PENDING')->count(),
                'my_approved' => BorrowRequest::where('user_id', $user->id)->where('status', 'APPROVED')->count(),
                'my_rejected' => BorrowRequest::where('user_id', $user->id)->where('status', 'REJECTED')->count(),
            ];
        }

        $recentBorrows = $borrowsQuery->take($isKajur ? 10 : 5)->get()->map(fn($req) => [
            'id'          => $req->id,
            'user_name'   => $req->user?->name ?? 'Pengguna Dihapus',
            'status'      => $req->status,
            'borrow_date' => $req->borrow_date->format('d/m/Y'),
            'return_date' => $req->return_date->format('d/m/Y'),
            'item_count'  => $req->items->count(),
        ]);

        return Inertia::render('Dashboard/Index', [
            'stats'         => $stats,
            'recentBorrows' => $recentBorrows,
            'auditLogs'     => $auditLogs,
        ]);
    }
}

