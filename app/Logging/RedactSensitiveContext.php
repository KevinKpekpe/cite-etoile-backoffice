<?php

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class RedactSensitiveContext implements ProcessorInterface
{
    private const REDACTED = '[REDACTED]';

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(context: $this->redact($record->context));
    }

    /**
     * @param  array<array-key, mixed>  $context
     * @return array<array-key, mixed>
     */
    private function redact(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $context[$key] = self::REDACTED;

                continue;
            }

            if (is_array($value)) {
                $context[$key] = $this->redact($value);
            }
        }

        return $context;
    }

    private function isSensitiveKey(string $key): bool
    {
        return preg_match(
            '/(?:password|passwd|secret|token|authorization|cookie|api[_-]?key|private[_-]?key|credit[_-]?card|card[_-]?number|cvv|pin)/i',
            $key,
        ) === 1;
    }
}
