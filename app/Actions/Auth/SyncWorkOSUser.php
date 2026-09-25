<?php

namespace App\Actions\Auth;

use App\Exceptions\AccountDeactivatedException;
use App\Models\User;
use Laravel\WorkOS\User as WorkOSUser;

/**
 * Finds, creates and updates local users from WorkOS AuthKit sign-ins.
 *
 * Passed as the find / create / update callbacks to
 * AuthKitAuthenticationRequest::authenticate(), which runs them before the
 * user is logged in.
 */
class SyncWorkOSUser
{
    /**
     * Find the local user for the given WorkOS user.
     */
    public function find(WorkOSUser $workosUser): ?User
    {
        return User::query()->where('workos_id', $workosUser->id)->first();
    }

    /**
     * Create a local user for a first-time WorkOS sign-in.
     */
    public function create(WorkOSUser $workosUser): User
    {
        $user = new User([
            'name' => $this->nameFor($workosUser),
            'email' => $workosUser->email,
            'workos_id' => $workosUser->id,
            'avatar' => $workosUser->avatar ?? '',
        ]);

        // WorkOS verifies email addresses, so every WorkOS user is verified.
        $user->forceFill([
            'email_verified_at' => now(),
            'is_admin' => $user->isEnvironmentAdmin(),
        ])->save();

        return $user;
    }

    /**
     * Update an existing local user from their WorkOS profile.
     *
     * @throws AccountDeactivatedException
     */
    public function update(User $user, WorkOSUser $workosUser): User
    {
        if ($user->isDeactivated()) {
            throw new AccountDeactivatedException;
        }

        $user->fill(['avatar' => $workosUser->avatar ?? '']);

        if (! $user->isAdmin() && $user->isEnvironmentAdmin()) {
            $user->forceFill(['is_admin' => true]);
        }

        $user->save();

        return $user;
    }

    /**
     * Build a display name, falling back to the email address.
     */
    private function nameFor(WorkOSUser $workosUser): string
    {
        $name = trim($workosUser->firstName.' '.$workosUser->lastName);

        return $name !== '' ? $name : $workosUser->email;
    }
}
