<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Users extends Component
{
    public bool $showCreateModal = false;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_admin = false;

    public function createUser(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_admin' => ['boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'is_admin' => $validated['is_admin'],
        ]);

        $this->reset(['name', 'email', 'password', 'password_confirmation', 'is_admin', 'showCreateModal']);
        session()->flash('status', 'User created successfully.');
    }

    public function deleteUser(User $user): void
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');

            return;
        }

        $user->delete();
        session()->flash('status', 'User deleted successfully.');
    }

    public function render()
    {
        return view('livewire.settings.users', [
            'users' => User::orderBy('created_at', 'desc')->get(),
        ]);
    }
}
