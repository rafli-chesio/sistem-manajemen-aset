<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BorrowRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('report.view');

        // Asset summary by category
        $assetsByCategory = Category::withCount('assets')
            ->having('assets_count', '>', 0)
            ->get()
            ->map(fn($c) => ['name' => $c->name, 'count' => $c->assets_count]);

        // Asset condition distribution
        $assetsByCondition = Asset::selectRaw('`condition`, count(*) as total')
            ->groupBy('condition')
            ->pluck('total', 'condition');

        // Borrow status distribution
        $borrowsByStatus = BorrowRequest::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Monthly borrow trend (last 12 months)
        $borrowTrend = BorrowRequest::selectRaw("DATE_FORMAT(created_at, '%Y-%m-01') as month, count(*) as total")
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m-01')")
            ->orderByRaw("DATE_FORMAT(created_at, '%Y-%m-01')")
            ->get()
            ->map(fn($b) => [
                'month' => \Carbon\Carbon::parse($b->month)->format('M Y'),
                'total' => $b->total,
            ]);

        // Most borrowed assets
        $topAssets = Asset::withCount('borrowItems')
            ->having('borrow_items_count', '>', 0)
            ->orderByDesc('borrow_items_count')
            ->take(10)
            ->get()
            ->map(fn($a) => [
                'name'  => $a->name,
                'code'  => $a->asset_code,
                'count' => $a->borrow_items_count,
            ]);

        return Inertia::render('Reports/Index', [
            'assetsByCategory'  => $assetsByCategory,
            'assetsByCondition' => $assetsByCondition,
            'borrowsByStatus'   => $borrowsByStatus,
            'borrowTrend'       => $borrowTrend,
            'topAssets'         => $topAssets,
        ]);
    }
}
