<?php

declare(strict_types=1);

namespace Salon;

/**
 * Minimal environment loader: real environment variables win, then a .env file.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $file = [];

    public static function load(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            self::$file[$key] = trim($value, "\"'");
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
        return self::$file[$key] ?? $default;
    }
}
