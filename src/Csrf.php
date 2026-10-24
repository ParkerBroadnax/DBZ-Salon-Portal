<?php

declare(strict_types=1);

namespace Salon;

/**
 * Per-session token so other sites can't submit forms on a visitor's behalf.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf" value="' . e(self::token()) . '">';
    }

    public static function valid(?string $submitted): bool
    {
        return is_string($submitted) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $submitted);
    }
}
