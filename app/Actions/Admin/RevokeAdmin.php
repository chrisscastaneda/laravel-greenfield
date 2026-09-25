<?php

namespace App\Actions\Admin;

use App\Exceptions\AdminOperationException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RevokeAdmin
{
    /**
     * Revoke admin access from the given user.
     *
     * @throws AdminOperationException
     */
    public function handle(User $user): User
    {
        return DB::transaction(function () use ($user) {
            // Lock the active admins so two concurrent revocations cannot
            // leave the application without an admin.
            User::query()->admins()->active()->lockForUpdate()->pluck('id');

            $this->ensureAllowed($user->refresh());

            $user->forceFill(['is_admin' => false])->save();

            return $user;
        });
    }

    /**
     * Ensure admin access may be revoked from the given user.
     *
     * @throws AdminOperationException
     */
    public function ensureAllowed(User $user): void
    {
        if (! $user->isAdmin()) {
            throw AdminOperationException::notAdmin();
        }

        if ($user->isEnvironmentAdmin()) {
            throw AdminOperationException::environmentAdmin();
        }

        if ($user->isLastActiveAdmin()) {
            throw AdminOperationException::lastAdmin();
        }
    }
}
