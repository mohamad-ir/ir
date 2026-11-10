<?php

/* Regression tests for issues found in the 1.7.0 release QA. */

beforeEach(fn () => $this->seedRoles());

it('sends the security headers on dashboard pages too, including the sign-in page', function () {
    foreach (['/admin/login', '/admin/password-reset/request'] as $url) {
        $this->get($url)->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
    $this->actingAsStaff();
    $this->get('/admin')->assertOk()->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});
