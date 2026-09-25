<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeactivateUser;
use App\Actions\Admin\ReactivateUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserDeactivationController extends Controller
{
    /**
     * Deactivate the user.
     */
    public function store(User $user, DeactivateUser $deactivateUser): RedirectResponse
    {
        Gate::authorize('deactivate', $user);

        $deactivateUser->handle($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name has been deactivated.', ['name' => $user->name])]);

        return to_route('admin.users.show', $user);
    }

    /**
     * Reactivate the user.
     */
    public function destroy(User $user, ReactivateUser $reactivateUser): RedirectResponse
    {
        Gate::authorize('reactivate', $user);

        $reactivateUser->handle($user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name has been reactivated.', ['name' => $user->name])]);

        return to_route('admin.users.show', $user);
    }
}
