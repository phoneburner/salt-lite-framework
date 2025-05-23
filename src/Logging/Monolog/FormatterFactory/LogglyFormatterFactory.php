<?php

declare(strict_types=1);

namespace PhoneBurner\SaltLite\Framework\Logging\Monolog\FormatterFactory;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LogglyFormatter;
use PhoneBurner\SaltLite\Framework\Logging\Config\LoggingHandlerConfigStruct;
use PhoneBurner\SaltLite\Framework\Logging\Monolog\MonologFormatterFactory;

class LogglyFormatterFactory implements MonologFormatterFactory
{
    public function make(LoggingHandlerConfigStruct $config): FormatterInterface
    {
        return new LogglyFormatter(
            $config->formatter_options['batch_mode'] ?? JsonFormatter::BATCH_MODE_NEWLINES,
            $config->formatter_options['append_new_line'] ?? false,
        );
    }
}
