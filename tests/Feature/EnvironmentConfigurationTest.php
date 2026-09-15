<?php

use Dotenv\Dotenv;

test('production template disables debug and protects session cookies', function () {
    $environment = Dotenv::parse(file_get_contents(base_path('.env.production.example')));

    expect($environment)->toMatchArray([
        'APP_ENV' => 'production',
        'APP_DEBUG' => 'false',
        'SESSION_SECURE_COOKIE' => 'true',
        'SESSION_HTTP_ONLY' => 'true',
        'SESSION_SAME_SITE' => 'lax',
        'SESSION_ENCRYPT' => 'true',
        'MAIL_MAILER' => 'array',
    ]);
    expect($environment['APP_URL'])->toStartWith('https://');
});

test('testing template isolates database and external side effects', function () {
    $environment = Dotenv::parse(file_get_contents(base_path('.env.testing.example')));

    expect($environment)->toMatchArray([
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => 'cite_etoile_du_monde_testing',
        'DB_URL' => '',
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'MAIL_MAILER' => 'array',
        'BROADCAST_CONNECTION' => 'null',
    ]);
});

test('environment templates contain no populated credentials', function (string $filename) {
    $environment = Dotenv::parse(file_get_contents(base_path($filename)));

    foreach ($environment as $key => $value) {
        if (preg_match('/(?:PASSWORD|SECRET|TOKEN|KEY)$/', $key) || $key === 'AWS_ACCESS_KEY_ID') {
            expect($value)->toBeIn(['', 'null']);
        }
    }
})->with(['.env.example', '.env.testing.example', '.env.production.example']);
