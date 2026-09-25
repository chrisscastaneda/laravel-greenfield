<?php

use App\Models\User;
use Tests\Support\FakeWorkOSRequestClient;

beforeEach(function () {
    config(['admin.emails' => ['boss@example.com']]);
});

test('a new user listed in ADMIN_EMAILS is created as an admin', function () {
    fakeWorkOS(FakeWorkOSRequestClient::authenticatesAs(['email' => 'Boss@Example.com']));

    authKitCallback();

    expect(User::sole()->is_admin)->toBeTrue();
});

test('an existing user listed in ADMIN_EMAILS is promoted when they sign in', function () {
    $user = User::factory()->create(['workos_id' => 'user_01BOSS', 'email' => 'boss@example.com']);
    fakeWorkOS(FakeWorkOSRequestClient::authenticatesAs(['id' => 'user_01BOSS']));

    authKitCallback();

    expect($user->refresh()->is_admin)->toBeTrue();
});

test('users not listed in ADMIN_EMAILS are not promoted', function () {
    fakeWorkOS(FakeWorkOSRequestClient::authenticatesAs(['email' => 'someone@example.com']));

    authKitCallback();

    expect(User::sole()->is_admin)->toBeFalse();
});

test('removing an email from ADMIN_EMAILS does not revoke admin access', function () {
    config(['admin.emails' => []]);
    $user = User::factory()->admin()->create(['workos_id' => 'user_01BOSS', 'email' => 'boss@example.com']);
    fakeWorkOS(FakeWorkOSRequestClient::authenticatesAs(['id' => 'user_01BOSS']));

    authKitCallback();

    expect($user->refresh()->is_admin)->toBeTrue();
});
