Feature: Transaction Details Retrieval
  In order to track and verify transactions
  As a developer using Web3.php
  I need to retrieve transaction details by hash

  Background:
    Given I connect to the Ganache blockchain

  Scenario: Create transaction between funded accounts and retrieve details
    Given I have access to funded accounts
    When I transfer 0.1 ETH between funded accounts
    Then the transfer should be successful
    And I should get a transaction hash
    When I retrieve the transaction details using the transaction hash
    Then the transaction details should contain the correct information
    And the transaction should have a valid block number
    And the transaction should have gas information

  Scenario: Retrieve transaction details with hash validation
    Given I have access to funded accounts
    When I transfer 0.05 ETH between funded accounts
    Then the transfer should be successful
    When I retrieve the transaction details using the transaction hash
    Then the transaction details should contain the correct information
    And the transaction hash in details should match the original hash

  Scenario: Verify transaction details contain required fields
    Given I have access to funded accounts
    When I transfer 0.2 ETH between funded accounts
    Then the transfer should be successful
    When I retrieve the transaction details using the transaction hash
    Then the transaction details should contain the correct information
    And the transaction should have gas information
    And the transaction should contain all required blockchain fields
