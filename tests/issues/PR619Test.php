<?php

namespace Tests\issues;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Exceptions\NoFolderSelectedException;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Connection\Protocols\ImapProtocol;

class PR619Test extends TestCase
{
    private function makeClientWithNullActiveFolder(): Client
    {
        $config = Config::make([
            'accounts' => [
                'default' => [
                    'host'       => 'localhost',
                    'protocol'   => 'imap',
                    'encryption' => 'ssl',
                    'username'   => 'foo@example.com',
                    'password'   => 'secret',
                ],
            ],
        ]);

        $client = new Client($config);

        $protocol = $this->createStub(ImapProtocol::class);
        $protocol->method('connected')->willReturn(true);
        $client->connection = $protocol;

        // Simulate the state after an implicit reconnect:
        // Client::connect() always calls disconnect() first,
        // and disconnect() resets active_folder to null.
        $client->setActiveFolder(null);

        return $client;
    }

    public function testMakeThrowsWhenActiveFolderIsNull(): void
    {
        $this->expectException(NoFolderSelectedException::class);

        $client = $this->makeClientWithNullActiveFolder();
        Message::make(1, 0, $client, "Subject: Test\r\n\r\n", '', []);
    }

    public function testMakeSucceedsWithActiveFolder(): void
    {
        $client = $this->makeClientWithNullActiveFolder();
        $client->setActiveFolder("INBOX");

        $message = Message::make(1, 0, $client, "Subject: Test\r\n\r\n", '', []);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame("INBOX", $message->getFolderPath());
    }
}
