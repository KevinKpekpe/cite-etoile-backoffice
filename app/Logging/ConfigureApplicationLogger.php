<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;

class ConfigureApplicationLogger
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(new RedactSensitiveContext);

        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new JsonFormatter(includeStacktraces: true));
            }
        }
    }
}
