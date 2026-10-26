<?php

declare(strict_types=1);

namespace Salon;

use PDO;

final class ServiceRepository
{
    public function __construct(private PDO $db)
    {
    }

    /** @return list<array{id: int, name: string, cost: string}> */
    public function all(): array
    {
        $rows = $this->db->query('SELECT id, name, cost FROM services ORDER BY name')->fetchAll();
        return array_map(static fn (array $r) => [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'cost' => number_format((float) $r['cost'], 2),
        ], $rows);
    }

    public function exists(int $id): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM services WHERE id = ?');
        $stmt->execute([$id]);
        return (bool) $stmt->fetchColumn();
    }
}
