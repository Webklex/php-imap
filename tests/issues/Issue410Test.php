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
use Tests\fixtures\FixtureTestCase;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

class Issue410Test extends FixtureTestCase {

    public function testIssueEmail() {
        $message = $this->getFixture("issue-410.eml");

        self::assertSame("☆第132号　「ガーデン&エクステリア」専門店のためのＱ&Ａサロン　【月刊エクステリア・ワーク】", (string)$message->subject);

        $attachments = $message->getAttachments();

        self::assertSame(1, $attachments->count());

        $attachment = $attachments->first();
        self::assertSame("☆第132号　「ガーデン&エクステリア」専門店のためのＱ&Ａサロン　【月刊エクステリア・ワーク】", $attachment->filename);
        self::assertSame("☆第132号　「ガーデン&エクステリア」専門店のためのＱ&Ａサロン　【月刊エクステリア・ワーク】", $attachment->name);
    }

    public function testIssueEmailB() {
        $message = $this->getFixture("issue-410b.eml");

        self::assertSame("386 - 400021804 - 19., Heiligenstädter Straße 80 - 0819306 - Anfrage Vergabevorschlag", (string)$message->subject);

        $attachments = $message->getAttachments();

        self::assertSame(1, $attachments->count());

        $attachment = $attachments->first();
        self::assertSame("2021_Mängelliste_0819306.xlsx", $attachment->description);
        self::assertSame("2021_Mängelliste_0819306.xlsx", $attachment->filename);
        self::assertSame("2021_Mängelliste_0819306.xlsx", $attachment->name);
    }

    public function testIssueEmailSymbols() {
        $message = $this->getFixture("issue-410symbols.eml");

        $attachments = $message->getAttachments();

        self::assertSame(1, $attachments->count());

        $attachment = $attachments->first();
        self::assertSame("Checkliste 10.,DAVIDGASSE 76-80;2;2.pdf", $attachment->description);
        self::assertSame("Checkliste 10.,DAVIDGASSE 76-80;2;2.pdf", $attachment->filename);
        self::assertSame("Checkliste 10.,DAVIDGASSE 76-80;2;2.pdf", $attachment->name);
    }

}