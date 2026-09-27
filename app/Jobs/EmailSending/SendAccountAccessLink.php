<?php

namespace App\Jobs\EmailSending;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Password;
use App\Notifications\AccountAccessNotification;

class SendAccountAccessLink implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
    ) {}

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if (! $user) {
            return;
        }

        $token = Password::broker()->createToken($user);

        $user->notify(new AccountAccessNotification(
            token: $token,
            isInvitation: true,
        ));
    }
}
