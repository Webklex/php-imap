<?php
/*
* File: Issue410Test.php
* Category: -
* Author: M.Goldenbaum
* Created: 23.06.23 20:41
* Updated: -
*
* Description:
*  -
*/

namespace Tests\issues;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Message;

class Issue410Test extends TestCase {

    public function testIssueEmail() {
        $filename = implode(DIRECTORY_SEPARATOR, [__DIR__, "..", "messages", "issue-410.eml"]);
        $message = Message::fromFile($filename);

        self::assertSame("☆第132号　「ガーデン&エクステリア」専門店のためのＱ&Ａサロン　【月刊エクステリア・ワーク】", (string)$message->subject);

        $attachments = $message->getAttachments();

        self::assertSame(1, $attachments->count());

        $attachment = $attachments->first();
        self::assertSame("☆第132号　「ガーデン&エクステリア」専門店のためのＱ&Ａサロン　【月刊エクステリア・ワーク】", $attachment->filename);
        self::assertSame("☆第132号　「ガーデン&エクステリア」専門店のためのＱ&Ａサロン　【月刊エクステリア・ワーク】", $attachment->name);
    }

}