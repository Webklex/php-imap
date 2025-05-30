<?php
/*
* File: Issue582Test.php
* Category: -
* Author: Mark Hewitt
* Created: 29.05.25  
* Updated: -
*
* Description:
*  Test PR and fix for issue 582 and potentially 567/580 and 578
*/

namespace Tests\issues;

use PHPUnit\Framework\TestCase;
use Tests\fixtures\FixtureTestCase;
use Webklex\PHPIMAP\Attachment;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Message;

class Issue582Test extends FixtureTestCase {

    /**
     * @throws RuntimeException
     * @throws MessageContentFetchingException
     * @throws ResponseException
     * @throws ImapBadRequestException
     * @throws InvalidMessageDateException
     * @throws ConnectionFailedException
     * @throws \ReflectionException
     * @throws ImapServerErrorException
     * @throws AuthFailedException
     * @throws MaskNotFoundException
     */
    public function testAttachmentEmail() {
        $message = $this->getFixture("issue-582a.eml");

        self::assertSame("redacted_Balances_GBP_", (string)$message->subject);

        $attachments = $message->getAttachments();

        self::assertSame(1, $attachments->count());

        /** @var Attachment $attachment */
        $attachment = $attachments->first();
        self::assertSame("redacted_Balances_GBP_.csv", $attachment->name);
        self::assertStringEndsWith("stake_limit,soft_limit,h", $attachment->content);
    }

    /**
     * @throws RuntimeException
     * @throws MessageContentFetchingException
     * @throws ResponseException
     * @throws ImapBadRequestException
     * @throws InvalidMessageDateException
     * @throws ConnectionFailedException
     * @throws \ReflectionException
     * @throws ImapServerErrorException
     * @throws AuthFailedException
     * @throws MaskNotFoundException
     */
    public function testContentEmail() {
        $message = $this->getFixture("issue-582b.eml");

        self::assertSame("html body in multipart related container is parsed as attachment", (string)$message->subject);
        self::assertStringContainsString("This is a message in a multipart related container", $message->getHtmlBody());
        $attachments = $message->getAttachments();
        self::assertSame(0, $attachments->count());
    }    
}