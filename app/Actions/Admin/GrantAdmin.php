<?php

namespace App\Actions\Admin;

use App\Exceptions\AdminOperationException;
use App\Models\User;

class GrantAdmin
{
    /**
     * Grant admin access to the given user.
     *
     * @throws AdminOperationException
     */
    public function handle(User $user): User
    {
        $this->ensureAllowed($user);

        $user->forceFill(['is_admin' => true])->save();

        return $user;
    }

    /**
     * Ensure admin access may be granted to the given user.
     *
     * @throws AdminOperationException
     */
    public function ensureAllowed(User $user): void
    {
        if ($user->isAdmin()) {
            throw AdminOperationException::alreadyAdmin();
        }

        if ($user->isDeactivated()) {
            throw AdminOperationException::deactivated();
        }
    }
}
