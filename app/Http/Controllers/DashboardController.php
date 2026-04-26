<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BorrowRequest;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $stats = [
            'total_assets'        => Asset::count(),
            'available_assets'    => Asset::where('type', 'UNIQUE')->where('status', 'AVAILABLE')->count(),
            'borrowed_assets'     => Asset::where('type', 'UNIQUE')->where('status', 'BORROWED')->count(),
            'damaged_assets'      => Asset::where('type', 'UNIQUE')->where('status', 'DAMAGED')->count(),
            'pending_borrows'     => BorrowRequest::where('status', 'PENDING')->count(),
            'overdue_borrows'     => BorrowRequest::where('status', 'OVERDUE')->count(),
            'total_users'         => User::count(),
        ];

        $recentBorrows = BorrowRequest::with(['user', 'items.asset'])
            ->latest()
            ->take(5)
            ->get();

        $recentBorrows = $recentBorrows->map(fn($req) => [
            'id'          => $req->id,
            'user_name'   => $req->user->name,
            'status'      => $req->status,
            'borrow_date' => $req->borrow_date->format('d/m/Y'),
            'return_date' => $req->return_date->format('d/m/Y'),
            'item_count'  => $req->items->count(),
        ]);

        return Inertia::render('Dashboard/Index', [
            'stats'         => $stats,
            'recentBorrows' => $recentBorrows,
        ]);
    }
}
