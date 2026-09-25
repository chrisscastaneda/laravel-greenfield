<?php

namespace App\Policies;

use App\Actions\Admin\DeactivateUser;
use App\Actions\Admin\GrantAdmin;
use App\Actions\Admin\ReactivateUser;
use App\Actions\Admin\RevokeAdmin;
use App\Exceptions\AdminOperationException;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Only active admins may manage users.
     */
    public function before(User $actor): ?Response
    {
        return $actor->isAdmin() && ! $actor->isDeactivated()
            ? null
            : Response::deny();
    }

    /**
     * Determine whether the actor can list users.
     */
    public function viewAny(User $actor): bool
    {
        return true;
    }

    /**
     * Determine whether the actor can view the user.
     */
    public function view(User $actor, User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the actor can grant admin access to the user.
     */
    public function grantAdmin(User $actor, User $user): Response
    {
        return $this->check(fn () => app(GrantAdmin::class)->ensureAllowed($user));
    }

    /**
     * Determine whether the actor can revoke the user's admin access.
     */
    public function revokeAdmin(User $actor, User $user): Response
    {
        if ($actor->is($user)) {
            return Response::deny('You cannot revoke your own admin access.');
        }

        return $this->check(fn () => app(RevokeAdmin::class)->ensureAllowed($user));
    }

    /**
     * Determine whether the actor can deactivate the user.
     */
    public function deactivate(User $actor, User $user): Response
    {
        if ($actor->is($user)) {
            return Response::deny('You cannot deactivate your own account.');
        }

        return $this->check(fn () => app(DeactivateUser::class)->ensureAllowed($user));
    }

    /**
     * Determine whether the actor can reactivate the user.
     */
    public function reactivate(User $actor, User $user): Response
    {
        return $this->check(fn () => app(ReactivateUser::class)->ensureAllowed($user));
    }

    /**
     * Convert an action's safeguard check into a policy response.
     */
    private function check(callable $ensureAllowed): Response
    {
        try {
            $ensureAllowed();
        } catch (AdminOperationException $e) {
            return Response::deny($e->getMessage());
        }

        return Response::allow();
    }
}
