<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessReturnRequest;
use App\Models\BorrowRequest;
use App\Services\ReturnService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReturnController extends Controller
{
    public function __construct(private readonly ReturnService $returnService) {}

    public function create(BorrowRequest $borrow): Response
    {
        $this->authorize('return.create');

        if (!$borrow->isReturnable()) {
            abort(403, 'Permintaan ini tidak dapat dikembalikan.');
        }

        // Load items.asset.images agar form bisa tampilkan info lengkap per item
        $borrow->load(['user', 'items.asset.images']);

        return Inertia::render('Returns/Create', [
            'borrow' => $borrow,
        ]);
    }

    public function store(ProcessReturnRequest $request, BorrowRequest $borrow): RedirectResponse
    {
        try {
            $this->returnService->processReturn(
                $borrow,
                auth()->user(),
                $request->input('item_conditions'),   // ← array per item
                $request->file('images', []),
                $request->input('notes')
            );

            return redirect()->route('borrows.show', $borrow)
                ->with('success', 'Pengembalian berhasil dicatat. Terima kasih!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
