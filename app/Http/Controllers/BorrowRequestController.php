<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBorrowRequest;
use App\Models\Asset;
use App\Models\BorrowRequest;
use App\Services\BorrowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BorrowRequestController extends Controller
{
    public function __construct(private readonly BorrowService $borrowService) {}

    public function index(Request $request): Response
    {
        $user = auth()->user();

        $query = BorrowRequest::with(['user', 'items.asset', 'approver'])
            ->when($request->status,  fn($q, $s) => $q->where('status', $s))
            ->when($request->search,  fn($q, $s) => $q->whereHas(
                'user', fn($uq) => $uq->where('name', 'like', "%{$s}%")
            ));

        // Kajur only sees own requests
        if ($user->hasRole('kajur')) {
            $query->where('user_id', $user->id);
        }

        $borrows = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Borrows/Index', [
            'borrows' => $borrows,
            'filters' => $request->only(['status', 'search']),
            'canApprove' => $user->can('borrow.approve'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('borrow.create');

        $assets = Asset::with(['category', 'images'])
            ->where(fn($q) => $q
                ->where(fn($sq) => $sq->where('type', 'UNIQUE')->where('status', 'AVAILABLE'))
                ->orWhere(fn($sq) => $sq->where('type', 'CONSUMABLE')->where('stock', '>', 0))
            )
            ->orderBy('name')
            ->get();

        return Inertia::render('Borrows/Create', [
            'availableAssets' => $assets,
        ]);
    }

    public function store(StoreBorrowRequest $request): RedirectResponse
    {
        try {
            $items      = $request->input('items');
            $borrowDate = today();

            // Cek apakah request mengandung UNIQUE asset
            $hasUnique = collect($items)->contains(function ($item) {
                $asset = Asset::find($item['asset_id']);
                return $asset && $asset->isUnique();
            });

            // Tanggal kembali: +7 hari untuk UNIQUE, sama dengan borrow_date untuk CONSUMABLE only
            $returnDate = $hasUnique
                ? $borrowDate->copy()->addDays(7)
                : $borrowDate->copy();

            $borrow = $this->borrowService->createRequest(
                auth()->user(),
                [
                    'borrow_date' => $borrowDate->toDateString(),
                    'return_date' => $returnDate->toDateString(),
                    'notes'       => $request->input('notes'),
                ],
                $items
            );

            return redirect()->route('borrows.show', $borrow)
                ->with('success', 'Permintaan peminjaman berhasil diajukan dan menunggu persetujuan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(BorrowRequest $borrow): Response
    {
        $borrow->load([
            'user',
            'items.asset.images',
            'approver',
            'returnRecord.images',
            'returnRecord.processor',
            'returnRecord.returnItems.borrowItem.asset',  // ← per-item conditions
        ]);

        return Inertia::render('Borrows/Show', [
            'borrow'     => $borrow,
            'canApprove' => auth()->user()->can('borrow.approve'),
            'canReturn'  => auth()->user()->hasRole('kajur'),  // hanya kajur yang mengembalikan
        ]);
    }

    public function approve(BorrowRequest $borrow): RedirectResponse
    {
        $this->authorize('borrow.approve');

        try {
            $this->borrowService->approve($borrow, auth()->user());
            return back()->with('success', 'Permintaan peminjaman disetujui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, BorrowRequest $borrow): RedirectResponse
    {
        $this->authorize('borrow.approve');

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->borrowService->reject($borrow, auth()->user(), $request->rejection_reason);
            return back()->with('success', 'Permintaan peminjaman ditolak.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
