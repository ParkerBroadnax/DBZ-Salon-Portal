<?php

declare(strict_types=1);

namespace Salon\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Salon\MessageService;

final class MessageServiceTest extends TestCase
{
    private PDO $db;
    private MessageService $messages;

    protected function setUp(): void
    {
        $this->db = TestDb::create();
        $this->messages = new MessageService($this->db);
    }

    private function input(array $overrides = []): array
    {
        return $overrides + [
            'name' => '  Grace Hopper ',
            'email' => 'Grace@Example.com',
            'subject' => 'Opening hours',
            'body' => 'Are you open on Sundays?',
        ];
    }

    public function testValidInputIsNormalized(): void
    {
        $result = $this->messages->validate($this->input());

        $this->assertSame([], $result['errors']);
        $this->assertSame('Grace Hopper', $result['data']['name']);
        $this->assertSame('grace@example.com', $result['data']['email']);
    }

    public function testRejectsMissingAndOversizedFields(): void
    {
        $result = $this->messages->validate($this->input([
            'name' => '',
            'email' => 'not-an-email',
            'subject' => str_repeat('a', 151),
            'body' => '   ',
        ]));

        $this->assertSame(['name', 'email', 'subject', 'body'], array_keys($result['errors']));
        $this->assertSame([], $result['data']);
    }

    public function testSqlInjectionIsStoredAsPlainText(): void
    {
        $body = "'); DROP TABLE messages;--";
        $result = $this->messages->validate($this->input(['body' => $body]));
        $id = $this->messages->create($result['data']);

        $stored = $this->db->query("SELECT body FROM messages WHERE id = {$id}")->fetchColumn();
        $this->assertSame($body, $stored);
        $this->assertSame(1, (int) $this->db->query('SELECT COUNT(*) FROM messages')->fetchColumn());
    }

    public function testRecentListsNewestFirstAndMarkReadUpdatesOneRow(): void
    {
        $first = $this->messages->create($this->messages->validate($this->input(['subject' => 'First']))['data']);
        $second = $this->messages->create($this->messages->validate($this->input(['subject' => 'Second']))['data']);

        $recent = $this->messages->recent();
        $this->assertSame(['Second', 'First'], array_column($recent, 'subject'));
        $this->assertSame(0, (int) $recent[0]['is_read']);

        $this->assertTrue($this->messages->markRead($first));
        $this->assertFalse($this->messages->markRead(999));
        $this->assertSame(1, (int) $this->db->query("SELECT is_read FROM messages WHERE id = {$first}")->fetchColumn());
        $this->assertSame(0, (int) $this->db->query("SELECT is_read FROM messages WHERE id = {$second}")->fetchColumn());
    }
}
