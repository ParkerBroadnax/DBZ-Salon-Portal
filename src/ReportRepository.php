<?php

declare(strict_types=1);

namespace Salon;

use PDO;

/**
 * Read-only reporting queries for the admin dashboard and the daily export job.
 * Written in portable SQL so they run on both MySQL and SQLite.
 */
final class ReportRepository
{
    public function __construct(private PDO $db)
    {
    }

    /** Appointments per service with booked value, cancelled excluded. */
    public function byService(?string $from = null, ?string $to = null): array
    {
        [$where, $params] = $this->dateRange($from, $to);
        $sql = "SELECT s.name AS service,
                       COUNT(a.id) AS bookings,
                       COALESCE(SUM(CASE WHEN a.id IS NOT NULL THEN s.cost ELSE 0 END), 0) AS booked_value
                FROM services s
                LEFT JOIN appointments a
                       ON a.service_id = s.id AND a.status <> 'cancelled' {$where}
                GROUP BY s.id, s.name
                ORDER BY bookings DESC, s.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Appointments per day and status. */
    public function byDay(?string $from = null, ?string $to = null): array
    {
        [$where, $params] = $this->dateRange($from, $to);
        $sql = "SELECT a.apt_date, a.status, COUNT(*) AS bookings
                FROM appointments a
                WHERE 1 = 1 {$where}
                GROUP BY a.apt_date, a.status
                ORDER BY a.apt_date, a.status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Upcoming appointments for the admin list. */
    public function upcoming(string $today, int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.id, a.apt_number, a.name, a.email, a.phone, s.name AS service,
                    a.apt_date, a.apt_time, a.status
             FROM appointments a
             JOIN services s ON s.id = a.service_id
             WHERE a.apt_date >= :today
             ORDER BY a.apt_date, a.apt_time
             LIMIT :lim"
        );
        $stmt->bindValue('today', $today);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE appointments SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
        return $stmt->rowCount() === 1;
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function dateRange(?string $from, ?string $to): array
    {
        $where = '';
        $params = [];
        if ($from !== null) {
            $where .= ' AND a.apt_date >= :from';
            $params['from'] = $from;
        }
        if ($to !== null) {
            $where .= ' AND a.apt_date <= :to';
            $params['to'] = $to;
        }
        return [$where, $params];
    }
}
