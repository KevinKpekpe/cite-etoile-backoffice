<?php

use Illuminate\Foundation\Application;
use Tests\TestCase;

test('test bootstrap rejects a database outside the dedicated test database', function () {
    config()->set('database.connections.mysql.database', 'cite_etoile_du_monde');

    $testCase = new class('runTest') extends TestCase
    {
        public function validateTestDatabase(Application $app): void
        {
            $this->ensureSafeTestDatabase($app);
        }
    };

    expect(fn () => $testCase->validateTestDatabase($this->app))
        ->toThrow(RuntimeException::class, 'Tests refused');
});
