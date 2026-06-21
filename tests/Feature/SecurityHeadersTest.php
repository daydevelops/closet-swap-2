<?php

test('security headers are present on api responses', function () {
    $response = $this->getJson(route('dashboard'));

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Content-Security-Policy', "default-src 'none'");
});
