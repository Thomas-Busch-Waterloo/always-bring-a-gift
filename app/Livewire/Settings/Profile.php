<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public string $timezone = 'UTC';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->timezone = Auth::user()->getUserTimezone();
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],

            'timezone' => ['required', 'string', 'timezone:all'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Get the list of available timezones as a flat array.
     *
     * @return array<string, string>
     */
    public function getTimezonesProperty(): array
    {
        $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
        $list = [];

        foreach ($timezones as $timezone) {
            $parts = explode('/', $timezone, 2);
            $region = $parts[0];
            $city = $parts[1] ?? $timezone;

            // Format the display name with region prefix
            $displayName = $region.' / '.str_replace(['_', '/'], [' ', ' / '], $city);
            $list[$timezone] = $displayName;
        }

        // Sort by display name
        asort($list);

        return $list;
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}
