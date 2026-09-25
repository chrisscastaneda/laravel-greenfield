<?php

namespace Tests\Support;

use PHPUnit\Framework\Assert;
use WorkOS\RequestClient\RequestClientInterface;

/**
 * Stands in for the WorkOS SDK's HTTP client so tests never reach the network.
 */
class FakeWorkOSRequestClient implements RequestClientInterface
{
    /**
     * @var list<array{method: string, url: string, params: array<string, mixed>|null}>
     */
    public array $requests = [];

    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(private array $response = [], private int $status = 200) {}

    /**
     * Build the response WorkOS returns when an AuthKit code is exchanged.
     *
     * @param  array<string, mixed>  $user
     */
    public static function authenticatesAs(array $user): self
    {
        return new self([
            'user' => [
                'object' => 'user',
                'id' => 'user_01TESTUSER',
                'email' => 'taylor@example.com',
                'first_name' => 'Taylor',
                'last_name' => 'Otwell',
                'email_verified' => true,
                'profile_picture_url' => 'https://example.com/avatar.png',
                'created_at' => '2026-01-01T00:00:00.000Z',
                'updated_at' => '2026-01-01T00:00:00.000Z',
                ...$user,
            ],
            'organization_id' => null,
            'access_token' => 'fake-access-token',
            'refresh_token' => 'fake-refresh-token',
        ]);
    }

    /**
     * @return array{0: string, 1: array<string, string>, 2: int}
     */
    public function request($method, $url, ?array $headers = null, ?array $params = null)
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'params' => $params];

        return [json_encode($this->response, JSON_THROW_ON_ERROR), [], $this->status];
    }

    public function assertCodeExchanged(string $code): void
    {
        Assert::assertTrue(
            collect($this->requests)->contains(fn (array $request) => str_ends_with($request['url'], 'user_management/authenticate')
                && ($request['params']['code'] ?? null) === $code),
            "The WorkOS authorization code [{$code}] was not exchanged.",
        );
    }
}
