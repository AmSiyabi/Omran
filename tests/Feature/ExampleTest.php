<?php

it('renders the Arabic RTL shell on the landing route', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('lang="ar"', escape: false);
    $response->assertSee('dir="rtl"', escape: false);
});
