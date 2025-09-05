# Behat Functional Tests for Web3.php

This directory contains Behat functional tests that test the Web3.php library against a live Ganache blockchain instance.

## Test Coverage

### 1. Wallet Creation (`features/wallet_creation.feature`)
- **Create a new wallet**: Tests the ability to generate new private keys and wallet addresses
- **Wallet address format validation**: Ensures wallet addresses follow the correct Ethereum format (0x prefix, 42 characters)

### 2. Gas Estimation (`features/gas_estimation.feature`)
- **Estimate gas for ETH transfer**: Tests gas estimation for different ETH amounts (1 ETH, 0.5 ETH, 0.001 ETH)
- **Gas estimate validation**: Ensures gas estimates are reasonable for simple ETH transfers (~21000 gas)

### 3. ETH Transfers (`features/transfers.feature`)
- **Transfer ETH to another account**: Tests successful ETH transfers with transaction hash validation
- **Transfer small amounts**: Tests transfers of small ETH amounts (0.001 ETH)
- **Balance verification**: Verifies account balances are properly retrieved

### 4. Transaction Details Retrieval (`features/transaction_details.feature`)
- **Create and retrieve transaction details**: Tests creating transactions and retrieving their details by hash
- **Transaction hash validation**: Ensures retrieved transaction details match the original transaction
- **Required blockchain fields**: Validates all required transaction fields are present (hash, from, to, value, gas, etc.)
- **Block information**: Verifies transactions have valid block numbers and gas information

## Prerequisites

1. **Ganache blockchain**: The tests require a running Ganache instance
2. **Docker**: Ganache is run via Docker Compose

## Setup and Running

### 1. Start Ganache
```bash
cd docker
docker compose up -d ganache
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Run All Tests
```bash
vendor/bin/behat
```

### 4. Run Specific Feature
```bash
vendor/bin/behat features/wallet_creation.feature
vendor/bin/behat features/gas_estimation.feature
vendor/bin/behat features/transfers.feature
vendor/bin/behat features/transaction_details.feature
```

## Test Architecture

### FeatureContext Class
The `features/bootstrap/FeatureContext.php` file contains all the step definitions and handles:

- **Web3 Connection**: Connects to Ganache at `http://127.0.0.1:8545`
- **Asynchronous Callbacks**: Converts Web3.php's asynchronous callbacks to synchronous operations for testing
- **Data Type Handling**: Properly handles BigInteger objects and hex values
- **Transaction Management**: Manages ETH transfers with proper gas limits and value formatting

### Key Step Definitions
- `@Given I connect to the Ganache blockchain`: Establishes connection to the blockchain
- `@Given I have an account with funds`: Gets a pre-funded Ganache account
- `@Given I have access to funded accounts`: Gets multiple pre-funded Ganache accounts for transfers
- `@When I create a new wallet`: Generates a new wallet with private key and address
- `@When I estimate gas for transferring :amount ETH to :recipient`: Estimates gas for transactions
- `@When I transfer :amount ETH to the recipient`: Executes ETH transfers
- `@When I transfer :amount ETH between funded accounts`: Executes transfers between Ganache accounts
- `@When I retrieve the transaction details using the transaction hash`: Retrieves transaction details by hash
- `@Then I should get a gas estimate`: Validates gas estimation results
- `@Then the transfer should be successful`: Validates successful transactions
- `@Then the transaction details should contain the correct information`: Validates transaction details accuracy
- `@Then the transaction should have a valid block number`: Ensures transaction is included in a block
- `@Then the transaction should have gas information`: Validates gas-related transaction fields

## Configuration

### Behat Configuration (`behat.yml`)
```yaml
default:
    suites:
        web3_functional:
            paths:
                - '%paths.base%/features'
            contexts:
                - FeatureContext

    formatters:
        pretty:
            verbose:  true
            paths:    false
            snippets: false
```

### Ganache Configuration
- **Host**: 127.0.0.1:8545
- **Accounts**: Pre-funded with 100 ETH each
- **Gas Limit**: 6,000,000
- **Gas Price**: 0 (free transactions)

## Test Results

All tests validate the core Web3.php functionality:

✅ **12 scenarios** passed
✅ **71 steps** executed successfully
✅ **Wallet creation** working
✅ **Gas estimation** functional
✅ **ETH transfers** operational
✅ **Transaction details retrieval** working
✅ **Balance queries** working

## Benefits

1. **Integration Testing**: Tests actual blockchain interactions, not just unit tests
2. **Real-World Scenarios**: Uses actual transaction flows and gas estimation
3. **Automated Validation**: Ensures Web3.php works correctly with Ethereum clients
4. **Regression Protection**: Catches breaking changes in blockchain interactions
5. **Documentation**: Feature files serve as living documentation of functionality

## Troubleshooting

### Common Issues

1. **Ganache not running**: Ensure `docker compose up -d ganache` was executed
2. **Connection timeouts**: Verify Ganache is accessible at `http://127.0.0.1:8545`
3. **Transaction failures**: Check that Ganache accounts have sufficient funds
4. **Type conversion errors**: BigInteger handling is complex but handled in FeatureContext

### Debug Commands
```bash
# Check Ganache status
curl -X POST --data '{"jsonrpc":"2.0","method":"net_version","params":[],"id":1}' -H "Content-Type: application/json" http://127.0.0.1:8545

# Check Ganache accounts
curl -X POST --data '{"jsonrpc":"2.0","method":"eth_accounts","params":[],"id":1}' -H "Content-Type: application/json" http://127.0.0.1:8545
```
