<?php
/*
* File: idle_server.php
* Category: Test support
*
* Description:
*  Minimal fake IMAP server used by IdleTest. It mimics how rfc2177 compliant servers
*  (e.g. Dovecot) react to a command other than DONE during IDLE: the IDLE command is
*  terminated with "BAD Expected DONE". Every received command is logged as
*  "<connection>\t<idling 0|1>\t<line>" to the log file given as first argument.
*
*  Usage: php idle_server.php <log_file> <lifetime_seconds>
*  Prints the listening port on the first line of STDOUT.
*/

$log_file = $argv[1];
$lifetime = (float)($argv[2] ?? 5);

$server = stream_socket_server("tcp://127.0.0.1:0", $errno, $errstr);
if (!$server) {
    fwrite(STDERR, "cannot listen: $errstr\n");
    exit(1);
}
$name = stream_socket_get_name($server, false);
echo substr($name, strrpos($name, ":") + 1) . "\n";
flush();

$clients = [];
$state = [];
$next_id = 1;
$end = microtime(true) + $lifetime;

$send = function ($stream, string $data): void {
    @fwrite($stream, $data . "\r\n");
};

while (microtime(true) < $end) {
    $read = array_merge([$server], array_values($clients));
    $write = null;
    $except = null;
    if (@stream_select($read, $write, $except, 0, 100000) === false) {
        break;
    }
    foreach ($read as $stream) {
        if ($stream === $server) {
            $client = @stream_socket_accept($server, 0);
            if ($client) {
                $id = $next_id++;
                $clients[$id] = $client;
                $state[$id] = ["idling" => false, "idle_tag" => null];
                $send($client, "* OK [CAPABILITY IMAP4rev1 IDLE] fake server ready");
            }
            continue;
        }
        $id = array_search($stream, $clients, true);
        $line = fgets($stream);
        if ($line === false || $line === "") {
            fclose($stream);
            unset($clients[$id], $state[$id]);
            continue;
        }
        $line = rtrim($line, "\r\n");
        file_put_contents($log_file, $id . "\t" . ($state[$id]["idling"] ? 1 : 0) . "\t" . $line . "\n", FILE_APPEND);

        if ($state[$id]["idling"]) {
            $tag = $state[$id]["idle_tag"];
            $state[$id]["idling"] = false;
            if (strtoupper($line) === "DONE") {
                $send($stream, "$tag OK Idle completed.");
            } else {
                // Same reaction as Dovecot: the IDLE command ends with BAD
                $send($stream, "$tag BAD Expected DONE.");
            }
            continue;
        }

        $parts = explode(" ", $line, 3);
        $tag = $parts[0];
        $command = strtoupper($parts[1] ?? "");
        switch ($command) {
            case "CAPABILITY":
                $send($stream, "* CAPABILITY IMAP4rev1 IDLE");
                $send($stream, "$tag OK Capability completed.");
                break;
            case "SELECT":
            case "EXAMINE":
                $send($stream, "* FLAGS (\\Answered \\Flagged \\Deleted \\Seen \\Draft)");
                $send($stream, "* 0 EXISTS");
                $send($stream, "* 0 RECENT");
                $send($stream, "* OK [UIDVALIDITY 1] UIDs valid");
                $send($stream, "* OK [UIDNEXT 1] Predicted next UID");
                $send($stream, "$tag OK [READ-WRITE] Select completed.");
                break;
            case "IDLE":
                $state[$id]["idling"] = true;
                $state[$id]["idle_tag"] = $tag;
                $send($stream, "+ idling");
                break;
            case "LOGOUT":
                $send($stream, "* BYE Logging out");
                $send($stream, "$tag OK Logout completed.");
                fclose($stream);
                unset($clients[$id], $state[$id]);
                break;
            default:
                $send($stream, "$tag OK $command completed.");
        }
    }
}

foreach ($clients as $client) {
    fclose($client);
}
fclose($server);
