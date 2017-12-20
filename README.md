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

Example: send transaction with calling smart-contract function
```php
include_once 'vendor/autoload.php';

use SinyakinIlya\Ethereum\Client;
use SinyakinIlya\Ethereum\Helper\Hash;
use SinyakinIlya\Ethereum\Helper\SmartContract;

$ip           = '0.0.0.0';             // geth address 
$addressSC    = '0x0c328e06....';
$ownerAddress = '0x978bEE7F....';
$ABI          = 'sc-abi.json';         // file with ABI for smart-contract

$senderAddress = '0x5237BC08b2FE....';
$senderPassPhrase = '..passphrase..';

$client = new Client($ip);
$sc     = new SmartContract($client);
$sc->setAddress($addressSC);
$sc->setContractInterface(file_get_contents($ABI));


function sendTransferFrom($from, $txData, $passphrase)
{
    global $sc, $client;
    $functionSignature = 'transferFrom(address,address,uint256)';     //this is string you get from $sc->getFunctions();
    $inputData = $sc->createSandRawData($functionSignature, $txData);
    $params = new stdClass;
    $params->from = $from;
    $params->to = $sc->getAddress();
    $params->gas = Hash::toHex(1000000);
    $params->gasPrice = $client->callMethod('eth_gasPrice');
    $params->value = Hash::toHex(0);
    $params->data = $inputData[$functionSignature];

    return $client->callMethod('personal_sendTransaction', [$params, $passphrase]);
}

$data = [
    $ownerAddress,   // from address
    $senderAddress,  // to adderss
    12345            // amount tokens
];

$txHash = sendTransferFrom($senderAddress, $data, $senderPassPhrase);
echo $txHash, PHP_EOL;
```
## Help and docs

- [Documentation](https://github.com/ethereum/wiki/wiki/JSON-RPC#json-rpc-api)


## Installing 

The recommended way to install Client is through
[Composer](http://getcomposer.org).

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```

Next, run the Composer command to install the latest stable version:

```bash
php composer.phar require sinyakinilya/ethereum-geth-client
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```