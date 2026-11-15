<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantNotificationController extends Controller
{
    public function read(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()
            ->whereKey($notification)
            ->firstOrFail()
            ->markAsRead();

        return back()->with('success', 'Notification marquée comme lue.');
    }
}
