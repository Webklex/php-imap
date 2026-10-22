<?php
/*
* File: IdleTest.php
* Category: -
*
* Description:
*  Folder::idle() must not send any command other than DONE while the session is in IDLE.
*  rfc2177 compliant servers (e.g. Dovecot) answer "BAD Expected DONE" and terminate the
*  IDLE - new messages would then never be reported. Client::getConnection() verifies the
*  session with a NOOP, so it must not be called inside the idle loop.
*/

namespace Tests;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Folder;

class IdleTest extends TestCase {

    /** @var resource|null */
    protected $server = null;

    protected string $log_file = "";

    protected function tearDown(): void {
        if (is_resource($this->server)) {
            proc_terminate($this->server);
            proc_close($this->server);
        }
        if ($this->log_file !== "" && file_exists($this->log_file)) {
            unlink($this->log_file);
        }
    }

    /**
     * Start the fake IMAP server and return its port
     * @param int $lifetime seconds until the server shuts down
     *
     * @return int
     */
    protected function startServer(int $lifetime): int {
        $this->log_file = tempnam(sys_get_temp_dir(), "php-imap-idle-");
        $command = [PHP_BINARY, __DIR__ . "/support/idle_server.php", $this->log_file, (string)$lifetime];
        $this->server = proc_open($command, [1 => ["pipe", "w"], 2 => ["pipe", "w"]], $pipes);
        self::assertIsResource($this->server, "fake IMAP server could not be started");
        $port = (int)trim((string)fgets($pipes[1]));
        self::assertGreaterThan(0, $port, "fake IMAP server did not report its port");
        return $port;
    }

    /**
     * Commands received by the fake server
     *
     * @return array<int, array{connection: int, idling: bool, line: string}>
     */
    protected function receivedCommands(): array {
        $commands = [];
        foreach (file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $row) {
            [$connection, $idling, $line] = explode("\t", $row, 3);
            $commands[] = ["connection" => (int)$connection, "idling" => $idling === "1", "line" => $line];
        }
        return $commands;
    }

    public function testIdleDoesNotSendCommandsDuringIdle(): void {
        $port = $this->startServer(5);

        $manager = new ClientManager([
            "accounts" => [
                "default" => [
                    "host"          => "127.0.0.1",
                    "port"          => $port,
                    "encryption"    => false,
                    "validate_cert" => false,
                    "username"      => "user",
                    "password"      => "secret",
                    "protocol"      => "imap",
                ],
            ],
        ]);
        $client = $manager->account("default");
        $client->connect();
        $folder = new Folder($client, "INBOX", "/", []);

        try {
            // Third argument (max_runtime) is ignored by versions without it - the loop then
            // ends with an exception once the fake server shuts down.
            $folder->idle(function () {}, 1, 3);
        } catch (\Throwable) {
            // the server went away - only the recorded commands matter
        }

        $commands = $this->receivedCommands();
        self::assertNotEmpty($commands);

        $during_idle = array_values(array_filter($commands, fn($c) => $c["idling"] && strtoupper($c["line"]) !== "DONE"));
        self::assertSame([], $during_idle, "commands sent while the session was in IDLE");

        $first_idle = null;
        foreach ($commands as $index => $command) {
            if (!$command["idling"] && str_ends_with(strtoupper($command["line"]), " IDLE")) {
                $first_idle = $index;
                break;
            }
        }
        self::assertNotNull($first_idle, "IDLE was never issued");

        // A login after IDLE was issued means the idle session was silently replaced
        $later_logins = array_filter(array_slice($commands, $first_idle), fn($c) => str_contains(strtoupper($c["line"]), " LOGIN "));
        self::assertSame([], array_values($later_logins), "the idle session was silently reconnected");
    }
}
