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
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, fn($q, $s) => $q->whereHas(
                'user', fn($uq) => $uq->where('name', 'like', "%{$s}%")
            ));

        // KAJUR hanya melihat permintaan milik sendiri
        if ($user->isKajur()) {
            $query->where('user_id', $user->id);
        }

        $borrows = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Borrows/Index', [
            'borrows'    => $borrows,
            'filters'    => $request->only(['status', 'search']),
            'canApprove' => $user->canApprove(),
        ]);
    }

    public function create(): Response
    {
        $user = auth()->user();

        // Hanya KAJUR dan ADMIN yang bisa mengajukan peminjaman
        abort_unless($user->canManageAssets(), 403, 'Anda tidak memiliki akses untuk meminjam aset.');

        $assets = Asset::with(['category', 'images'])
            ->where(fn($q) => $q
                ->where(fn($sq) => $sq->where('type', 'FIXED')->where('status', 'AVAILABLE'))
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

            $hasFixed = collect($items)->contains(function ($item) {
                $asset = Asset::find($item['asset_id']);
                return $asset && $asset->isFixed();
            });

            // Maks pinjam 30 hari untuk aset tetap
            $returnDate = $hasFixed
                ? $borrowDate->copy()->addDays(30)
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
            'returnRecord.returnItems.borrowItem.asset',
        ]);

        $user = auth()->user();

        return Inertia::render('Borrows/Show', [
            'borrow'     => $borrow,
            'canApprove' => $user->canApprove(),
            'canReturn'  => $user->isKajur() || $user->isAdmin(),
        ]);
    }

    public function approve(BorrowRequest $borrow): RedirectResponse
    {
        abort_unless(auth()->user()->canApprove(), 403);

        try {
            $this->borrowService->approve($borrow, auth()->user());
            return back()->with('success', 'Permintaan peminjaman disetujui.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, BorrowRequest $borrow): RedirectResponse
    {
        abort_unless(auth()->user()->canApprove(), 403);

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
