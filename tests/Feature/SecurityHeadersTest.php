<?php

it('sends every required security header (spec §10)', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertHeader('X-Frame-Options', 'DENY');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain('nonce-')
        ->toContain("frame-ancestors 'none'")
        ->toContain("object-src 'none'")
        ->not->toContain('unsafe-inline');
});

it('does not leak stack traces when debug is off', function () {
    config(['app.debug' => false]);

    Route::get('/__phase1-boom', function (): never {
        throw new RuntimeException('sensitive-internal-detail');
    });

    $response = $this->get('/__phase1-boom');

    $response->assertStatus(500);
    $response->assertDontSee('sensitive-internal-detail');
    $response->assertDontSee('RuntimeException');
});
