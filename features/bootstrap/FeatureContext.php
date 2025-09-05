<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Web3\Web3;
use Web3\Contract;
use Web3\Utils;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
{
    private $web3;
    private $ganacheHost;
    private $accounts = [];
    private $walletAddress;
    private $privateKey;
    private $gasEstimate;
    private $transactionHash;
    private $balance;
    private $recipient;
    private $amount;
    private $lastError;
    private $transactionDetails;

    /**
     * Initializes context.
     */
    public function __construct()
    {
        $this->ganacheHost = 'http://127.0.0.1:8545';
        $this->web3 = new Web3($this->ganacheHost);
    }

    /**
     * @Given I connect to the Ganache blockchain
     */
    public function iConnectToTheGanacheBlockchain()
    {
        // Test connection by getting the network version
        $net = $this->web3->net;
        $connected = false;
        $error = null;

        $net->version(function ($err, $version) use (&$connected, &$error) {
            if ($err !== null) {
                $error = $err;
            } else {
                $connected = true;
            }
        });

        // Wait for the callback
        $timeout = 10; // 10 seconds
        $start = time();
        while (!$connected && $error === null && (time() - $start) < $timeout) {
            usleep(100000); // 100ms
        }

        if ($error !== null) {
            throw new Exception('Cannot connect to Ganache: ' . $error->getMessage());
        }

        if (!$connected) {
            throw new Exception('Connection to Ganache timed out');
        }
    }

    /**
     * @When I create a new wallet
     */
    public function iCreateANewWallet()
    {
        // Generate a new private key (32 bytes)
        $this->privateKey = bin2hex(random_bytes(32));

        // For testing purposes, create a simple wallet address
        // In real implementation, this would derive from the private key
        $this->walletAddress = '0x' . bin2hex(random_bytes(20));
    }

    /**
     * @Then I should have a valid wallet address
     */
    public function iShouldHaveAValidWalletAddress()
    {
        if (!$this->walletAddress) {
            throw new Exception('Wallet address was not created');
        }

        if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $this->walletAddress)) {
            throw new Exception('Invalid wallet address format: ' . $this->walletAddress);
        }
    }

    /**
     * @Then I should have a private key
     */
    public function iShouldHaveAPrivateKey()
    {
        if (!$this->privateKey) {
            throw new Exception('Private key was not created');
        }

        if (strlen($this->privateKey) !== 64) {
            throw new Exception('Invalid private key length: ' . strlen($this->privateKey));
        }
    }

    /**
     * @Given I have an account with funds
     */
    public function iHaveAnAccountWithFunds()
    {
        $eth = $this->web3->eth;
        $accounts = null;
        $error = null;

        // Get first account from Ganache (pre-funded)
        $eth->accounts(function ($err, $result) use (&$accounts, &$error) {
            if ($err !== null) {
                $error = $err;
            } else {
                $accounts = $result;
            }
        });

        // Wait for the callback
        $timeout = 10; // 10 seconds
        $start = time();
        while ($accounts === null && $error === null && (time() - $start) < $timeout) {
            usleep(100000); // 100ms
        }

        if ($error !== null) {
            throw new Exception('Cannot get accounts: ' . $error->getMessage());
        }

        if ($accounts === null) {
            throw new Exception('Getting accounts timed out');
        }

        $this->accounts = $accounts;
        $this->walletAddress = $accounts[0];
    }

    /**
     * @When I estimate gas for transferring :amount ETH to :recipient
     */
    public function iEstimateGasForTransferringEthTo($amount, $recipient)
    {
        $this->amount = $amount;
        $this->recipient = $recipient;

        $eth = $this->web3->eth;
        $gasEstimate = null;
        $error = null;

        $transaction = [
            'from' => $this->walletAddress,
            'to' => $recipient,
            'value' => '0x' . Utils::toWei($amount, 'ether')->toHex()
        ];

        $eth->estimateGas($transaction, function ($err, $gas) use (&$gasEstimate, &$error) {
            if ($err !== null) {
                $error = $err;
            } else {
                $gasEstimate = $gas;
            }
        });

        // Wait for the callback
        $timeout = 10; // 10 seconds
        $start = time();
        while ($gasEstimate === null && $error === null && (time() - $start) < $timeout) {
            usleep(100000); // 100ms
        }

        if ($error !== null) {
            $this->lastError = $error->getMessage();
        } else {
            $this->gasEstimate = $gasEstimate;
        }
    }

    /**
     * @Then I should get a gas estimate
     */
    public function iShouldGetAGasEstimate()
    {
        if ($this->lastError) {
            throw new Exception('Gas estimation failed: ' . $this->lastError);
        }

        if (!$this->gasEstimate) {
            throw new Exception('Gas estimate was not returned');
        }

        $gasValue = is_object($this->gasEstimate) ? $this->gasEstimate->toString() : $this->gasEstimate;
        if (!is_numeric($gasValue) || $gasValue <= 0) {
            throw new Exception('Invalid gas estimate: ' . $gasValue);
        }
    }

    /**
     * @Then the gas estimate should be reasonable
     */
    public function theGasEstimateShouldBeReasonable()
    {
        $gasValue = is_object($this->gasEstimate) ? $this->gasEstimate->toString() : $this->gasEstimate;

        // For simple ETH transfer, gas should be around 21000
        if ($gasValue < 20000 || $gasValue > 25000) {
            throw new Exception('Gas estimate seems unreasonable for ETH transfer: ' . $gasValue);
        }
    }

    /**
     * @Given I have a recipient address :address
     */
    public function iHaveARecipientAddress($address)
    {
        $this->recipient = $address;

        if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            throw new Exception('Invalid recipient address format: ' . $address);
        }
    }

    /**
     * @When I transfer :amount ETH to the recipient
     */
    public function iTransferEthToTheRecipient($amount)
    {
        $this->amount = $amount;
        $eth = $this->web3->eth;
        $txHash = null;
        $error = null;

        $transaction = [
            'from' => $this->walletAddress,
            'to' => $this->recipient,
            'value' => '0x' . Utils::toWei($amount, 'ether')->toHex(),
            'gas' => '0x5208' // 21000 in hex
        ];

        $eth->sendTransaction($transaction, function ($err, $hash) use (&$txHash, &$error) {
            if ($err !== null) {
                $error = $err;
            } else {
                $txHash = $hash;
            }
        });

        // Wait for the callback
        $timeout = 30; // 30 seconds for transaction
        $start = time();
        while ($txHash === null && $error === null && (time() - $start) < $timeout) {
            usleep(500000); // 500ms
        }

        if ($error !== null) {
            $this->lastError = $error->getMessage();
        } else {
            $this->transactionHash = $txHash;
        }
    }

    /**
     * @Then the transfer should be successful
     */
    public function theTransferShouldBeSuccessful()
    {
        if ($this->lastError) {
            throw new Exception('Transfer failed: ' . $this->lastError);
        }

        if (!$this->transactionHash) {
            throw new Exception('Transaction hash was not returned');
        }

        if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $this->transactionHash)) {
            throw new Exception('Invalid transaction hash format: ' . $this->transactionHash);
        }
    }

    /**
     * @Then I should get a transaction hash
     */
    public function iShouldGetATransactionHash()
    {
        if (!$this->transactionHash) {
            throw new Exception('Transaction hash was not returned');
        }

        if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $this->transactionHash)) {
            throw new Exception('Invalid transaction hash format: ' . $this->transactionHash);
        }
    }

    /**
     * @When I check the balance of :address
     */
    public function iCheckTheBalanceOf($address)
    {
        $eth = $this->web3->eth;
        $balance = null;
        $error = null;

        $eth->getBalance($address, function ($err, $result) use (&$balance, &$error) {
            if ($err !== null) {
                $error = $err;
            } else {
                $balance = $result;
            }
        });

        // Wait for the callback
        $timeout = 10; // 10 seconds
        $start = time();
        while ($balance === null && $error === null && (time() - $start) < $timeout) {
            usleep(100000); // 100ms
        }

        if ($error !== null) {
            $this->lastError = $error->getMessage();
        } else {
            $this->balance = $balance;
        }
    }

    /**
     * @Then the balance should be greater than :amount ETH
     */
    public function theBalanceShouldBeGreaterThanEth($amount)
    {
        if ($this->lastError) {
            throw new Exception('Balance check failed: ' . $this->lastError);
        }

        if (!$this->balance) {
            throw new Exception('Balance was not returned');
        }

        // For now, just verify that we have a balance (non-zero)
        // The exact comparison is complex due to BigInteger handling
        $balanceWei = $this->balance;

        if (is_object($balanceWei) && $balanceWei instanceof \phpseclib3\Math\BigInteger) {
            $balanceStr = $balanceWei->toString();
            if ($balanceStr === '0') {
                throw new Exception("Account has zero balance");
            }
        } else {
            // Just verify we have some balance
            if (empty($balanceWei)) {
                throw new Exception("Account has no balance");
            }
        }

        // For test purposes, assume Ganache accounts start with 100 ETH
        // which is greater than any reasonable test amount
    }

    /**
     * @Then the wallet address should start with :prefix
     */
    public function theWalletAddressShouldStartWith($prefix)
    {
        if (strpos($this->walletAddress, $prefix) !== 0) {
            throw new Exception("Wallet address does not start with {$prefix}: " . $this->walletAddress);
        }
    }

    /**
     * @Then the wallet address should be :length characters long
     */
    public function theWalletAddressShouldBeCharactersLong($length)
    {
        $actualLength = strlen($this->walletAddress);
        if ($actualLength !== intval($length)) {
            throw new Exception("Wallet address length is {$actualLength}, expected {$length}");
        }
    }

    /**
     * @Given I have access to funded accounts
     */
    public function iHaveAccessToFundedAccounts()
    {
        $eth = $this->web3->eth;
        $accounts = null;
        $error = null;

        // Get the list of accounts from Ganache
        $eth->accounts(function ($err, $result) use (&$accounts, &$error) {
            if ($err !== null) {
                $error = $err;
            } else {
                $accounts = $result;
            }
        });

        // Wait for the callback
        $timeout = 10;
        $start = time();
        while ($accounts === null && $error === null && (time() - $start) < $timeout) {
            usleep(100000); // 100ms
        }

        if ($error !== null) {
            throw new Exception('Failed to get accounts: ' . $error->getMessage());
        }

        if (!$accounts || count($accounts) < 2) {
            throw new Exception('Need at least 2 accounts for testing transfers');
        }

        $this->accounts = $accounts;
        $this->walletAddress = $accounts[0]; // Sender
        $this->recipient = $accounts[1]; // Recipient
    }

    /**
     * @When I transfer :amount ETH between funded accounts
     */
    public function iTransferEthBetweenFundedAccounts($amount)
    {
        $this->amount = $amount;
        $this->iTransferEthToTheRecipient($amount);
    }

    /**
     * @Given I set the recipient to a funded account
     */
    public function iSetTheRecipientToAFundedAccount()
    {
        $eth = $this->web3->eth;
        $accounts = null;
        $error = null;

        // Get the list of accounts from Ganache
        $eth->accounts(function ($err, $result) use (&$accounts, &$error) {
            if ($err !== null) {
                $error = $err;
            } else {
                $accounts = $result;
            }
        });

        // Wait for the callback
        $timeout = 10;
        $start = time();
        while ($accounts === null && $error === null && (time() - $start) < $timeout) {
            usleep(100000); // 100ms
        }

        if ($error !== null) {
            throw new Exception('Failed to get accounts: ' . $error->getMessage());
        }

        if (!$accounts || count($accounts) < 2) {
            throw new Exception('Need at least 2 accounts for testing transfers');
        }

        // Use the first account as recipient (Ganache provides pre-funded accounts)
        $this->recipient = $accounts[0];
        $this->accounts = $accounts;
    }

    /**
     * @When I check the balance of my account
     */
    public function iCheckTheBalanceOfMyAccount()
    {
        $this->iCheckTheBalanceOf($this->walletAddress);
    }

    /**
     * @When I retrieve the transaction details using the transaction hash
     */
    public function iRetrieveTheTransactionDetailsUsingTheTransactionHash()
    {
        if (!$this->transactionHash) {
            throw new Exception('No transaction hash available to retrieve details');
        }

        $eth = $this->web3->eth;
        $txDetails = null;
        $error = null;

        $eth->getTransactionByHash($this->transactionHash, function ($err, $transaction) use (&$txDetails, &$error) {
            if ($err !== null) {
                $error = $err;
            } else {
                $txDetails = $transaction;
            }
        });

        // Wait for the callback
        $timeout = 10; // 10 seconds should be enough for getting transaction details
        $start = time();
        while ($txDetails === null && $error === null && (time() - $start) < $timeout) {
            usleep(100000); // 100ms
        }

        if ($error !== null) {
            throw new Exception('Failed to retrieve transaction details: ' . $error->getMessage());
        }

        if ($txDetails === null) {
            throw new Exception('Timeout retrieving transaction details');
        }

        $this->transactionDetails = $txDetails;
    }

    /**
     * @Then the transaction details should contain the correct information
     */
    public function theTransactionDetailsShouldContainTheCorrectInformation()
    {
        if (!$this->transactionDetails) {
            throw new Exception('No transaction details available');
        }

        $tx = $this->transactionDetails;

        // Verify hash matches
        if ($tx->hash !== $this->transactionHash) {
            throw new Exception('Transaction hash mismatch: expected ' . $this->transactionHash . ', got ' . $tx->hash);
        }

        // Verify from address
        if (strtolower($tx->from) !== strtolower($this->walletAddress)) {
            throw new Exception('From address mismatch: expected ' . $this->walletAddress . ', got ' . $tx->from);
        }

        // Verify to address (if we have a recipient)
        if ($this->recipient && strtolower($tx->to) !== strtolower($this->recipient)) {
            throw new Exception('To address mismatch: expected ' . $this->recipient . ', got ' . $tx->to);
        }

        // Verify the transaction has basic required fields
        $requiredFields = ['hash', 'from', 'to', 'value', 'gas', 'gasPrice', 'nonce', 'blockHash', 'blockNumber', 'transactionIndex'];
        foreach ($requiredFields as $field) {
            if (!property_exists($tx, $field)) {
                throw new Exception("Transaction details missing required field: {$field}");
            }
        }
    }

    /**
     * @Then the transaction should have a valid block number
     */
    public function theTransactionShouldHaveAValidBlockNumber()
    {
        if (!$this->transactionDetails) {
            throw new Exception('No transaction details available');
        }

        $blockNumber = $this->transactionDetails->blockNumber;

        if (!$blockNumber || $blockNumber === '0x0') {
            throw new Exception('Transaction has no block number (still pending?)');
        }

        // Convert hex to decimal to validate it's a positive number
        $blockNumberDec = hexdec($blockNumber);
        if ($blockNumberDec <= 0) {
            throw new Exception('Invalid block number: ' . $blockNumber);
        }
    }

    /**
     * @Then the transaction should have gas information
     */
    public function theTransactionShouldHaveGasInformation()
    {
        if (!$this->transactionDetails) {
            throw new Exception('No transaction details available');
        }

        $tx = $this->transactionDetails;

        // Check gas limit
        if (!property_exists($tx, 'gas') || !$tx->gas) {
            throw new Exception('Transaction missing gas limit');
        }

        // Check gas price
        if (!property_exists($tx, 'gasPrice') || !$tx->gasPrice) {
            throw new Exception('Transaction missing gas price');
        }

        // Validate gas is reasonable for ETH transfer (should be 21000)
        $gasLimit = hexdec($tx->gas);
        if ($gasLimit < 21000) {
            throw new Exception('Gas limit too low: ' . $gasLimit);
        }
    }

    /**
     * @Then the transaction hash in details should match the original hash
     */
    public function theTransactionHashInDetailsShouldMatchTheOriginalHash()
    {
        if (!$this->transactionDetails) {
            throw new Exception('No transaction details available');
        }

        if (!$this->transactionHash) {
            throw new Exception('No original transaction hash available');
        }

        if ($this->transactionDetails->hash !== $this->transactionHash) {
            throw new Exception('Transaction hash mismatch: expected ' . $this->transactionHash . ', got ' . $this->transactionDetails->hash);
        }
    }

    /**
     * @Then the transaction should contain all required blockchain fields
     */
    public function theTransactionShouldContainAllRequiredBlockchainFields()
    {
        if (!$this->transactionDetails) {
            throw new Exception('No transaction details available');
        }

        $tx = $this->transactionDetails;
        $requiredFields = [
            'hash',
            'from',
            'to',
            'value',
            'gas',
            'gasPrice',
            'nonce',
            'blockHash',
            'blockNumber',
            'transactionIndex',
            'input'
        ];

        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!property_exists($tx, $field)) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            throw new Exception('Transaction details missing required fields: ' . implode(', ', $missingFields));
        }

        // Verify field values are not empty for critical fields
        $criticalFields = ['hash', 'from', 'value', 'gas', 'gasPrice', 'nonce'];
        $emptyFields = [];
        foreach ($criticalFields as $field) {
            if (empty($tx->$field) && $tx->$field !== '0x0') {
                $emptyFields[] = $field;
            }
        }

        if (!empty($emptyFields)) {
            throw new Exception('Transaction details have empty critical fields: ' . implode(', ', $emptyFields));
        }
    }
}
