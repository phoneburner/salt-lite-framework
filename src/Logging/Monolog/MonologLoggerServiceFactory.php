<?php

declare(strict_types=1);

namespace PhoneBurner\SaltLite\Framework\Logging\Monolog;

use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\LogglyFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\LogglyHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PhoneBurner\SaltLite\App\App;
use PhoneBurner\SaltLite\Framework\Logging\Config\LoggingConfigStruct;
use PhoneBurner\SaltLite\Framework\Logging\LoggerServiceFactory;
use PhoneBurner\SaltLite\Framework\MessageBus\LongRunningProcessServiceResetter;
use PhoneBurner\SaltLite\Logging\PsrLoggerAdapter;
use PhoneBurner\SaltLite\String\Str;
use PhoneBurner\SaltLite\Type\Type;
use Psr\Log\LoggerInterface;

use function PhoneBurner\SaltLite\Framework\ghost;

class MonologLoggerServiceFactory implements LoggerServiceFactory
{
    public const array DEFAULT_FORMATTERS = [
        LogglyHandler::class => LogglyFormatter::class,
        RotatingFileHandler::class => LineFormatter::class,
        StreamHandler::class => LineFormatter::class,
        SlackWebhookHandler::class => LineFormatter::class,
        ErrorLogHandler::class => LineFormatter::class,
        TestHandler::class => LineFormatter::class,
    ];

    public function __construct(
        private readonly MonologHandlerFactory $handler_factory,
    ) {
    }

    public function __invoke(App $app, string $id): LoggerInterface
    {
        return new PsrLoggerAdapter(ghost(function (Logger $logger) use ($app): void {
            $config = Type::of(LoggingConfigStruct::class, $app->config->get('logging'));

            $logger->__construct(
                $config->channel ?? Str::kabob($app->config->get('app.name')),
                \array_map($this->handler_factory->make(...), \array_values($config->handlers)),
                \array_map($app->services->get(...), $config->processors),
            );

            // Set a custom exception handler to suppress any errors that occur
            // while processing a log entry in production environments.
            $logger->setExceptionHandler($app->get(LoggerExceptionHandler::class)(...));

            // On resolution, replace the resolved logger as the container's
            // logger instance, which should also consume any buffered log
            // entries from the default buffer logger. It's ok that we're setting
            // the inner logger here, because the PsrLoggerAdapter is a wrapper around
            // the Monolog logger, and we don't need to worry about it being
            // replaced again.
            $app->services->setLogger($logger);

            // Register with the long-running process service resetter to make sure
            // that we batch/flush any buffered log entries when the worker stops.
            $app->services->get(LongRunningProcessServiceResetter::class)->add($logger);
        }));
    }
}
