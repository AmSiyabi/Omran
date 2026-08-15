<?php

use App\Models\User;
use App\Notifications\NewDeviceLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('renders the Arabic login page', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('تسجيل الدخول')
        ->assertSee('dir="rtl"', escape: false);
});

it('throttles login after 5 failed attempts per email and IP', function () {
    $user = User::factory()->create(['email' => 'target@omran.local']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', [
            'email' => 'target@omran.local',
            'password' => 'wrong-password-'.$attempt,
        ]);
    }

    $response = $this->post('/login', [
        'email' => 'target@omran.local',
        'password' => 'wrong-password-6',
    ]);

    // Fortify converts the lockout into a validation error carrying the
    // throttle translation ("حاول مرة أخرى بعد :seconds ثانية")
    $response->assertSessionHasErrors('email');

    $message = session('errors')->first('email');
    expect($message)->toContain('مرة أخرى بعد');
});

it('hard-caps a brute-force burst with HTTP 429 from the outer limiter', function () {
    User::factory()->create(['email' => 'burst@omran.local']);

    $status = null;

    // The middleware tier allows 10/min; the 11th request must be cut off
    foreach (range(1, 11) as $attempt) {
        $status = $this->post('/login', [
            'email' => 'burst@omran.local',
            'password' => 'wrong-'.$attempt,
        ])->getStatusCode();
    }

    expect($status)->toBe(429);
});

it('records last login and known device, and notifies on a new device only', function () {
    Notification::fake();

    $user = User::factory()->create(['password' => bcrypt('correct-horse-battery')]);

    // First device — baseline, no alert
    $this->withHeader('User-Agent', 'DeviceOne/1.0')
        ->post('/login', ['email' => $user->email, 'password' => 'correct-horse-battery'])
        ->assertRedirect();

    expect($user->refresh()->last_login_at)->not->toBeNull()
        ->and($user->devices()->count())->toBe(1);

    Notification::assertNothingSent();

    $this->post('/logout');

    // Second, different device — alert expected
    $this->withHeader('User-Agent', 'DeviceTwo/2.0')
        ->post('/login', ['email' => $user->email, 'password' => 'correct-horse-battery'])
        ->assertRedirect();

    expect($user->devices()->count())->toBe(2);

    Notification::assertSentTo($user, NewDeviceLogin::class);
});
