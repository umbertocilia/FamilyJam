<?php

declare(strict_types=1);

namespace App\Services\Auth;

use CodeIgniter\Cache\CacheInterface;
use Throwable;

final class LoginThrottleService
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_SECONDS = 900;

    public function __construct(private readonly ?CacheInterface $cache = null)
    {
    }

    public function blocked(string $email, string $ipAddress): bool
    {
        try {
            return $this->attempts($email, $ipAddress) >= self::MAX_ATTEMPTS;
        } catch (Throwable $exception) {
            log_message('error', '[FamilyJam login throttle] blocked() failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function increment(string $email, string $ipAddress): void
    {
        try {
            $cache = $this->cache ?? cache();
            $key = $this->key($email, $ipAddress);
            $attempts = (int) ($cache->get($key) ?? 0);
            $cache->save($key, $attempts + 1, self::WINDOW_SECONDS);
        } catch (Throwable $exception) {
            log_message('error', '[FamilyJam login throttle] increment() failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function clear(string $email, string $ipAddress): void
    {
        try {
            ($this->cache ?? cache())->delete($this->key($email, $ipAddress));
        } catch (Throwable $exception) {
            log_message('error', '[FamilyJam login throttle] clear() failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function attempts(string $email, string $ipAddress): int
    {
        try {
            return (int) (($this->cache ?? cache())->get($this->key($email, $ipAddress)) ?? 0);
        } catch (Throwable $exception) {
            log_message('error', '[FamilyJam login throttle] attempts() failed: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return 0;
        }
    }

    private function key(string $email, string $ipAddress): string
    {
        return 'auth-login:' . sha1(strtolower(trim($email)) . '|' . $ipAddress);
    }
}
