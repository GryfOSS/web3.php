Feature: Wallet Creation
  In order to interact with the Ethereum blockchain
  As a user
  I need to be able to create new wallets

  Background:
    Given I connect to the Ganache blockchain

  Scenario: Create a new wallet
    When I create a new wallet
    Then I should have a valid wallet address
    And I should have a private key

  Scenario: Wallet address format validation
    When I create a new wallet
    Then I should have a valid wallet address
    And the wallet address should start with "0x"
    And the wallet address should be 42 characters long
