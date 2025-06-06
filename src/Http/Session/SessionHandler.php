<?php

declare(strict_types=1);

namespace PhoneBurner\SaltLite\Framework\Http\Session;

use PhoneBurner\SaltLite\Attribute\Usage\Contract;
use PhoneBurner\SaltLite\Http\Session\SessionHandler as SessionHandlerContract;
use PhoneBurner\SaltLite\Http\Session\SessionId;
use Psr\Http\Message\ServerRequestInterface;

#[Contract]
abstract class SessionHandler implements SessionHandlerContract
{
    /**
     * Called before reading from the session
     * The method signature is a bit unusual, and probably violates the open/closed
     * principle in order to maintain compatibility the PHP session handler interface.
     */
    public function open(
        string $path = '',
        string $name = SessionManager::SESSION_ID_COOKIE_NAME,
        SessionId|null $id = null,
        ServerRequestInterface|null $request = null,
    ): bool {
        return true;
    }

    /**
     * Called after writing to the session
     */
    public function close(): bool
    {
        return true;
    }

    abstract public function read(SessionId|string $id): string;

    abstract public function write(SessionId|string $id, string $data): bool;

    abstract public function destroy(SessionId|string $id): bool;

    /**
     * Default the cleanup behavior to the underlying service managing sessions
     */
    public function gc(int $max_lifetime): int
    {
        return 0;
    }
}
