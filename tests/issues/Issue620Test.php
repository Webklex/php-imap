<?php
/*
* File: Issue620Test.php
* Category: Test
* Author: -
* Created: 20.08.26 12:00
* Updated: -
*
* Description:
*  -
*/

namespace Tests\issues;

use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Query\Query;

class Issue620Test extends TestCase {

    /**
     * Test that a query picks up options.fetch_body and options.fetch_flags.
     */
    public function testFetchBodyAndFlagsAreTakenFromConfig(): void {
        $cm = new ClientManager([
            'options' => [
                'fetch_body'  => false,
                'fetch_flags' => false,
            ],
        ]);

        $query = new Query($cm->make([]));

        self::assertFalse($query->getFetchBody());
        self::assertFalse($query->getFetchFlags());
    }

    /**
     * Test that both options keep defaulting to true.
     */
    public function testFetchBodyAndFlagsDefaultToTrue(): void {
        $cm = new ClientManager();

        $query = new Query($cm->make([]));

        self::assertTrue($query->getFetchBody());
        self::assertTrue($query->getFetchFlags());
    }
}
