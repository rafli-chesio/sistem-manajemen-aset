<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(): Response
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markRead(Request $request)
    {
        $notifId = $request->input('id');

        if ($notifId) {
            auth()->user()->notifications()->where('id', $notifId)->update(['read_at' => now()]);
        } else {
            auth()->user()->unreadNotifications->markAsRead();
        }

        return back();
    }

    public function count(): JsonResponse
    {
        return response()->json([
            'count' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
