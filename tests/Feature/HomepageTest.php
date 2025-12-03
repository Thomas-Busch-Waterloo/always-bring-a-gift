<?php

declare(strict_types=1);

test('root redirects to dashboard', function () {
    $response = $this->get('/');

    $response->assertRedirect('dashboard');
});
