<section class="w-full">
    @include('partials.admin-heading')

    <x-admin.layout>
        <x-slot name="heading">{{ __('User Management') }}</x-slot>
        <x-slot name="subheading">{{ __('Manage users who can access this application') }}</x-slot>

        @if (session('status'))
        <flux:callout variant="success" class="mb-6">{{ session('status') }}</flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" class="mb-6">{{ session('error') }}</flux:callout>
    @endif

    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ $users->count() }} {{ Str::plural('user', $users->count()) }} total
            </p>
            <flux:button wire:click="$set('showCreateModal', true)" variant="primary" icon="plus">
                Add User
            </flux:button>
        </div>

        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
            @foreach ($users as $user)
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</span>
                            @if ($user->id === auth()->id())
                                <flux:badge variant="primary" size="sm" inset="top bottom">You</flux:badge>
                            @endif
                            @if ($user->is_admin)
                                <flux:badge variant="success" size="sm" inset="top bottom">Admin</flux:badge>
                            @endif
                        </div>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 mt-2">{{ $user->email }}</div>
                    </div>
                    <div>
                        @if ($user->id !== auth()->id())
                            <flux:button
                                size="sm"
                                variant="ghost"
                                class="hover:text-red-600 dark:hover:text-red-400"
                                wire:click="deleteUser({{ $user->id }})"
                                wire:confirm="Are you sure you want to delete this user? This will delete all their data including people, events, and gifts."
                            >
                                Delete
                            </flux:button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Create User Modal --}}
    <flux:modal wire:model="showCreateModal" class="max-w-md">
        <form wire:submit="createUser" class="space-y-6">
            <div>
                <flux:heading size="lg">Add New User</flux:heading>
                <flux:subheading>Create a new user account for this household</flux:subheading>
            </div>

            <flux:input
                wire:model="name"
                label="Name"
                placeholder="John Doe"
                required
            />

            <flux:input
                wire:model="email"
                label="Email"
                type="email"
                placeholder="user@example.com"
                required
            />

            <flux:input
                wire:model="password"
                label="Password"
                type="password"
                placeholder="Minimum 8 characters"
                required
                viewable
            />

            <flux:input
                wire:model="password_confirmation"
                label="Confirm Password"
                type="password"
                placeholder="Re-enter password"
                required
            />

            <flux:checkbox wire:model="is_admin" label="Admin User" description="Grant administrative privileges to this user" />

            <div class="flex gap-3 justify-end">
                <flux:button type="button" variant="ghost" wire:click="$set('showCreateModal', false)">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Create User
                </flux:button>
            </div>
        </form>
    </flux:modal>
    </x-admin.layout>
</section>
