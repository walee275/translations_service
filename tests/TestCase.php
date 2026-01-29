<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (app()->environment('testing') === false) {
            throw new \RuntimeException('Tests are NOT running in testing environment!');
        }
    }

    public function test_env_sanity_check()
    {
        dump([
            'env' => app()->environment(),
            'connection' => config('database.default'),
            'database' => config('database.connections.sqlite.database'),
        ]);

        $this->assertTrue(true);
    }
}
