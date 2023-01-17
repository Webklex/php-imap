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
use Webklex\PHPIMAP\Message;

class Issue275Test extends TestCase {

    public function testIssue() {
        $filename = implode(DIRECTORY_SEPARATOR, [__DIR__, "..", "messages", "issue-275.eml"]);
        $message = Message::fromFile($filename);

        self::assertSame("Testing 123", (string)$message->subject);
        self::assertSame("testing123 this is a body", $message->getTextBody());
    }

}