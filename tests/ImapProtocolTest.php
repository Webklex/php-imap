<?php
/*
* File: ImapProtocolTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 28.12.22 18:11
* Updated: -
*
* Description:
*  -
*/

namespace Tests;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Connection\Protocols\ImapProtocol;
use Webklex\PHPIMAP\Connection\Protocols\Response;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\IMAP;

class ImapProtocolTest extends TestCase {

    /** @var Config $config */
    protected Config $config;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        $this->config = Config::make();
    }


    /**
     * ImapProtocol test
     *
     * @return void
     */
    public function testImapProtocol(): void {

        $protocol = new ImapProtocol($this->config, false);
        self::assertSame(false, $protocol->getCertValidation());
        self::assertSame("", $protocol->getEncryption());

        $protocol->setCertValidation(true);
        $protocol->setEncryption("ssl");

        self::assertSame(true, $protocol->getCertValidation());
        self::assertSame("ssl", $protocol->getEncryption());

        $protocol->setSslOptions([
            'verify_peer' => true,
            'cafile' => '/dummy/path/for/testing',
            'peer_fingerprint' => ['md5' => 40],
        ]);

        self::assertSame([
            'verify_peer' => true,
            'cafile' => '/dummy/path/for/testing',
            'peer_fingerprint' => ['md5' => 40],
        ], $protocol->getSslOptions());
    }


    /**
     * Section identifiers like BODY[HEADER.FIELDS (Subject)] contain spaces
     * inside the brackets. The tokenizer must capture the entire bracketed
     * section as a single atom rather than splitting on the inner space.
     *
     * A real-world FETCH response uses a literal ({N}\r\n<bytes>) for the
     * payload; here we use a bare atom so the test can exercise just the
     * tokenizer in isolation.
     */
    public function testDecodeLinePreservesSpacesInsideBrackets(): void {
        $protocol = $this->makeDecodingProtocol();
        $response = new Response(0);
        $line = '* 5 FETCH (BODY[HEADER.FIELDS (Subject)] PAYLOAD)';
        $tokens = $protocol->callDecodeLine($response, $line);

        // Expect: [ '*', '5', 'FETCH', [ 'BODY[HEADER.FIELDS (Subject)]', 'PAYLOAD' ] ]
        self::assertSame('*', $tokens[0]);
        self::assertSame('5', $tokens[1]);
        self::assertSame('FETCH', $tokens[2]);
        self::assertIsArray($tokens[3]);
        self::assertSame('BODY[HEADER.FIELDS (Subject)]', $tokens[3][0]);
        self::assertSame('PAYLOAD', $tokens[3][1]);
    }


    /**
     * BODY[HEADER] (no inner space) should still tokenize as a single atom.
     * Regression guard against the bracket-balance fix breaking the simple case.
     */
    public function testDecodeLineHandlesPlainBracketSection(): void {
        $protocol = $this->makeDecodingProtocol();
        $response = new Response(0);
        $line = '* 7 FETCH (BODY[HEADER] PAYLOAD)';
        $tokens = $protocol->callDecodeLine($response, $line);

        self::assertSame('*', $tokens[0]);
        self::assertSame('7', $tokens[1]);
        self::assertSame('FETCH', $tokens[2]);
        self::assertIsArray($tokens[3]);
        self::assertSame('BODY[HEADER]', $tokens[3][0]);
        self::assertSame('PAYLOAD', $tokens[3][1]);
    }


    /**
     * Multiple HEADER.FIELDS items in a multisection fetch response should
     * each tokenize as one atom, with their payloads following.
     */
    public function testDecodeLinePreservesMultipleBracketedSections(): void {
        $protocol = $this->makeDecodingProtocol();
        $response = new Response(0);
        $line = '* 9 FETCH (BODY[HEADER.FIELDS (Subject)] PAY1 BODY[HEADER.FIELDS (Message-Id)] PAY2)';
        $tokens = $protocol->callDecodeLine($response, $line);

        self::assertIsArray($tokens[3]);
        self::assertSame('BODY[HEADER.FIELDS (Subject)]', $tokens[3][0]);
        self::assertSame('PAY1', $tokens[3][1]);
        self::assertSame('BODY[HEADER.FIELDS (Message-Id)]', $tokens[3][2]);
        self::assertSame('PAY2', $tokens[3][3]);
    }


    /**
     * Some servers (e.g., Dovecot) normalize HEADER.FIELDS field names to
     * uppercase in the response (legal per RFC 3501 since header field
     * names are case-insensitive). The fetch() comparison must therefore
     * match case-insensitively or queries silently miss.
     *
     * Drives a mocked end-to-end fetch round-trip with a request whose
     * field name (Message-Id) differs in case from the response (MESSAGE-ID).
     */
    public function testFetchMatchesCaseInsensitiveHeaderFieldNames(): void {
        $payload = "Message-ID: <abc@example.com>\r\n\r\n";
        $len = strlen($payload);

        $mock = new class($this->config, false) extends ImapProtocol {
            /** @var list<string> */
            public array $lines = [];
            private int $pos = 0;
            public function nextLine(Response $response): string {
                $line = $this->lines[$this->pos++];
                $response->addResponse($line);
                return $line;
            }
            public function sendRequest(string $command, array $tokens = [], ?string &$tag = null): Response {
                $tag = 'TAG1';
                return Response::make(1, [], [], false);
            }
        };
        $mock->lines = [
            "* 5 FETCH (UID 42 BODY[HEADER.FIELDS (MESSAGE-ID)] {{$len}}\r\n",
            $payload . ")\r\n",
            "TAG1 OK FETCH completed\r\n",
        ];

        $r = $mock->fetch(['BODY.PEEK[HEADER.FIELDS (Message-Id)]'], [42], null, IMAP::ST_UID);
        $data = $r->validatedData();

        self::assertIsArray($data);
        self::assertArrayHasKey(42, $data);
        self::assertSame($payload, $data[42]);
    }


    private function makeDecodingProtocol(): ImapProtocol {
        return new class($this->config, false) extends ImapProtocol {
            public function callDecodeLine(Response $response, string $line): array {
                return $this->decodeLine($response, $line);
            }
        };
    }
}