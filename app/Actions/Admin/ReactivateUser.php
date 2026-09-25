<?php

namespace App\Actions\Admin;

use App\Exceptions\AdminOperationException;
use App\Models\User;

class ReactivateUser
{
    /**
     * Reactivate the given user.
     *
     * @throws AdminOperationException
     */
    public function handle(User $user): User
    {
        $this->ensureAllowed($user);

        $user->forceFill(['deactivated_at' => null])->save();

        return $user;
    }

    /**
     * Ensure the given user may be reactivated.
     *
     * @throws AdminOperationException
     */
    public function ensureAllowed(User $user): void
    {
        if (! $user->isDeactivated()) {
            throw AdminOperationException::notDeactivated();
        }
    }
}
