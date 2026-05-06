<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BorrowRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user    = auth()->user();
        $isAdmin = $user->isAdmin();
        $isKajur = $user->isKajur();

        $stats      = [];
        $auditLogs  = [];

        if ($isAdmin || $user->isViewer()) {
            $stats = [
                'total_assets'     => Asset::count(),
                'available_assets' => Asset::where('type', 'FIXED')->where('status', 'AVAILABLE')->count(),
                'borrowed_assets'  => Asset::where('type', 'FIXED')->where('status', 'BORROWED')->count(),
                'maintenance_assets' => Asset::where('status', 'MAINTENANCE')->count(),
                'pending_borrows'  => BorrowRequest::where('status', 'PENDING')->count(),
                'overdue_borrows'  => BorrowRequest::where('status', 'OVERDUE')->count(),
                'total_users'      => User::count(),
            ];
        }

        if ($isAdmin) {
            $auditLogs = \App\Models\AuditLog::with('user')->latest()->take(6)->get()->map(fn($log) => [
                'id'         => $log->id,
                'action'     => $log->action,
                'user'       => $log->user ? $log->user->name : 'System',
                'created_at' => $log->created_at->diffForHumans(),
                'time'       => $log->created_at->format('H:i'),
            ]);
        }

        $borrowsQuery = BorrowRequest::with(['user', 'items.asset'])->latest();

        if ($isKajur) {
            $borrowsQuery->where('user_id', $user->id);
            $stats = [
                'my_pending'  => BorrowRequest::where('user_id', $user->id)->where('status', 'PENDING')->count(),
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

        // Quick availability search
        $searchQuery    = $request->input('q', '');
        $quickResults   = [];
        if ($searchQuery && strlen($searchQuery) >= 2) {
            $quickResults = Asset::with(['category', 'location'])
                ->where(function ($q) use ($searchQuery) {
                    $q->where('name', 'like', "%{$searchQuery}%")
                      ->orWhere('asset_code', 'like', "%{$searchQuery}%")
                      ->orWhere('brand', 'like', "%{$searchQuery}%");
                })
                ->select(['id', 'name', 'asset_code', 'type', 'status', 'stock', 'brand', 'condition', 'category_id', 'location_id'])
                ->limit(10)
                ->get()
                ->map(fn($a) => [
                    'id'        => $a->id,
                    'name'      => $a->name,
                    'code'      => $a->asset_code,
                    'type'      => $a->type,
                    'status'    => $a->status,
                    'stock'     => $a->stock,
                    'brand'     => $a->brand,
                    'condition' => $a->condition,
                    'available' => $a->isAvailable(),
                    'category'  => $a->category?->name,
                    'location'  => $a->location?->name,
                ]);
        }

        return Inertia::render('Dashboard/Index', [
            'stats'         => $stats,
            'recentBorrows' => $recentBorrows,
            'auditLogs'     => $auditLogs,
            'quickResults'  => $quickResults,
            'searchQuery'   => $searchQuery,
        ]);
    }
}
