<?php

class LoginAttemptLimiter {
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 15;
    private const SESSION_KEY = 'login_attempts';

    private static function normalizeEmail(string $email): string {
        return strtolower(trim($email));
    }

    private static function getBucket(string $email): array {
        SessionHelper::start();
        $key = self::normalizeEmail($email);
        if ($key === '') {
            return ['count' => 0, 'locked_until' => 0];
        }

        if (!isset($_SESSION[self::SESSION_KEY][$key])) {
            $_SESSION[self::SESSION_KEY][$key] = ['count' => 0, 'locked_until' => 0];
        }

        return $_SESSION[self::SESSION_KEY][$key];
    }

    private static function saveBucket(string $email, array $bucket): void {
        $key = self::normalizeEmail($email);
        if ($key === '') {
            return;
        }

        SessionHelper::start();
        $_SESSION[self::SESSION_KEY][$key] = $bucket;
    }

    public static function checkLocked(string $email): ?array {
        $email = self::normalizeEmail($email);
        if ($email === '') {
            return null;
        }

        $bucket = self::getBucket($email);
        $now = time();

        if ($bucket['locked_until'] > $now) {
            $remaining = $bucket['locked_until'] - $now;
            return [
                'status' => 'error',
                'message' => "Too many failed login attempts. Please try again in {$remaining} second(s).",
                'locked' => true,
                'retry_after' => $remaining,
            ];
        }

        if ($bucket['locked_until'] > 0) {
            $bucket['count'] = 0;
            $bucket['locked_until'] = 0;
            self::saveBucket($email, $bucket);
        }

        return null;
    }

    public static function recordFailure(string $email, array $result): array {
        $email = self::normalizeEmail($email);
        if ($email === '') {
            return $result;
        }

        $bucket = self::getBucket($email);
        $bucket['count']++;

        if ($bucket['count'] >= self::MAX_ATTEMPTS) {
            $bucket['count'] = 0;
            $bucket['locked_until'] = time() + self::LOCKOUT_SECONDS;
            self::saveBucket($email, $bucket);

            return [
                'status' => 'error',
                'message' => 'Too many failed login attempts. Please try again in ' . self::LOCKOUT_SECONDS . ' seconds.',
                'locked' => true,
                'retry_after' => self::LOCKOUT_SECONDS,
            ];
        }

        self::saveBucket($email, $bucket);
        $result['attempts_remaining'] = self::MAX_ATTEMPTS - $bucket['count'];

        return $result;
    }

    public static function clear(string $email): void {
        $key = self::normalizeEmail($email);
        if ($key === '') {
            return;
        }

        SessionHelper::start();
        unset($_SESSION[self::SESSION_KEY][$key]);
    }
}
