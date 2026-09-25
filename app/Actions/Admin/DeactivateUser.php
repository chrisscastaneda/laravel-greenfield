<?php

namespace App\Actions\Admin;

use App\Exceptions\AdminOperationException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeactivateUser
{
    /**
     * Deactivate the given user. Deactivated users cannot sign in, and any
     * existing sessions are ended on their next request.
     *
     * @throws AdminOperationException
     */
    public function handle(User $user): User
    {
        return DB::transaction(function () use ($user) {
            User::query()->admins()->active()->lockForUpdate()->pluck('id');

            $this->ensureAllowed($user->refresh());

            $user->forceFill(['deactivated_at' => now()])->save();

            return $user;
        });
    }

    /**
     * Ensure the given user may be deactivated.
     *
     * @throws AdminOperationException
     */
    public function ensureAllowed(User $user): void
    {
        if ($user->isDeactivated()) {
            throw AdminOperationException::alreadyDeactivated();
        }

        if ($user->isEnvironmentAdmin()) {
            throw AdminOperationException::environmentAdmin();
        }

        if ($user->isLastActiveAdmin()) {
            throw AdminOperationException::lastAdmin();
        }
    }
}
