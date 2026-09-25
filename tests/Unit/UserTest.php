<?php

use App\Models\User;

test('admin emails are parsed from a comma-separated list', function () {
    $original = [$_SERVER['ADMIN_EMAILS'] ?? null, $_ENV['ADMIN_EMAILS'] ?? null];
    $_SERVER['ADMIN_EMAILS'] = $_ENV['ADMIN_EMAILS'] = ' Boss@Example.com, ,second@example.com ';

    try {
        $config = require config_path('admin.php');
    } finally {
        [$_SERVER['ADMIN_EMAILS'], $_ENV['ADMIN_EMAILS']] = $original;
    }

    expect($config['emails'])->toBe(['boss@example.com', 'second@example.com']);
});

test('environment admins are matched case-insensitively', function () {
    config(['admin.emails' => ['boss@example.com']]);

    expect(new User(['email' => 'Boss@Example.COM']))->isEnvironmentAdmin()->toBeTrue()
        ->and(new User(['email' => 'someone@example.com']))->isEnvironmentAdmin()->toBeFalse();
});

test('new users are not admins or deactivated', function () {
    $user = new User(['email' => 'someone@example.com']);

    expect($user->isAdmin())->toBeFalse()
        ->and($user->isDeactivated())->toBeFalse();
});

test('admin and deactivation flags cannot be mass assigned', function () {
    $user = new User([
        'email' => 'someone@example.com',
        'is_admin' => true,
        'deactivated_at' => now(),
    ]);

    expect($user->isAdmin())->toBeFalse()
        ->and($user->isDeactivated())->toBeFalse();
});
