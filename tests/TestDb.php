<?php

declare(strict_types=1);

namespace Salon\Tests;

use PDO;
use Salon\Database;

/** In-memory SQLite database with the real schema and seed data. */
final class TestDb
{
    public static function create(): PDO
    {
        $root = dirname(__DIR__);
        $pdo = Database::connect('sqlite::memory:');
        $pdo->exec(file_get_contents($root . '/db/schema.sqlite.sql'));
        $pdo->exec(file_get_contents($root . '/db/seed.sql'));
        return $pdo;
    }
}
