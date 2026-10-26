<?php

declare(strict_types=1);

namespace Salon\Tests;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Salon\BookingService;
use Salon\ServiceRepository;

final class BookingServiceTest extends TestCase
{
    private PDO $db;
    private BookingService $booking;

    protected function setUp(): void
    {
        $this->db = TestDb::create();
        $this->booking = new BookingService(
            $this->db,
            new ServiceRepository($this->db),
            new DateTimeImmutable('2026-10-24'),
        );
    }

    private function input(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Ada Lovelace',
            'email' => 'Ada@Example.com',
            'phone' => '(718) 555-0100',
            'service_id' => '1',
            'adate' => '10/30/2026',
            'atime' => '2:30pm',
        ];
    }

    public function testValidInputIsNormalized(): void
    {
        $result = $this->booking->validate($this->input());

        $this->assertSame([], $result['errors']);
        $this->assertSame('ada@example.com', $result['data']['email']);
        $this->assertSame('2026-10-30', $result['data']['apt_date']);
        $this->assertSame('14:30:00', $result['data']['apt_time']);
    }

    public function testRejectsPastDateUnknownServiceAndBadEmail(): void
    {
        $result = $this->booking->validate($this->input([
            'adate' => '10/1/2026',
            'service_id' => '999',
            'email' => 'not-an-email',
        ]));

        $this->assertArrayHasKey('adate', $result['errors']);
        $this->assertArrayHasKey('service_id', $result['errors']);
        $this->assertArrayHasKey('email', $result['errors']);
    }

    public function testRejectsImpossibleDate(): void
    {
        $result = $this->booking->validate($this->input(['adate' => '2/30/2027']));
        $this->assertArrayHasKey('adate', $result['errors']);
    }

    public function testArrayInputIsRejected(): void
    {
        $result = $this->booking->validate($this->input(['name' => ['x'], 'adate' => ['10/30/2026']]));

        $this->assertArrayHasKey('name', $result['errors']);
        $this->assertArrayHasKey('adate', $result['errors']);
    }

    public function testSqlInjectionIsStoredAsPlainText(): void
    {
        $name = "Robert'); DROP TABLE appointments;--";
        $result = $this->booking->validate($this->input(['name' => $name]));
        $number = $this->booking->create($result['data']);

        $stored = $this->db->query("SELECT name FROM appointments WHERE apt_number = '{$number}'")->fetchColumn();
        $this->assertSame($name, $stored);
        $this->assertSame(1, (int) $this->db->query('SELECT COUNT(*) FROM appointments')->fetchColumn());
    }

    public function testAppointmentNumbersAreUniqueNineDigits(): void
    {
        $data = $this->booking->validate($this->input())['data'];
        $numbers = [];
        for ($i = 0; $i < 50; $i++) {
            $numbers[] = $this->booking->create($data);
        }

        $this->assertCount(50, array_unique($numbers));
        foreach ($numbers as $n) {
            $this->assertMatchesRegularExpression('/^\d{9}$/', $n);
        }
    }
}
