<?php

declare(strict_types=1);

namespace PhoneBurner\SaltLite\Framework\Logging\Monolog\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use PhoneBurner\SaltLite\App\Environment;

class EnvironmentProcessor implements ProcessorInterface
{
    public function __construct(private readonly Environment $environment)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['context'] ??= $this->environment->context->name;
        $record->extra['build_stage'] ??= $this->environment->stage->value;

        return $record;
    }
}
