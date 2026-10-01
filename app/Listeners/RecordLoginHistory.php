<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;

class RecordLoginHistory
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->loginHistories()->create([
            'ip_address'   => request()->ip(),
            'user_agent'   => str(request()->userAgent())->limit(500)->toString(),
            'logged_in_at' => now(),
        ]);
    }
}
