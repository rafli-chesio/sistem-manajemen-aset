<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    /**
     * Generate QR code SVG for an asset and return as response.
     */
    public function generate(Asset $asset): Response
    {
        $url = route('assets.show', $asset->id);

        $svg = QrCode::format('svg')
            ->size(300)
            ->errorCorrection('M')
            ->generate($url);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }

    /**
     * Generate and store a QR code reference for an asset.
     */
    public function assign(Asset $asset)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $qrCode = 'QR-' . strtoupper($asset->asset_code ?? 'AST-' . $asset->id);

        $asset->update(['qr_code' => $qrCode]);

        return back()->with('success', "QR Code '{$qrCode}' berhasil ditetapkan untuk aset {$asset->name}.");
    }

    /**
     * Scan QR code and redirect to asset page.
     * The QR points to the asset show URL directly, so this is a utility page.
     */
    public function scan()
    {
        return inertia('Assets/Scan');
    }
}
