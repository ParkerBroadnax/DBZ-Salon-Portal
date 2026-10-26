<?php

declare(strict_types=1);

namespace Salon;

use PDO;

/**
 * Creates the PDO connection from environment configuration.
 *
 * Credentials live in the environment (or a local .env file), never in code.
 */
final class Database
{
    public static function fromEnv(): PDO
    {
        // Default: a local SQLite file, so the app runs with no database server.
        $dsn = Env::get('DB_DSN', 'sqlite:' . dirname(__DIR__) . '/var/salon.sqlite');
        $user = Env::get('DB_USER', 'salon');
        $pass = Env::get('DB_PASSWORD', '');

        return self::connect(self::resolveSqlitePath($dsn, dirname(__DIR__)), $user, $pass);
    }

    /** Make a relative SQLite path relative to the project root, not the web server's working directory. */
    public static function resolveSqlitePath(string $dsn, string $root): string
    {
        if (!str_starts_with($dsn, 'sqlite:')) {
            return $dsn;
        }
        $path = substr($dsn, 7);
        if ($path === ':memory:' || str_starts_with($path, '/')) {
            return $dsn;
        }
        return 'sqlite:' . $root . '/' . $path;
    }

    public static function connect(string $dsn, ?string $user = null, ?string $pass = null): PDO
    {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        if (str_starts_with($dsn, 'sqlite:')) {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }

        return $pdo;
    }
}
