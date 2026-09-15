<?php

use App\Logging\ConfigureApplicationLogger;
use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

test('application log channels rotate structured logs with a finite retention', function () {
    expect(config('logging.channels.daily'))->toMatchArray([
        'driver' => 'daily',
        'tap' => [ConfigureApplicationLogger::class],
        'max_files' => 30,
        'replace_placeholders' => true,
    ]);
    expect(config('logging.channels.stderr.tap'))->toBe([ConfigureApplicationLogger::class]);
});

test('logger produces JSON and redacts sensitive nested context', function () {
    $handler = new TestHandler;
    $monolog = new Logger('test', [$handler], [new PsrLogMessageProcessor]);
    $logger = new IlluminateLogger($monolog);
    (new ConfigureApplicationLogger)($logger);

    $logger->warning('Payment gateway rejected {authorization}', [
        'customer_id' => 42,
        'authorization' => 'Bearer exposed-token',
        'request' => [
            'password_confirmation' => 'exposed-password',
            'reference' => 'PAY-001',
        ],
    ]);

    $record = $handler->getRecords()[0];

    expect($record->context)->toBe([
        'customer_id' => 42,
        'authorization' => '[REDACTED]',
        'request' => [
            'password_confirmation' => '[REDACTED]',
            'reference' => 'PAY-001',
        ],
    ]);
    expect($record->message)->toBe('Payment gateway rejected [REDACTED]');
    expect($handler->getFormatter())->toBeInstanceOf(JsonFormatter::class);
    expect($handler->getFormatter()->format($record))->json()->toMatchArray([
        'message' => 'Payment gateway rejected [REDACTED]',
        'level_name' => 'WARNING',
        'context' => $record->context,
    ]);
});
