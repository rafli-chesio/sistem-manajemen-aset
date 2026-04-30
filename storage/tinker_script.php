<?php
// Simulate a borrow request
$kajur = App\Models\User::role('kajur')->first();
$admin = App\Models\User::role('super_admin')->first();

if (!$kajur || !$admin) {
    echo "Kajur or Admin not found.\n";
    exit;
}

$asset = App\Models\Asset::where('stock', '>', 0)->orWhere('status', 'AVAILABLE')->first();
if (!$asset) {
    echo "No available asset found.\n";
    exit;
}

$service = app(App\Services\BorrowService::class);

echo "Creating borrow request...\n";
$request = $service->createRequest($kajur, [
    'borrow_date' => now()->toDateString(),
    'return_date' => now()->addDays(7)->toDateString(),
    'notes' => 'Test borrow request'
], [
    ['asset_id' => $asset->id, 'quantity' => 1]
]);

echo "Request created: " . $request->id . "\n";

echo "Admin notifications count: " . $admin->notifications()->count() . "\n";

echo "Approving request...\n";
$service->approve($request, $admin);

echo "Kajur notifications count: " . $kajur->notifications()->count() . "\n";
