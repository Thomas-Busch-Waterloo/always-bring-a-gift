<?php

test('registration screen can be rendered', function () {
    if (! config('auth.registration_enabled')) {
        $this->markTestSkipped('Registration is disabled');
    }

    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    if (! config('auth.registration_enabled')) {
        $this->markTestSkipped('Registration is disabled');
    }

    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('registration routes are not available when disabled', function () {
    if (config('auth.registration_enabled')) {
        $this->markTestSkipped('Registration is enabled');
    }

    // Verify register routes don't exist in the route collection
    $routes = app('router')->getRoutes();

    expect($routes->hasNamedRoute('register'))->toBeFalse('register route should not exist');
    expect($routes->hasNamedRoute('register.store'))->toBeFalse('register.store route should not exist');
});
