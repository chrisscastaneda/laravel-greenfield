<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use WorkOS\Client as WorkOSClient;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests don't need compiled frontend assets.
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        // Restore the real WorkOS HTTP client after tests that faked it.
        WorkOSClient::setRequestClient(null);

        parent::tearDown();
    }
}
