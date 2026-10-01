<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('records the time, IP address and browser of every login', function (): void {
    $user = User::factory()->create();

    $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.24', 'HTTP_USER_AGENT' => 'Mozilla/5.0 Test']);
    $this->get('/');
    Auth::login($user);

    expect($user->loginHistories()->sole())
        ->ip_address->toBe('192.168.1.24')
        ->logged_in_at->not->toBeNull();
});
