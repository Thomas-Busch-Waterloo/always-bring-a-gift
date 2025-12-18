<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'is_admin',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get the notification settings for the user.
     */
    public function notificationSetting(): HasOne
    {
        return $this->hasOne(NotificationSetting::class);
    }

    /**
     * Get the user's notification preferences.
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class, 'user_id');
    }

    /**
     * Get the user's notification templates.
     */
    public function notificationTemplates(): HasMany
    {
        return $this->hasMany(NotificationTemplate::class, 'user_id');
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Resolve Slack webhook routing for notifications.
     */
    public function routeNotificationForSlack(): ?string
    {
        return $this->notificationSetting?->slackWebhook();
    }

    /**
     * Resolve Discord webhook routing for notifications.
     */
    public function routeNotificationForDiscord(): ?string
    {
        return $this->notificationSetting?->discordWebhook();
    }

    /**
     * Resolve push routing for notifications.
     */
    public function routeNotificationForPush(): ?array
    {
        $setting = $this->notificationSetting;

        if (! $setting) {
            return null;
        }

        $endpoint = $setting->pushEndpoint();

        if (! $endpoint) {
            return null;
        }

        return [
            'endpoint' => $endpoint,
            'token' => $setting->pushToken(),
        ];
    }

    /**
     * Get the user's timezone with a fallback to UTC.
     */
    public function getUserTimezone(): string
    {
        return $this->timezone ?? 'UTC';
    }
}
