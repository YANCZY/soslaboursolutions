<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;


class NotificationController extends Controller
{
    public function open(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();

        $notificationRecord = $user->notifications()
            ->where('id', $notification)
            ->firstOrFail();

        if (is_null($notificationRecord->read_at)) {
            $notificationRecord->markAsRead();
        }

        $targetUrl = data_get($notificationRecord->data, 'url', route('for-approvals.index'));

        return redirect()->to($targetUrl);
    }
}
