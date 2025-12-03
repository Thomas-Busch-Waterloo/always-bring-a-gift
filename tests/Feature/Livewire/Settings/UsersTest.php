<?php

declare(strict_types=1);

use App\Livewire\Settings\Users;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->user);
});

test('can render users page', function () {
    $response = $this->get(route('admin.users.index'));

    $response->assertSuccessful();
    $response->assertSee('User Management');
});

test('non-admin cannot access users page', function () {
    $nonAdmin = User::factory()->create(['is_admin' => false]);
    $this->actingAs($nonAdmin);

    $response = $this->get(route('admin.users.index'));

    $response->assertForbidden();
});

test('displays all users', function () {
    $otherUser = User::factory()->create(['name' => 'Jane Doe']);

    Livewire::test(Users::class)
        ->assertSee($this->user->name)
        ->assertSee($this->user->email)
        ->assertSee($otherUser->name)
        ->assertSee($otherUser->email);
});

test('can create user', function () {
    Livewire::test(Users::class)
        ->set('name', 'New User')
        ->set('email', 'newuser@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('createUser')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false);

    $user = User::where('email', 'newuser@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('New User');
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->is_admin)->toBeFalse();
});

test('can create admin user', function () {
    Livewire::test(Users::class)
        ->set('name', 'Admin User')
        ->set('email', 'admin@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('is_admin', true)
        ->call('createUser')
        ->assertHasNoErrors();

    $user = User::where('email', 'admin@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->is_admin)->toBeTrue();
});

test('validates user creation', function () {
    Livewire::test(Users::class)
        ->set('name', '')
        ->set('email', 'invalid-email')
        ->set('password', 'short')
        ->set('password_confirmation', 'different')
        ->call('createUser')
        ->assertHasErrors(['name', 'email', 'password']);
});

test('requires unique email for user creation', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.com']);

    Livewire::test(Users::class)
        ->set('name', 'New User')
        ->set('email', 'existing@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('createUser')
        ->assertHasErrors(['email']);
});

test('requires password confirmation', function () {
    Livewire::test(Users::class)
        ->set('name', 'New User')
        ->set('email', 'newuser@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'different')
        ->call('createUser')
        ->assertHasErrors(['password']);
});

test('can delete other users', function () {
    $otherUser = User::factory()->create();

    Livewire::test(Users::class)
        ->call('deleteUser', $otherUser->id)
        ->assertHasNoErrors();

    expect(User::find($otherUser->id))->toBeNull();
});

test('cannot delete own account', function () {
    Livewire::test(Users::class)
        ->call('deleteUser', $this->user)
        ->assertHasNoErrors()
        ->assertSee('You cannot delete your own account.');

    expect(User::find($this->user->id))->not->toBeNull();
});

test('shows you badge for current user', function () {
    Livewire::test(Users::class)
        ->assertSee('You');
});

test('shows user count', function () {
    User::factory()->count(2)->create();

    Livewire::test(Users::class)
        ->assertSee('3 users total');
});

test('shows admin badge for admin users', function () {
    $admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin User']);

    Livewire::test(Users::class)
        ->assertSee('Admin User')
        ->assertSee('Admin');
});
