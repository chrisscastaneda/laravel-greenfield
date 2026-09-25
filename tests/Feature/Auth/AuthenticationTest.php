<?php

use App\Models\User;
use Tests\Support\FakeWorkOSRequestClient;

describe('login', function () {
    test('guests are redirected to WorkOS AuthKit', function () {
        fakeWorkOS();

        $response = $this->get(route('login'));

        $response->assertRedirect();
        expect($response->headers->get('Location'))
            ->toStartWith('https://api.workos.com/user_management/authorize')
            ->toContain('client_id=client_test')
            ->toContain('provider=authkit')
            ->toContain(urlencode('https://localhost/authenticate'));

        $response->assertSessionHas('state');
    });

    test('authenticated users cannot visit the login route', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    });

    test('login fails loudly when WorkOS is not configured', function () {
        config(['services.workos.client_id' => null]);

        $this->withoutExceptionHandling();

        $this->get(route('login'));
    })->throws(RuntimeException::class, 'services.workos.client_id');
});

describe('WorkOS callback', function () {
    test('a first-time user is created and signed in', function () {
        $workos = fakeWorkOS(FakeWorkOSRequestClient::authenticatesAs([
            'id' => 'user_01NEW',
            'email' => 'new@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
        ]));

        authKitCallback('code-123')->assertRedirect(route('dashboard'));

        $workos->assertCodeExchanged('code-123');

        $user = User::query()->where('workos_id', 'user_01NEW')->sole();

        $this->assertAuthenticatedAs($user);
        expect($user)
            ->name->toBe('New User')
            ->email->toBe('new@example.com')
            ->avatar->toBe('https://example.com/avatar.png')
            ->email_verified_at->not->toBeNull()
            ->last_login_at->not->toBeNull()
            ->is_admin->toBeFalse();

        expect(session('workos_access_token'))->toBe('fake-access-token');
        expect(session('workos_refresh_token'))->toBe('fake-refresh-token');
    });

    test('a user without a name falls back to their email address', function () {
        fakeWorkOS(FakeWorkOSRequestClient::authenticatesAs([
            'email' => 'nameless@example.com',
            'first_name' => null,
            'last_name' => null,
        ]));

        authKitCallback();

        expect(User::sole()->name)->toBe('nameless@example.com');
    });

    test('a returning user is signed in without creating a duplicate', function () {
        $user = User::factory()->create(['workos_id' => 'user_01EXISTING', 'avatar' => '']);

        fakeWorkOS(FakeWorkOSRequestClient::authenticatesAs(['id' => 'user_01EXISTING']));

        authKitCallback()->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        expect(User::count())->toBe(1);
        expect($user->refresh())
            ->avatar->toBe('https://example.com/avatar.png')
            ->last_login_at->not->toBeNull();
    });

    test('users are redirected to the page they originally requested', function () {
        User::factory()->create(['workos_id' => 'user_01EXISTING']);
        fakeWorkOS(FakeWorkOSRequestClient::authenticatesAs(['id' => 'user_01EXISTING']));

        $this->get(route('profile.edit'))->assertRedirect(route('login'));

        authKitCallback()->assertRedirect(route('profile.edit'));
    });

    test('an invalid state parameter is rejected', function () {
        $workos = fakeWorkOS();

        $this->withSession(['state' => json_encode(['state' => 'expected'])])
            ->get('/authenticate?'.http_build_query([
                'code' => 'code',
                'state' => json_encode(['state' => 'forged']),
            ]))
            ->assertForbidden();

        $this->assertGuest();
        expect($workos->requests)->toBeEmpty();
    });

    test('a failed code exchange does not sign anyone in', function () {
        fakeWorkOS(new FakeWorkOSRequestClient(['error' => 'invalid_grant'], 400));

        authKitCallback()->assertServerError();

        $this->assertGuest();
        expect(User::count())->toBe(0);
    });

    test('deactivated users cannot sign in', function () {
        User::factory()->deactivated()->create(['workos_id' => 'user_01DEACTIVATED']);
        fakeWorkOS(FakeWorkOSRequestClient::authenticatesAs(['id' => 'user_01DEACTIVATED']));

        authKitCallback()->assertRedirect(route('account.deactivated'));

        $this->assertGuest();
        expect(session('workos_access_token'))->toBeNull();
    });
});

describe('sessions', function () {
    test('guests are redirected to login from protected pages', function (string $route) {
        $this->get(route($route))->assertRedirect(route('login'));
    })->with(['dashboard', 'profile.edit', 'admin.users.index']);

    test('authenticated users can visit the dashboard', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk();
    });

    test('a deactivated user is signed out on their next request', function () {
        $user = User::factory()->deactivated()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('account.deactivated'));

        $this->assertGuest();
    });

    test('users can log out', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    });

    test('the account deactivated page is displayed to guests', function () {
        $this->get(route('account.deactivated'))->assertOk();
    });
});
