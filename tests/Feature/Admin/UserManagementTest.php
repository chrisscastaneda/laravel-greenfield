<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

describe('access', function () {
    test('non-admins cannot access the admin area', function (string $method, string $route) {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($user)
            ->{$method}(route($route, $target))
            ->assertForbidden();
    })->with([
        ['get', 'admin.users.index'],
        ['get', 'admin.users.show'],
        ['post', 'admin.users.admin.store'],
        ['delete', 'admin.users.admin.destroy'],
        ['post', 'admin.users.deactivation.store'],
        ['delete', 'admin.users.deactivation.destroy'],
    ]);

    test('admins can list users', function () {
        $admin = admin();
        User::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Index')
                ->has('users.data', 4)
                ->missing('users.data.0.workos_id'));
    });

    test('admins can search users by name or email', function () {
        $admin = admin();
        User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
        User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@navy.example']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'ADA']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.email', 'ada@example.com')
                ->where('filters.search', 'ADA'));

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'navy']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Grace Hopper'));
    });

    test('admins can view a user with the actions available to them', function () {
        $admin = admin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Show')
                ->where('user.id', $user->id)
                ->where('can.grantAdmin.allowed', true)
                ->where('can.revokeAdmin.allowed', false)
                ->where('can.deactivate.allowed', true)
                ->where('can.reactivate.allowed', false));
    });

    test('deactivated admins cannot access the admin area', function () {
        $this->actingAs(User::factory()->admin()->deactivated()->create())
            ->get(route('admin.users.index'))
            ->assertRedirect(route('account.deactivated'));
    });
});

describe('admin access', function () {
    test('admins can grant admin access', function () {
        $user = User::factory()->create();

        $this->actingAs(admin())
            ->post(route('admin.users.admin.store', $user))
            ->assertRedirect(route('admin.users.show', $user));

        expect($user->refresh()->is_admin)->toBeTrue();
    });

    test('admins can revoke admin access from another admin', function () {
        $other = admin();

        $this->actingAs(admin())
            ->delete(route('admin.users.admin.destroy', $other))
            ->assertRedirect(route('admin.users.show', $other));

        expect($other->refresh()->is_admin)->toBeFalse();
    });

    test('admins cannot revoke their own admin access', function () {
        $admin = admin();
        admin();

        $this->actingAs($admin)
            ->delete(route('admin.users.admin.destroy', $admin))
            ->assertForbidden();

        expect($admin->refresh()->is_admin)->toBeTrue();
    });

    test('admin access cannot be revoked from users listed in ADMIN_EMAILS', function () {
        config(['admin.emails' => ['boss@example.com']]);
        $boss = User::factory()->admin()->create(['email' => 'boss@example.com']);

        $this->actingAs(admin())
            ->delete(route('admin.users.admin.destroy', $boss))
            ->assertForbidden();

        expect($boss->refresh()->is_admin)->toBeTrue();
    });

    test('deactivated users cannot be granted admin access', function () {
        $user = User::factory()->deactivated()->create();

        $this->actingAs(admin())
            ->post(route('admin.users.admin.store', $user))
            ->assertForbidden();

        expect($user->refresh()->is_admin)->toBeFalse();
    });
});

describe('deactivation', function () {
    test('admins can deactivate a user', function () {
        $user = User::factory()->create();

        $this->actingAs(admin())
            ->post(route('admin.users.deactivation.store', $user))
            ->assertRedirect(route('admin.users.show', $user));

        expect($user->refresh()->isDeactivated())->toBeTrue();
    });

    test('admins can reactivate a user', function () {
        $user = User::factory()->deactivated()->create();

        $this->actingAs(admin())
            ->delete(route('admin.users.deactivation.destroy', $user))
            ->assertRedirect(route('admin.users.show', $user));

        expect($user->refresh()->isDeactivated())->toBeFalse();
    });

    test('admins cannot deactivate themselves', function () {
        $admin = admin();
        admin();

        $this->actingAs($admin)
            ->post(route('admin.users.deactivation.store', $admin))
            ->assertForbidden();

        expect($admin->refresh()->isDeactivated())->toBeFalse();
    });

    test('users listed in ADMIN_EMAILS cannot be deactivated', function () {
        config(['admin.emails' => ['boss@example.com']]);
        $boss = User::factory()->admin()->create(['email' => 'boss@example.com']);

        $this->actingAs(admin())
            ->post(route('admin.users.deactivation.store', $boss))
            ->assertForbidden();

        expect($boss->refresh()->isDeactivated())->toBeFalse();
    });

    test('other admins can be deactivated while another admin remains', function () {
        $other = admin();

        $this->actingAs(admin())
            ->post(route('admin.users.deactivation.store', $other))
            ->assertRedirect();

        expect($other->refresh()->isDeactivated())->toBeTrue();
    });
});
