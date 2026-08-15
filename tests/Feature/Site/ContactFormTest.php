<?php

use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function validContactPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'زائر مهتم',
        'email' => 'visitor@example.com',
        'phone' => '91234567',
        'subject' => 'استفسار عن دورة',
        'message' => 'أرغب في معرفة مواعيد الدفعة القادمة من دورة الذكاء الاصطناعي.',
        'company_website' => '',
        '_started_at' => time() - 30,
    ], $overrides);
}

it('stores a valid message and notifies the owners', function () {
    Notification::fake();
    $this->seed(RolesAndPermissionsSeeder::class);

    $owner = User::factory()->create();
    $owner->assignRole('owner');

    $this->post(route('public.contact.store'), validContactPayload())
        ->assertRedirect(route('public.contact'))
        ->assertSessionHas('contact_success');

    expect(ContactMessage::query()->count())->toBe(1);
    Notification::assertSentTo($owner, ContactMessageReceived::class);
});

it('silently drops honeypot submissions', function () {
    Notification::fake();

    $this->post(route('public.contact.store'), validContactPayload([
        'company_website' => 'https://spam.example',
    ]))
        ->assertRedirect(route('public.contact'))
        ->assertSessionHas('contact_success');

    expect(ContactMessage::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('silently drops submissions filled faster than a human could', function () {
    $this->post(route('public.contact.store'), validContactPayload([
        '_started_at' => time(),
    ]))->assertSessionHas('contact_success');

    expect(ContactMessage::query()->count())->toBe(0);
});

it('validates required fields with Arabic messages', function () {
    $this->post(route('public.contact.store'), validContactPayload([
        'name' => '',
        'message' => 'قصير',
    ]))->assertSessionHasErrors(['name', 'message']);

    expect(ContactMessage::query()->count())->toBe(0);
});

it('rate limits the endpoint per IP', function () {
    $status = null;

    foreach (range(1, 4) as $i) {
        $status = $this->post(route('public.contact.store'), validContactPayload([
            'email' => "visitor{$i}@example.com",
        ]))->getStatusCode();
    }

    expect($status)->toBe(429);
});
