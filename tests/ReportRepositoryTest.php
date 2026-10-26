<?php

declare(strict_types=1);

namespace Salon\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Salon\ReportRepository;

final class ReportRepositoryTest extends TestCase
{
    private PDO $db;
    private ReportRepository $reports;

    protected function setUp(): void
    {
        $this->db = TestDb::create();
        $this->reports = new ReportRepository($this->db);

        $insert = $this->db->prepare(
            "INSERT INTO appointments (apt_number, name, email, phone, service_id, apt_date, apt_time, status)
             VALUES (?, 'Test', 't@example.com', '7185550100', ?, ?, '10:00:00', ?)"
        );
        // service 1 = Haircut ($35), service 2 = Silk Press ($85)
        $insert->execute(['100000001', 1, '2026-10-24', 'confirmed']);
        $insert->execute(['100000002', 1, '2026-10-24', 'pending']);
        $insert->execute(['100000003', 2, '2026-10-24', 'cancelled']);
        $insert->execute(['100000004', 2, '2026-10-25', 'pending']);
    }

    public function testByServiceExcludesCancelledAndFiltersByDate(): void
    {
        $rows = array_column($this->reports->byService('2026-10-24', '2026-10-24'), null, 'service');

        $this->assertSame(2, (int) $rows['Haircut']['bookings']);
        $this->assertEquals(70.0, (float) $rows['Haircut']['booked_value']);
        $this->assertSame(0, (int) $rows['Silk Press']['bookings']);
        $this->assertEquals(0.0, (float) $rows['Silk Press']['booked_value'], 'no bookings means no value');
        $this->assertCount(8, $rows, 'every service appears, even with zero bookings');
    }

    public function testByDayGroupsByStatus(): void
    {
        $rows = $this->reports->byDay('2026-10-24', '2026-10-24');
        $counts = array_column($rows, 'bookings', 'status');

        $this->assertEquals(['cancelled' => 1, 'confirmed' => 1, 'pending' => 1], array_map('intval', $counts));
    }

    public function testSetStatusRejectsUnknownValues(): void
    {
        $this->assertFalse($this->reports->setStatus(1, 'deleted'));
        $this->assertTrue($this->reports->setStatus(1, 'completed'));
    }
}
