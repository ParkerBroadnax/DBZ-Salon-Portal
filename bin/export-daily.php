<?php

declare(strict_types=1);

/**
 * Daily batch export of booking metrics, one date partition per run.
 *
 *   php bin/export-daily.php --date=2026-10-24 [--out=exports]
 *
 * Writes exports/date=YYYY-MM-DD/{bookings_by_service,bookings_by_status}.csv
 * plus a _SUCCESS marker. Design choices, borrowed from data-platform practice:
 *  - Idempotent: re-running a date replaces that partition, never appends.
 *  - Atomic: files are written to a temp folder and renamed into place.
 *  - Privacy-aware: only aggregates leave the database, no names, emails or phones.
 */

require __DIR__ . '/../src/bootstrap.php';

use Salon\ReportRepository;

$opts = getopt('', ['date:', 'out:']);
$date = $opts['date'] ?? (new DateTimeImmutable('yesterday'))->format('Y-m-d');
$out = rtrim($opts['out'] ?? dirname(__DIR__) . '/exports', '/');

$parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
    fwrite(STDERR, "Invalid --date, expected YYYY-MM-DD\n");
    exit(2);
}

$reports = new ReportRepository($db);
$partition = "{$out}/date={$date}";
$tmp = "{$out}/.tmp-date={$date}-" . bin2hex(random_bytes(4));

if (!is_dir($tmp) && !mkdir($tmp, 0775, true)) {
    fwrite(STDERR, "Could not create {$tmp}\n");
    exit(1);
}

$write = static function (string $file, array $header, iterable $rows): int {
    $fh = fopen($file, 'wb');
    fputcsv($fh, $header);
    $n = 0;
    foreach ($rows as $row) {
        fputcsv($fh, $row);
        $n++;
    }
    fclose($fh);
    return $n;
};

$byService = $write(
    "{$tmp}/bookings_by_service.csv",
    ['date', 'service', 'bookings', 'booked_value'],
    (static function () use ($reports, $date) {
        foreach ($reports->byService($date, $date) as $r) {
            yield [$date, $r['service'], (int) $r['bookings'], number_format((float) $r['booked_value'], 2, '.', '')];
        }
    })()
);

$byStatus = $write(
    "{$tmp}/bookings_by_status.csv",
    ['date', 'status', 'bookings'],
    (static function () use ($reports, $date) {
        foreach ($reports->byDay($date, $date) as $r) {
            yield [$date, $r['status'], (int) $r['bookings']];
        }
    })()
);

touch("{$tmp}/_SUCCESS");

// Swap the finished partition into place.
if (is_dir($partition)) {
    foreach (array_diff(scandir($partition) ?: [], ['.', '..']) as $old) {
        unlink("{$partition}/{$old}");
    }
    rmdir($partition);
}
rename($tmp, $partition);

fwrite(STDOUT, "Wrote {$partition} ({$byService} service rows, {$byStatus} status rows)\n");
