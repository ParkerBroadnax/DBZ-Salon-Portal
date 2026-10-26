<?php

declare(strict_types=1);

namespace Salon;

use PDO;

/**
 * Validates, stores and lists messages from the contact form.
 */
final class MessageService
{
    private const MAX_LENGTHS = ['name' => 120, 'email' => 190, 'subject' => 150, 'body' => 5000];

    public function __construct(private PDO $db)
    {
    }

    /**
     * @param array<string, mixed> $input raw form input
     * @return array{errors: array<string, string>, data: array<string, string>}
     */
    public function validate(array $input): array
    {
        $errors = [];
        $name = text_input($input, 'name');
        $email = text_input($input, 'email');
        $subject = text_input($input, 'subject');
        $body = text_input($input, 'body');

        if ($name === '' || mb_strlen($name) > self::MAX_LENGTHS['name']) {
            $errors['name'] = 'Please enter your name (up to 120 characters).';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > self::MAX_LENGTHS['email']) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if ($subject === '' || mb_strlen($subject) > self::MAX_LENGTHS['subject']) {
            $errors['subject'] = 'Please enter a subject (up to 150 characters).';
        }
        if ($body === '' || mb_strlen($body) > self::MAX_LENGTHS['body']) {
            $errors['body'] = 'Please enter a message (up to 5000 characters).';
        }

        return [
            'errors' => $errors,
            'data' => $errors ? [] : [
                'name' => $name,
                'email' => strtolower($email),
                'subject' => $subject,
                'body' => $body,
            ],
        ];
    }

    /** @param array<string, string> $data output of validate() */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO messages (name, email, subject, body) VALUES (:name, :email, :subject, :body)'
        );
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    /** Unread messages first, then newest first, so the limit never hides an unread one behind read ones. */
    public function recent(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, subject, body, is_read, created_at
             FROM messages
             ORDER BY is_read, created_at DESC, id DESC
             LIMIT :lim'
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markRead(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE messages SET is_read = 1 WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() === 1;
    }
}
