<?php
/*
* File: Issue355Test.php
* Category: -
* Author: M.Goldenbaum
* Created: 10.01.23 10:48
* Updated: -
*
* Description:
*  -
*/

namespace Tests\issues;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;

class Issue379Test extends TestCase {

    public function testIssue() {
        if (!$_ENV["LIVE_MAILBOX"] ?? false) {
            $this->markTestSkipped("This test requires a live mailbox. Please set the LIVE_MAILBOX environment variable to run this test.");
        }
        $cm = new ClientManager([
            'accounts' => [
                'default' => [
                    'host'           => $_ENV["LIVE_MAILBOX_HOST"] ?? "localhost",
                    'port'           => $_ENV["LIVE_MAILBOX_PORT"] ?? 143,
                    'protocol'       => 'imap', //might also use imap, [pop3 or nntp (untested)]
                    'encryption'     => $_ENV["LIVE_MAILBOX_ENCRYPTION"] ?? false, // Supported: false, 'ssl', 'tls'
                    'validate_cert'  => $_ENV["LIVE_MAILBOX_VALIDATE_CERT"] ?? false,
                    'username'       => $_ENV["LIVE_MAILBOX_USERNAME"] ?? "root@example.com",
                    'password'       => $_ENV["LIVE_MAILBOX_PASSWORD"] ?? "foobar",
                ],
            ],
        ]);

        /** @var Client $client */
        $client = $cm->account('default');
        $this->assertNotNull($client);

        //Connect to the IMAP Server
        $client->connect();

        $folder = $client->getFolderByPath('INBOX');
        $this->assertNotNull($folder);

        $message = $folder->messages()->getMessageByUid(2);
        $this->assertNotNull($message);

        $this->assertEquals(127, $message->getSize());
    }

}