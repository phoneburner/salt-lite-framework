<?php

declare(strict_types=1);

namespace PhoneBurner\SaltLite\Framework\Storage;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use PhoneBurner\SaltLite\App\App;
use PhoneBurner\SaltLite\Attribute\Usage\Internal;
use PhoneBurner\SaltLite\Container\DeferrableServiceProvider;
use PhoneBurner\SaltLite\Container\ServiceFactory\NewInstanceServiceFactory;
use PhoneBurner\SaltLite\Framework\Storage\Config\StorageConfigStruct;
use PhoneBurner\SaltLite\Framework\Storage\FilesystemAdapterFactory\ContainerAdapterFactory;
use PhoneBurner\SaltLite\Framework\Storage\FilesystemAdapterFactory\LocalFilesystemAdapterFactory;
use PhoneBurner\SaltLite\Framework\Storage\FilesystemAdapterFactory\S3FilesystemAdapterFactory;

/**
 * @codeCoverageIgnore
 */
#[Internal('Override Definitions in Application Service Providers')]
final class StorageServiceProvider implements DeferrableServiceProvider
{
    public static function provides(): array
    {
        return [
            FilesystemReader::class,
            FilesystemWriter::class,
            FilesystemOperator::class,
            FilesystemOperatorFactory::class,
            FilesystemAdapterFactory::class,
            ContainerAdapterFactory::class,
            LocalFilesystemAdapterFactory::class,
            S3FilesystemAdapterFactory::class,
        ];
    }

    public static function bind(): array
    {
        return [
            FilesystemReader::class => FilesystemOperator::class,
            FilesystemWriter::class => FilesystemOperator::class,
            FilesystemAdapterFactory::class => ContainerAdapterFactory::class,
        ];
    }

    #[\Override]
    public static function register(App $app): void
    {
        $app->set(
            FilesystemOperator::class,
            static fn(App $app): FilesystemOperator => $app->get(FilesystemOperatorFactory::class)->default(),
        );

        $app->set(
            FilesystemOperatorFactory::class,
            static fn(App $app): FilesystemOperatorFactory => new FilesystemOperatorFactory(
                $app->get(StorageConfigStruct::class),
                $app->get(FilesystemAdapterFactory::class),
            ),
        );

        $app->set(
            ContainerAdapterFactory::class,
            static fn(App $app): ContainerAdapterFactory => new ContainerAdapterFactory(
                $app->services,
                $app->get(StorageConfigStruct::class)->factories,
            ),
        );

        $app->set(LocalFilesystemAdapterFactory::class, NewInstanceServiceFactory::singleton());

        $app->set(S3FilesystemAdapterFactory::class, NewInstanceServiceFactory::singleton());
    }
}
