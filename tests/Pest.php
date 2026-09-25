<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\FakeWorkOSRequestClient;
use Tests\TestCase;
use WorkOS\Client as WorkOSClient;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Configure fake WorkOS credentials and a fake SDK HTTP client.
 */
function fakeWorkOS(?FakeWorkOSRequestClient $client = null): FakeWorkOSRequestClient
{
    config([
        'services.workos.client_id' => 'client_test',
        'services.workos.secret' => 'sk_test',
        'services.workos.redirect_url' => 'https://localhost/authenticate',
    ]);

    $client ??= new FakeWorkOSRequestClient;

    WorkOSClient::setRequestClient($client);

    return $client;
}

/**
 * Simulate AuthKit redirecting back to the application with a valid state.
 */
function authKitCallback(string $code = 'auth-code'): TestResponse
{
    $state = json_encode(['state' => 'test-state', 'previous_url' => base64_encode('/')]);

    return test()
        ->withSession(['state' => $state])
        ->get('/authenticate?'.http_build_query(['code' => $code, 'state' => $state]));
}

function admin(): User
{
    return User::factory()->admin()->create();
}
