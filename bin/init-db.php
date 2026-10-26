<?php

declare(strict_types=1);

/**
 * Create the schema and sample services in the database named by DB_DSN.
 *
 *   php bin/init-db.php            # uses DB_DSN from the environment or .env
 *
 * For a zero-setup local run, set DB_DSN=sqlite:var/salon.sqlite.
 */

$root = dirname(__DIR__);
require $root . '/src/Env.php';
require $root . '/src/Database.php';

Salon\Env::load($root . '/.env');
$dsn = Salon\Database::resolveSqlitePath(Salon\Env::get('DB_DSN', 'sqlite:var/salon.sqlite'), $root);

if (str_starts_with($dsn, 'sqlite:')) {
    $path = substr($dsn, 7);
    if ($path !== ':memory:' && !is_dir(dirname($path))) {
        mkdir(dirname($path), 0775, true);
    }
}

$pdo = Salon\Database::connect($dsn, Salon\Env::get('DB_USER'), Salon\Env::get('DB_PASSWORD'));
$schema = str_starts_with($dsn, 'sqlite:') ? 'schema.sqlite.sql' : 'schema.sql';

$pdo->exec(file_get_contents("{$root}/db/{$schema}"));
if ((int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn() === 0) {
    $pdo->exec(file_get_contents("{$root}/db/seed.sql"));
}

echo "Database ready ({$schema}).\n";
