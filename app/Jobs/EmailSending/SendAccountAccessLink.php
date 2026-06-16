<?php

namespace App\Jobs\EmailSending;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Password;
use RuntimeException;

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

        $status = Password::sendResetLink([
            'email' => $user->email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw new RuntimeException(__($status));
        }
    }
}
