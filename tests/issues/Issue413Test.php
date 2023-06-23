<?php
/*
* File: Issue413Test.php
* Category: Test
* Author: M.Goldenbaum
* Created: 23.06.23 21:09
* Updated: -
*
* Description:
*  -
*/

namespace Tests\issues;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Message;

class Issue413Test extends TestCase {

    public function testIssueEmail() {
        $filename = implode(DIRECTORY_SEPARATOR, [__DIR__, "..", "messages", "issue-413.eml"]);
        $message = Message::fromFile($filename);

        self::assertSame("Test Message", (string)$message->subject);
        self::assertSame("This is just a test, so ignore it (if you can!)\r\n\r\nTony Marston", $message->getTextBody());
    }

}