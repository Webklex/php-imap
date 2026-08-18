<?php
/*
* File:     PR621Test.php
* Category: Test
* Author:   Mikilio
* Created:  16.08.26
* Updated:  -
*
* Description:
*  Reconnect / folder-invariant fix. A Message always has an associated folder.
*/

namespace Tests\issues;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Connection\Protocols\ImapProtocol;
use Webklex\PHPIMAP\Connection\Protocols\Response;
use Webklex\PHPIMAP\Exceptions\NoFolderSelectedException;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Query\WhereQuery;

class ReconnectClient extends Client {

    public bool $reconnected = false;

    public function connect(): Client {
        $this->reconnected = true;
        $this->setActiveFolder(null);
        return $this;
    }
}


class PR621Test extends TestCase {

    /** @var Client $client */
    protected Client $client;

    /** @var MockObject ImapProtocol mockup */
    protected MockObject $protocol;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        $config = Config::make([
                                   "accounts" => [
                                       "default" => [
                                           'protocol'   => 'imap',
                                           'encryption' => 'ssl',
                                           'username'   => 'foo@domain.tld',
                                           'password'   => 'bar',
                                           'proxy'      => [
                                               'socket'          => null,
                                               'request_fulluri' => false,
                                               'username'        => null,
                                               'password'        => null,
                                           ],
                                       ],
                                   ]
                               ]);
        $this->client = new Client($config);
    }

    public function testDetachedMessageFolderAccessThrows() {
        $message = Message::fromFile(__DIR__ . "/../messages/1366671050@github.com.eml");

        $this->expectException(NoFolderSelectedException::class);
        $message->getFolderPath();
    }

    public function testConstructionResolvesActiveFolderAsFallback() {
        $this->createNewProtocolMockup();
        $this->client->openFolder("INBOX");

        $message = Message::make(1, null, $this->client, "", "", [0 => "\\Seen"], null);

        self::assertSame("INBOX", $message->getFolderPath());
    }

    public function testQueryUsesClientActiveFolder() {
        $this->createNewProtocolMockup();
        $this->client->setActiveFolder("INBOX.Active");
        $this->protocol->method('headers')->willReturn(Response::empty()->setResponse([1 => "Subject: Probe\r\n\r\n"]));

        $query = new WhereQuery($this->client, [], "INBOX.Query");
        $message = $query->setFetchBody(false)->setFetchFlags(false)->getMessage(1);

        self::assertSame("INBOX.Active", $message->getFolderPath());
    }

    public function testQueryMakeUsesClientActiveFolder() {
        $this->createNewProtocolMockup();
        $this->client->setActiveFolder("INBOX.Active");

        $query = new QueryFolderProbe($this->client, [], "INBOX.Probe");
        $message = $query->makeMessage(1, 1, "", "", [0 => "\\Seen"]);

        self::assertSame("INBOX.Active", $message->getFolderPath());
    }

    public function testBareQueryWithoutFolderFallsBackToActiveFolder() {
        $this->createNewProtocolMockup();
        $this->client->setActiveFolder("INBOX.Active");
        $this->protocol->method('headers')->willReturn(Response::empty()->setResponse([1 => "Subject: Probe\r\n\r\n"]));

        $query = new WhereQuery($this->client);
        $message = $query->setFetchBody(false)->setFetchFlags(false)->getMessage(1);

        self::assertSame("INBOX.Active", $message->getFolderPath());
    }

    public function testBareQueryWithNullActiveFolderThrows() {
        $this->createNewProtocolMockup();
        $this->client->setActiveFolder(null);

        $query = new WhereQuery($this->client);

        $this->expectException(NoFolderSelectedException::class);
        $query->getMessage(1);
    }

    public function testFolderQuerySetsClientActiveFolder() {
        $this->createNewProtocolMockup();
        $this->protocol->method('headers')->willReturn(Response::empty()->setResponse([1 => "Subject: Probe\r\n\r\n"]));

        $folder = new Folder($this->client, "INBOX.Sub", ".", []);
        $query = $folder->query();

        $message = $query->setFetchBody(false)->setFetchFlags(false)->getMessage(1);

        self::assertSame("INBOX.Sub", $message->getFolderPath());
    }

    public function testSearchReassertsFolderAfterReconnect() {
        $config = Config::make([
                                   "accounts" => [
                                       "default" => [
                                           'protocol'   => 'imap',
                                           'encryption' => 'ssl',
                                           'username'   => 'foo@domain.tld',
                                           'password'   => 'bar',
                                           'proxy'      => [
                                               'socket'          => null,
                                               'request_fulluri' => false,
                                               'username'        => null,
                                               'password'        => null,
                                           ],
                                       ],
                                   ]
                               ]);

        $protocol = $this->createMock(ImapProtocol::class);
        $protocol->method('connected')->willReturn(false);
        $protocol->method('createStream')->willReturn(true);
        $protocol->method('getConnectionTimeout')->willReturn(30);
        $protocol->method('search')->willReturn(Response::empty()->setResult([1]));
        $protocol->expects($this->once())->method('selectFolder')->willReturn(Response::empty()->setResponse([
            "flags" => [0 => []], "exists" => 1, "recent" => 0, "unseen" => 0, "uidvalidity" => 1, "uidnext" => 2,
        ]));

        $client = new ReconnectClient($config);
        $client->connection = $protocol;
        $client->setActiveFolder("INBOX.Query");

        $query = new WhereQuery($client, [], "INBOX.Query");
        $result = $query->search();

        self::assertTrue($client->reconnected);
        self::assertSame([1], $result->toArray());
    }

    /**
     * Create a new protocol mockup
     *
     * @return void
     */
    protected function createNewProtocolMockup(): void {
        $this->protocol = $this->createMock(ImapProtocol::class);

        $this->protocol->expects($this->any())->method('createStream')->willReturn(true);
        $this->protocol->expects($this->any())->method('connected')->willReturn(true);
        $this->protocol->expects($this->any())->method('getConnectionTimeout')->willReturn(30);
        $this->protocol->expects($this->any())->method('logout')->willReturn(Response::empty()->setResponse([
                                                                                 0 => "BYE Logging out\r\n",
                                                                                 1 => "OK Logout completed (0.001 + 0.000 secs).\r\n",
                                                                             ]));
        $this->protocol->expects($this->any())->method('selectFolder')->willReturn(Response::empty()->setResponse([
                                                                                       "flags"       => [
                                                                                           0 => [
                                                                                               0 => "\Answered",
                                                                                               1 => "\Flagged",
                                                                                               2 => "\Deleted",
                                                                                               3 => "\Seen",
                                                                                               4 => "\Draft",
                                                                                               5 => "NonJunk",
                                                                                               6 => "unknown-1",
                                                                                           ],
                                                                                       ],
                                                                                       "exists"      => 139,
                                                                                       "recent"      => 0,
                                                                                       "unseen"      => 94,
                                                                                       "uidvalidity" => 1488899637,
                                                                                       "uidnext"     => 278,
                                                                                   ]));

        $this->client->connection = $this->protocol;
    }
}

class QueryFolderProbe extends WhereQuery {

    public function makeMessage(int $uid, int $msglist, string $header, string $content, array $flags): ?Message {
        return $this->make($uid, $msglist, $header, $content, $flags);
    }
}
