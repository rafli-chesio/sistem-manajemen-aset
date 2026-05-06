<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\BorrowRequest;
use App\Models\Category;
use App\Models\Location;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    private function buildReportData(): array
    {
        $assetsByCategory = Category::withCount('assets')
            ->having('assets_count', '>', 0)
            ->get()
            ->map(fn($c) => ['name' => $c->name, 'count' => $c->assets_count]);

        $assetsByCondition = Asset::selectRaw('`condition`, count(*) as total')
            ->groupBy('condition')
            ->pluck('total', 'condition');

        $borrowsByStatus = BorrowRequest::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $borrowTrend = BorrowRequest::selectRaw("DATE_FORMAT(created_at, '%Y-%m-01') as month, count(*) as total")
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m-01')")
            ->orderByRaw("DATE_FORMAT(created_at, '%Y-%m-01')")
            ->get()
            ->map(fn($b) => [
                'month' => \Carbon\Carbon::parse($b->month)->format('M Y'),
                'total' => $b->total,
            ]);

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

        $damagedAssets = Asset::whereIn('condition', ['RUSAK_RINGAN', 'RUSAK_BERAT'])
            ->with(['category', 'location'])
            ->get()
            ->map(fn($a) => [
                'name'      => $a->name,
                'code'      => $a->asset_code,
                'condition' => $a->condition,
                'category'  => $a->category?->name,
                'location'  => $a->location?->name,
            ]);

        $metrics = [
            'total_assets'        => Asset::count(),
            'available_assets'    => Asset::where('status', 'AVAILABLE')->count(),
            'borrowed_assets'     => Asset::where('status', 'BORROWED')->count(),
            'maintenance_assets'  => Asset::where('status', 'MAINTENANCE')->count(),
            'damaged_assets'      => Asset::whereIn('condition', ['RUSAK_RINGAN', 'RUSAK_BERAT'])->count(),
            'total_transactions'  => BorrowRequest::count(),
            'pending_approvals'   => BorrowRequest::where('status', 'PENDING')->count(),
        ];

        return compact(
            'assetsByCategory', 'assetsByCondition', 'borrowsByStatus',
            'borrowTrend', 'topAssets', 'damagedAssets', 'metrics'
        );
    }

    public function index(): Response
    {
        return Inertia::render('Reports/Index', $this->buildReportData());
    }

    public function exportPdf(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $data = $this->buildReportData();
        $data['generated_at']  = now()->format('d F Y, H:i');
        $data['generated_by']  = auth()->user()->name;

        $pdf = Pdf::loadView('reports.pdf', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download('laporan-aset-' . now()->format('Ymd') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return Excel::download(
            new \App\Exports\AssetsExport(),
            'laporan-aset-' . now()->format('Ymd') . '.xlsx'
        );
    }
}
