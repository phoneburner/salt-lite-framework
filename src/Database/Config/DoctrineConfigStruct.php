<?php

declare(strict_types=1);

namespace PhoneBurner\SaltLite\Framework\Database\Config;

use Doctrine\DBAL\Types\Type;
use PhoneBurner\SaltLite\Configuration\ConfigStruct;
use PhoneBurner\SaltLite\Configuration\Struct\ConfigStructArrayAccess;
use PhoneBurner\SaltLite\Configuration\Struct\ConfigStructSerialization;

final readonly class DoctrineConfigStruct implements ConfigStruct
{
    use ConfigStructArrayAccess;
    use ConfigStructSerialization;

    /**
     * @param array<string, DoctrineConnectionConfigStruct> $connections
     * @param array<string, class-string<Type>> $types
     * @link https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/cookbook/custom-mapping-types.html
     */
    public function __construct(
        public array $connections = [],
        public array $types = [],
        public string $default_connection = 'default',
    ) {
        \assert($default_connection !== '');
        \assert($connections === [] || \array_key_exists($default_connection, $connections));
    }
}
