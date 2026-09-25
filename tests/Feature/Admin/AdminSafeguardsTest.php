<?php

use App\Actions\Admin\DeactivateUser;
use App\Actions\Admin\GrantAdmin;
use App\Actions\Admin\ReactivateUser;
use App\Actions\Admin\RevokeAdmin;
use App\Exceptions\AdminOperationException;
use App\Models\User;

test('revoking the last active admin is refused', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->deactivated()->create();

    app(RevokeAdmin::class)->handle($admin);
})->throws(AdminOperationException::class, 'only active admin');

test('deactivating the last active admin is refused', function () {
    $admin = User::factory()->admin()->create();

    app(DeactivateUser::class)->handle($admin);
})->throws(AdminOperationException::class, 'only active admin');

test('environment admins are protected', function (string $action) {
    config(['admin.emails' => ['boss@example.com']]);
    User::factory()->admin()->create();
    $boss = User::factory()->admin()->create(['email' => 'BOSS@example.com']);

    app($action)->handle($boss);
})->with([RevokeAdmin::class, DeactivateUser::class])
    ->throws(AdminOperationException::class, 'ADMIN_EMAILS');

test('actions refuse no-op changes', function (string $action, Closure $user, string $message) {
    app($action)->handle($user());
})->with([
    [GrantAdmin::class, fn () => User::factory()->admin()->create(), 'already an admin'],
    [RevokeAdmin::class, fn () => User::factory()->create(), 'not an admin'],
    [DeactivateUser::class, fn () => User::factory()->deactivated()->create(), 'already deactivated'],
    [ReactivateUser::class, fn () => User::factory()->create(), 'not deactivated'],
])->throws(AdminOperationException::class);
