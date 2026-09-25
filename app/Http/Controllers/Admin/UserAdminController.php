<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\GrantAdmin;
use App\Actions\Admin\RevokeAdmin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserAdminController extends Controller
{
    /**
     * Grant admin access to the user.
     */
    public function store(User $user, GrantAdmin $grantAdmin): RedirectResponse
    {
        Gate::authorize('grantAdmin', $user);

        $grantAdmin->handle($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name is now an admin.', ['name' => $user->name])]);

        return to_route('admin.users.show', $user);
    }

    /**
     * Revoke the user's admin access.
     */
    public function destroy(User $user, RevokeAdmin $revokeAdmin): RedirectResponse
    {
        Gate::authorize('revokeAdmin', $user);

        $revokeAdmin->handle($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name is no longer an admin.', ['name' => $user->name])]);

        return to_route('admin.users.show', $user);
    }
}
