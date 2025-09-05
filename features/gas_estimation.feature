Feature: Gas Estimation
  In order to send transactions efficiently
  As a user
  I need to be able to estimate gas costs

  Background:
    Given I connect to the Ganache blockchain
    And I have an account with funds

  Scenario: Estimate gas for ETH transfer
    Given I have a recipient address "0x742d35Cc6634C0532925a3b8D0C0c0CfD24a5bb8"
    When I estimate gas for transferring "1" ETH to "0x742d35Cc6634C0532925a3b8D0C0c0CfD24a5bb8"
    Then I should get a gas estimate
    And the gas estimate should be reasonable

  Scenario: Estimate gas for different amounts
    Given I have a recipient address "0x742d35Cc6634C0532925a3b8D0C0c0CfD24a5bb8"
    When I estimate gas for transferring "0.5" ETH to "0x742d35Cc6634C0532925a3b8D0C0c0CfD24a5bb8"
    Then I should get a gas estimate
    And the gas estimate should be reasonable

  Scenario: Estimate gas for small transfer
    Given I have a recipient address "0x742d35Cc6634C0532925a3b8D0C0c0CfD24a5bb8"
    When I estimate gas for transferring "0.001" ETH to "0x742d35Cc6634C0532925a3b8D0C0c0CfD24a5bb8"
    Then I should get a gas estimate
    And the gas estimate should be reasonable
