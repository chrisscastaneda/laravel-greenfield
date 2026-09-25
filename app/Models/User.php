<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $workos_id
 * @property string|null $remember_token
 * @property string $avatar
 * @property bool $is_admin
 * @property Carbon|null $deactivated_at
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'workos_id', 'avatar'])]
#[Hidden(['workos_id', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_admin' => false,
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
            'is_admin' => 'boolean',
            'deactivated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Determine if the user has admin access.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Determine if the user's account has been deactivated.
     */
    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /**
     * Determine if the user's email is listed in ADMIN_EMAILS.
     */
    public function isEnvironmentAdmin(): bool
    {
        return in_array(strtolower($this->email), config('admin.emails'), true);
    }

    /**
     * Determine if the user is the only active admin.
     */
    public function isLastActiveAdmin(): bool
    {
        return $this->isAdmin()
            && ! $this->isDeactivated()
            && ! static::query()->admins()->active()->whereKeyNot($this->getKey())->exists();
    }

    /**
     * Scope a query to admin users.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function admins(Builder $query): void
    {
        $query->where('is_admin', true);
    }

    /**
     * Scope a query to users that have not been deactivated.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('deactivated_at');
    }
}
