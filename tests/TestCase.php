<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $this->ensureSafeTestDatabase($app);

        return $app;
    }

    protected function ensureSafeTestDatabase(Application $app): void
    {
        $configuration = $app->make('config');

        if ($app->configurationIsCached()
            || ! $app->environment('testing')
            || $configuration->get('database.default') !== 'mysql'
            || $configuration->get('database.connections.mysql.database') !== 'cite_etoile_du_monde_testing'
            || $configuration->get('database.connections.mysql.username') !== 'cite_etoile_testing'
            || $configuration->get('database.connections.mysql.url')) {
            throw new RuntimeException('Tests refused: use an uncached testing environment and the dedicated MySQL database and user.');
        }
    }
}
