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

use Tests\LiveMailboxTestCase;

class Issue379Test extends LiveMailboxTestCase {

    public function testIssue() {
        if (!$_ENV["LIVE_MAILBOX"] ?? false) {
            $this->markTestSkipped("This test requires a live mailbox. Please set the LIVE_MAILBOX environment variable to run this test.");
        }

        $client = $this->getClient();
        $this->assertNotNull($client);

        //Connect to the IMAP Server
        $client->connect();

        $folder = $client->getFolderByPath('INBOX');
        $this->assertNotNull($folder);

        $content = file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, "..", "messages", "plain.eml"]));
        $message = $this->appendMessage($folder, $content);
        $this->assertNotNull($message);

        $this->assertEquals(214, $message->getSize());

        $this->assertEquals(true, $message->delete(true));
    }

}