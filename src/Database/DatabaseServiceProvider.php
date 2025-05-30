<?php

declare(strict_types=1);

namespace PhoneBurner\SaltLite\Framework\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\Console\ConnectionProvider as DoctrineConnectionProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\EntityManagerProvider as DoctrineEntityManagerProvider;
use PhoneBurner\SaltLite\App\App;
use PhoneBurner\SaltLite\Attribute\Usage\Internal;
use PhoneBurner\SaltLite\Cache\Psr6\CacheItemPoolFactory as CacheItemPoolFactoryContract;
use PhoneBurner\SaltLite\Container\DeferrableServiceProvider;
use PhoneBurner\SaltLite\Framework\Cache\CacheItemPoolFactory;
use PhoneBurner\SaltLite\Framework\Database\Doctrine\ConnectionFactory;
use PhoneBurner\SaltLite\Framework\Database\Doctrine\ConnectionProvider;
use PhoneBurner\SaltLite\Framework\Database\Doctrine\Orm\EntityManagerFactory;
use PhoneBurner\SaltLite\Framework\Database\Doctrine\Orm\EntityManagerProvider;
use PhoneBurner\SaltLite\Framework\Database\Redis\CachingRedisManager;
use PhoneBurner\SaltLite\Framework\Database\Redis\RedisManager;
use Psr\Log\LoggerInterface;

use function PhoneBurner\SaltLite\Framework\ghost;
use function PhoneBurner\SaltLite\Framework\proxy;

/**
 * @codeCoverageIgnore
 */
#[Internal('Override Definitions in Application Service Providers')]
final class DatabaseServiceProvider implements DeferrableServiceProvider
{
    public static function provides(): array
    {
        return [
            \Redis::class,
            RedisManager::class,
            DoctrineConnectionProvider::class,
            DoctrineEntityManagerProvider::class,
            CachingRedisManager::class,
            ConnectionProvider::class,
            ConnectionFactory::class,
            Connection::class,
            EntityManagerProvider::class,
            EntityManagerFactory::class,
            EntityManagerInterface::class,
        ];
    }

    public static function bind(): array
    {
        return [
            RedisManager::class => CachingRedisManager::class,
            DoctrineConnectionProvider::class => ConnectionProvider::class,
            DoctrineEntityManagerProvider::class => EntityManagerProvider::class,
        ];
    }

    #[\Override]
    public static function register(App $app): void
    {
        $app->set(
            \Redis::class,
            static fn(App $app): \Redis => $app->get(RedisManager::class)->connect(),
        );

        $app->set(
            CachingRedisManager::class,
            ghost(static fn(CachingRedisManager $ghost): null => $ghost->__construct(
                $app->config->get('database.redis'),
            )),
        );

        $app->set(ConnectionProvider::class, new ConnectionProvider($app));

        $app->set(
            ConnectionFactory::class,
            ghost(static fn(ConnectionFactory $ghost): null => $ghost->__construct(
                $app->environment,
                $app->config->get('database.doctrine'),
                $app->get(CacheItemPoolFactoryContract::class),
                $app->get(LoggerInterface::class),
            )),
        );

        $app->set(
            Connection::class,
            proxy(static fn(Connection $proxy): Connection => $app->get(ConnectionFactory::class)->connect()),
        );

        $app->set(EntityManagerProvider::class, new EntityManagerProvider($app));

        $app->set(
            EntityManagerFactory::class,
            ghost(static fn(EntityManagerFactory $ghost): null => $ghost->__construct(
                $app->services,
                $app->environment,
                $app->config->get('database.doctrine'),
                $app->get(DoctrineConnectionProvider::class),
                $app->get(CacheItemPoolFactory::class),
            )),
        );

        /**
         * The EntityManager is a heavy object, so we'll defer its creation until it's needed.
         * Because we can't create a ghost or proxy for an interface, we'll handle that
         * within the EntityManagerFactory itself.
         */
        $app->set(
            EntityManagerInterface::class,
            static fn(App $app): EntityManagerInterface => $app->get(EntityManagerFactory::class)->ghost(),
        );
    }
}
