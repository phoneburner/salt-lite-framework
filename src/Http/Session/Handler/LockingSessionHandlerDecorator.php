<?php

declare(strict_types=1);

namespace PhoneBurner\SaltLite\Framework\Http\Session\Handler;

use PhoneBurner\SaltLite\Cache\CacheKey;
use PhoneBurner\SaltLite\Cache\Lock\Lock;
use PhoneBurner\SaltLite\Cache\Lock\LockFactory;
use PhoneBurner\SaltLite\Framework\Http\Session\SessionHandler;
use PhoneBurner\SaltLite\Framework\Http\Session\SessionManager;
use PhoneBurner\SaltLite\Http\Response\Exceptional\ServerErrorResponse;
use PhoneBurner\SaltLite\Http\Session\SessionId;
use PhoneBurner\SaltLite\Time\Ttl;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Sessions lock for 10 seconds, and we will wait for up to 30 seconds to acquire
 * a lock on the session. Only existing sessions will be locked, since it does not
 * make sense to lock a session that no one knows how to access yet.
 */
final class LockingSessionHandlerDecorator extends SessionHandlerDecorator
{
    private Lock|null $lock = null;

    public function __construct(
        protected SessionHandler $handler,
        private readonly LockFactory $lock_factory,
    ) {
    }

    #[\Override]
    public function open(
        string $path = '',
        string $name = SessionManager::SESSION_ID_COOKIE_NAME,
        SessionId|null $id = null,
        ServerRequestInterface|null $request = null,
    ): bool {
        if ($id !== null) {
            $this->lock = $this->lock_factory->make(CacheKey::make('session', $id), Ttl::seconds(10));
            $this->lock->acquire(true, 30) || throw new ServerErrorResponse(detail: 'Could not acquire session lock');
        }

        return $this->handler->open($path, $name);
    }

    #[\Override]
    public function close(): bool
    {
        try {
            return $this->handler->close();
        } finally {
            $this->lock?->release();
        }
    }
}
