<?php
/*
* File: LiveMailboxTestCase.php
* Category: -
* Author: M.Goldenbaum
* Created: 04.03.23 03:43
* Updated: -
*
* Description:
*  -
*/

namespace Tests;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;
use Webklex\PHPIMAP\Exceptions\EventNotFoundException;
use Webklex\PHPIMAP\Exceptions\ImapBadRequestException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;
use Webklex\PHPIMAP\Exceptions\InvalidMessageDateException;
use Webklex\PHPIMAP\Exceptions\MaskNotFoundException;
use Webklex\PHPIMAP\Exceptions\MessageContentFetchingException;
use Webklex\PHPIMAP\Exceptions\MessageFlagException;
use Webklex\PHPIMAP\Exceptions\MessageHeaderFetchingException;
use Webklex\PHPIMAP\Exceptions\ResponseException;
use Webklex\PHPIMAP\Exceptions\RuntimeException;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;

/**
 * Class LiveMailboxTestCase
 *
 * @package Tests
 */
abstract class LiveMailboxTestCase extends TestCase {
    const SPECIAL_CHARS = 'A_\\|!"£$%&()=?àèìòùÀÈÌÒÙ<>-@#[]_ß_б_π_€_✔_你_يد_Z_';

    /**
     * Client manager
     * @var ClientManager $manager
     */
    protected static ClientManager $manager;

    /**
     * Get the client manager
     *
     * @return ClientManager
     */
    final protected function getManager(): ClientManager {
        if (!isset(self::$manager)) {
            self::$manager = new ClientManager([
                'options' => [
                    "debug" => $_ENV["LIVE_MAILBOX_DEBUG"] ?? false,
                ],
                'accounts' => [
                    'default' => [
                        'host'          => getenv("LIVE_MAILBOX_HOST"),
                        'port'          => getenv("LIVE_MAILBOX_PORT"),
                        'encryption'    => getenv("LIVE_MAILBOX_ENCRYPTION"),
                        'validate_cert' => getenv("LIVE_MAILBOX_VALIDATE_CERT"),
                        'username'      => getenv("LIVE_MAILBOX_USERNAME"),
                        'password'      => getenv("LIVE_MAILBOX_PASSWORD"),
                        'protocol'      => 'imap', //might also use imap, [pop3 or nntp (untested)]
                    ],
                ],
            ]);
        }
        return self::$manager;
    }

    /**
     * Get the client
     *
     * @return Client
     * @throws MaskNotFoundException
     */
    final protected function getClient(): Client {
        return $this->getManager()->account('default');
    }

    final protected function getSpecialChars(): string {
        return self::SPECIAL_CHARS;
    }

    /**
     * Append a message to a folder
     * @param Folder $folder
     * @param string $message
     *
     * @return Message
     * @throws AuthFailedException
     * @throws ConnectionFailedException
     * @throws EventNotFoundException
     * @throws ImapBadRequestException
     * @throws ImapServerErrorException
     * @throws InvalidMessageDateException
     * @throws MessageContentFetchingException
     * @throws MessageFlagException
     * @throws MessageHeaderFetchingException
     * @throws ResponseException
     * @throws RuntimeException
     */
    final protected function appendMessage(Folder $folder, string $message): Message {
        $response = $folder->appendMessage($message);

        if (!isset($response[0])) {
            $this->fail("No message ID returned");
        }
        $test = explode(' ', $response[0]);
        if (!isset($test[3])) {
            $this->fail("No message ID returned");
        }

        $id = substr($test[3], 0, -1);
        return $folder->messages()->getMessageByUid(intval($id));
    }
}