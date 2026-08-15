<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the Arabic RTL shell on the landing route', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('lang="ar"', escape: false);
    $response->assertSee('dir="rtl"', escape: false);
});
