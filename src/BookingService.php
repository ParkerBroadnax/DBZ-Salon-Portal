<?php

declare(strict_types=1);

namespace Salon;

use DateTimeImmutable;
use PDO;
use PDOException;

/**
 * Validates and stores appointment requests.
 *
 * Replaces the original handler, which was copy-pasted into two pages and
 * concatenated raw $_POST values into SQL.
 */
final class BookingService
{
    private const DATE_FORMATS = ['!n/j/Y', '!Y-m-d'];
    private const TIME_FORMATS = ['!g:ia', '!g:i a', '!H:i'];
    private const MAX_NUMBER_ATTEMPTS = 5;

    public function __construct(
        private PDO $db,
        private ServiceRepository $services,
        private ?DateTimeImmutable $today = null,
    ) {
    }

    /**
     * @param array<string, mixed> $input raw form input
     * @return array{errors: array<string, string>, data: array<string, string|int>}
     */
    public function validate(array $input): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $serviceId = filter_var($input['service_id'] ?? null, FILTER_VALIDATE_INT);

        if ($name === '' || mb_strlen($name) > 120) {
            $errors['name'] = 'Please enter your name (up to 120 characters).';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (!preg_match('/^[0-9+().\-\s]{7,30}$/', $phone)) {
            $errors['phone'] = 'Please enter a valid phone number.';
        }
        if ($serviceId === false || !$this->services->exists($serviceId)) {
            $errors['service_id'] = 'Please choose a service.';
        }

        $date = self::parse((string) ($input['adate'] ?? ''), self::DATE_FORMATS);
        $today = ($this->today ?? new DateTimeImmutable('today'))->setTime(0, 0);
        if ($date === null) {
            $errors['adate'] = 'Please choose a date.';
        } elseif ($date < $today) {
            $errors['adate'] = 'Appointments cannot be booked in the past.';
        }

        $time = self::parse(strtolower((string) ($input['atime'] ?? '')), self::TIME_FORMATS);
        if ($time === null) {
            $errors['atime'] = 'Please choose a time.';
        }

        return [
            'errors' => $errors,
            'data' => $errors ? [] : [
                'name' => $name,
                'email' => strtolower($email),
                'phone' => $phone,
                'service_id' => (int) $serviceId,
                'apt_date' => $date->format('Y-m-d'),
                'apt_time' => $time->format('H:i:s'),
            ],
        ];
    }

    /**
     * @param array<string, string|int> $data output of validate()
     * @return string the new appointment number
     */
    public function create(array $data): string
    {
        $stmt = $this->db->prepare(
            'INSERT INTO appointments (apt_number, name, email, phone, service_id, apt_date, apt_time)
             VALUES (:apt_number, :name, :email, :phone, :service_id, :apt_date, :apt_time)'
        );

        for ($attempt = 1; $attempt <= self::MAX_NUMBER_ATTEMPTS; $attempt++) {
            $number = (string) random_int(100000000, 999999999);
            try {
                $stmt->execute(['apt_number' => $number] + $data);
                return $number;
            } catch (PDOException $e) {
                // 23000 = integrity constraint violation; retry only on a number collision.
                if ($e->getCode() !== '23000' || $attempt === self::MAX_NUMBER_ATTEMPTS) {
                    throw $e;
                }
            }
        }

        throw new \LogicException('unreachable');
    }

    private static function parse(string $value, array $formats): ?DateTimeImmutable
    {
        $value = trim($value);
        foreach ($formats as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $value);
            $errors = DateTimeImmutable::getLastErrors();
            if ($parsed !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $parsed;
            }
        }
        return null;
    }
}
