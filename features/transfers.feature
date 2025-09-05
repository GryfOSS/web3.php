Feature: ETH Transfers
  In order to send value on the blockchain
  As a user
  I need to be able to transfer ETH between accounts

  Background:
    Given I connect to the Ganache blockchain
    And I have an account with funds

  Scenario: Transfer ETH to another account
    Given I have a recipient address "0x742d35Cc6634C0532925a3b8D0C0c0CfD24a5bb8"
    When I transfer "1" ETH to the recipient
    Then the transfer should be successful
    And I should get a transaction hash

  Scenario: Transfer small amount of ETH
    Given I have a recipient address "0x742d35Cc6634C0532925a3b8D0C0c0CfD24a5bb8"
    When I transfer "0.001" ETH to the recipient
    Then the transfer should be successful
    And I should get a transaction hash

  Scenario: Check account balance
    When I check the balance of my account
    Then the balance should be greater than "90" ETH

  Scenario: Verify funded account has sufficient balance
    When I check the balance of my account
    Then the balance should be greater than "50" ETH
