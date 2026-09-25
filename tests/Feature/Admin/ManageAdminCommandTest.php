<?php

use App\Models\User;

test('the command grants admin access', function () {
    $user = User::factory()->create(['email' => 'someone@example.com']);

    $this->artisan('greenfield:admin', ['email' => 'Someone@Example.com'])
        ->expectsOutputToContain('Granted admin access')
        ->assertSuccessful();

    expect($user->refresh()->is_admin)->toBeTrue();
});

test('the command revokes admin access', function () {
    admin();
    $user = User::factory()->admin()->create(['email' => 'someone@example.com']);

    $this->artisan('greenfield:admin', ['email' => 'someone@example.com', '--revoke' => true])
        ->expectsOutputToContain('Revoked admin access')
        ->assertSuccessful();

    expect($user->refresh()->is_admin)->toBeFalse();
});

test('the command will not revoke the last active admin', function () {
    $user = User::factory()->admin()->create(['email' => 'only@example.com']);

    $this->artisan('greenfield:admin', ['email' => 'only@example.com', '--revoke' => true])
        ->expectsOutputToContain('only active admin')
        ->assertFailed();

    expect($user->refresh()->is_admin)->toBeTrue();
});

test('the command will not revoke users listed in ADMIN_EMAILS', function () {
    config(['admin.emails' => ['boss@example.com']]);
    admin();
    $boss = User::factory()->admin()->create(['email' => 'boss@example.com']);

    $this->artisan('greenfield:admin', ['email' => 'boss@example.com', '--revoke' => true])
        ->assertFailed();

    expect($boss->refresh()->is_admin)->toBeTrue();
});

test('the command fails for unknown users', function () {
    $this->artisan('greenfield:admin', ['email' => 'nobody@example.com'])
        ->expectsOutputToContain('must sign in at least once')
        ->assertFailed();
});
