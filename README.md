# web3.php

[![Tests](https://github.com/GryfOSS/web3.php/workflows/Tests/badge.svg)](https://github.com/GryfOSS/web3.php/actions)
[![Code Coverage](https://img.shields.io/badge/coverage-91.8%25-brightgreen.svg)](https://github.com/GryfOSS/web3.php/actions)
[![Licensed under the MIT License](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/sc0Vu/web3.php/blob/master/LICENSE)


A maintained php interface for interacting with the Ethereum blockchain and ecosystem.

It is a modernised version of `https://github.com/iexbase/web3.php`. It includes
more unit tests, higher tests coverage, functional tests, uses phpseclib3 and
solves few bugs.

# Install

```
composer require gryfoss/web3.php
```

# Usage

### New instance
```php
use Web3\Web3;

$web3 = new Web3('http://localhost:8545');
```

### Using provider
```php
use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;

$web3 = new Web3(new HttpProvider(new HttpRequestManager('http://localhost:8545')));

// timeout
$web3 = new Web3(new HttpProvider(new HttpRequestManager('http://localhost:8545', 0.1)));
```

### You can use callback to each rpc call:
```php
$web3->clientVersion(function ($err, $version) {
    if ($err !== null) {
        // do something
        return;
    }
    if (isset($version)) {
        echo 'Client version: ' . $version;
    }
});
```

### Eth
```php
use Web3\Web3;

$web3 = new Web3('http://localhost:8545');
$eth = $web3->eth;
```

Or

```php
use Web3\Eth;

$eth = new Eth('http://localhost:8545');
```

### Net
```php
use Web3\Web3;

$web3 = new Web3('http://localhost:8545');
$net = $web3->net;
```

Or

```php
use Web3\Net;

$net = new Net('http://localhost:8545');
```

### Batch

web3
```php
$web3->batch(true);
$web3->clientVersion();
$web3->hash('0x1234');
$web3->execute(function ($err, $data) {
    if ($err !== null) {
        // do something
        // it may throw exception or array of exception depends on error type
        // connection error: throw exception
        // json rpc error: array of exception
        return;
    }
    // do something
});
```

eth

```php
$eth->batch(true);
$eth->protocolVersion();
$eth->syncing();

$eth->provider->execute(function ($err, $data) {
    if ($err !== null) {
        // do something
        return;
    }
    // do something
});
```

net
```php
$net->batch(true);
$net->version();
$net->listening();

$net->provider->execute(function ($err, $data) {
    if ($err !== null) {
        // do something
        return;
    }
    // do something
});
```

personal
```php
$personal->batch(true);
$personal->listAccounts();
$personal->newAccount('123456');

$personal->provider->execute(function ($err, $data) {
    if ($err !== null) {
        // do something
        return;
    }
    // do something
});
```

### Contract

```php
use Web3\Contract;

$contract = new Contract('http://localhost:8545', $abi);

// deploy contract
$contract->bytecode($bytecode)->new($params, $callback);

// call contract function
$contract->at($contractAddress)->call($functionName, $params, $callback);

// change function state
$contract->at($contractAddress)->send($functionName, $params, $callback);

// estimate deploy contract gas
$contract->bytecode($bytecode)->estimateGas($params, $callback);

// estimate function gas
$contract->at($contractAddress)->estimateGas($functionName, $params, $callback);

// get constructor data
$constructorData = $contract->bytecode($bytecode)->getData($params);

// get function data
$functionData = $contract->at($contractAddress)->getData($functionName, $params);
```

# Assign value to outside scope(from callback scope to outside scope)
Due to callback is not like javascript callback,
if we need to assign value to outside scope,
we need to assign reference to callback.
```php
$newAccount = '';

$web3->personal->newAccount('123456', function ($err, $account) use (&$newAccount) {
    if ($err !== null) {
        echo 'Error: ' . $err->getMessage();
        return;
    }
    $newAccount = $account;
    echo 'New account: ' . $account . PHP_EOL;
});
```

### Testing

Ganache cli interface is provided as a docker container in docker/ folder. Go there
and run `docker compose up -d` to run it. Then run tests using `run-tests.sh`.
It runs both PHPUnit and functional tests via Behat.

# License

MIT
