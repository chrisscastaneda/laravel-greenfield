<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List users, optionally filtered by a name or email search.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.mb_strtolower($search).'%';

                $query->where(fn ($query) => $query
                    ->whereRaw('lower(name) like ?', [$term])
                    ->orWhereRaw('lower(email) like ?', [$term]));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => $this->summary($user));

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Show a single user and the admin actions available for them.
     */
    public function show(Request $request, User $user): Response
    {
        Gate::authorize('view', $user);

        $actor = $request->user();

        return Inertia::render('admin/users/Show', [
            'user' => [
                ...$this->summary($user),
                'is_environment_admin' => $user->isEnvironmentAdmin(),
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            ],
            'can' => collect(['grantAdmin', 'revokeAdmin', 'deactivate', 'reactivate'])
                ->mapWithKeys(function (string $ability) use ($actor, $user) {
                    $response = Gate::forUser($actor)->inspect($ability, $user);

                    return [$ability => [
                        'allowed' => $response->allowed(),
                        'reason' => $response->message(),
                    ]];
                }),
        ]);
    }

    /**
     * The user attributes shown in the admin area.
     *
     * @return array<string, mixed>
     */
    private function summary(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'is_admin' => $user->isAdmin(),
            'deactivated_at' => $user->deactivated_at?->toIso8601String(),
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
