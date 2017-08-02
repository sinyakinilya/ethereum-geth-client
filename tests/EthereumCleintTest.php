<?php
/**
 * EthereumCleintTest.php
 *
 * @author   Ilya Sinyakin <sinyakin.ilya@gmail.com>
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use SinyakinIlya\Ethereum\Client;

class EthereumClientTest extends TestCase
{
    public function testMainNet()
    {
        $client = new Client('94.130.58.86');
        $r = $client->callMethod('net_version');

        $this->assertEquals($r, 1);
    }
}
