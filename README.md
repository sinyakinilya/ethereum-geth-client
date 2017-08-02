Simple Client for Ethereum(geth)
=======================

Client use guzzlehttp/guzzle;

All method [json-rpc-methods](https://github.com/ethereum/wiki/wiki/JSON-RPC#json-rpc-methods)

Example:
```php
include_once 'vendor/autoload.php';

use SinyakinIlya\Ethereum\Client;
use SinyakinIlya\Ethereum\Helper\Hash;

$walletAccount = '0x...';

//geth must be run with params: geth --rpc --rpcaddr 0.0.0.0 --rpcapi admin,miner,shh,txpool,personal,eth,net,web3
$ip = '..ip..';
$port = 8545;

 
$client = new Client($ip, $port);

$lastBlock = $client->callMethod('eth_blockNumber');
$currentBockNumber = Hash::hexToNumber($lastBlock);
$toTransactions = $fromTransactions = [];

do {
    $block = $client->callMethod('eth_getBlockByNumber', [Hash::toHex($currentBockNumber), true]);
    if (!is_array($block)) {
        break;
    }
    foreach ($block['transactions'] as $transaction) {
        if ($transaction['from'] == $walletAccount) {
            $fromTransactions[$transaction['to']][] = $transaction;
        } elseif ($transaction['to'] == $walletAccount) {
            $toTransactions[$transaction['from']][] = $transaction;
        }
    }
    echo 'Process block: ', $currentBockNumber--, PHP_EOL;
} while ($currentBockNumber >= 4000000);

file_put_contents('tr.json', json_encode(['f' => $fromTransactions, 't' => $toTransactions]));
```

## Help and docs

- [Documentation](https://github.com/ethereum/wiki/wiki/JSON-RPC#json-rpc-api)


## Installing Guzzle

The recommended way to install Guzzle is through
[Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version of Guzzle:

```bash
php composer.phar require sinyakinilya/ethereum-geth-client
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```