<?php

namespace Test\Unit;

use Test\TestCase;
use Web3\Validators\CallValidator;
use Web3\Validators\FilterValidator;
use Web3\Validators\PostValidator;
use Web3\Validators\ShhFilterValidator;
use Web3\Validators\TransactionValidator;

class ValidatorCoverageTest extends TestCase
{
    /**
     * Test CallValidator::validate method
     */
    public function testCallValidator(): void
    {
        // Test with valid call data
        $validCall = [
            'to' => '0x0000000000000000000000000000000000000001'
        ];
        $this->assertTrue(CallValidator::validate($validCall));

        // Test with invalid input (not array)
        $this->assertFalse(CallValidator::validate('invalid'));

        // Test with missing 'to' field
        $this->assertFalse(CallValidator::validate([]));

        // Test with invalid 'to' address
        $this->assertFalse(CallValidator::validate(['to' => 'invalid']));
    }

    /**
     * Test FilterValidator::validate method
     */
    public function testFilterValidator()
    {
        // Test valid filter data
        $validFilter = [
            'fromBlock' => '0x1',
            'toBlock' => 'latest',
            'address' => '0x742d35Cc6839C6B87C5982456b6A6E5b9c0E1234',
            'topics' => ['0x123456']
        ];
        $this->assertTrue(FilterValidator::validate($validFilter));

        // Test invalid filter data
        $this->assertFalse(FilterValidator::validate('not an array'));

        // Test invalid fromBlock
        $invalidFromBlock = [
            'fromBlock' => 'invalid'
        ];
        $this->assertFalse(FilterValidator::validate($invalidFromBlock));

        // Test invalid toBlock
        $invalidToBlock = [
            'toBlock' => 'invalid'
        ];
        $this->assertFalse(FilterValidator::validate($invalidToBlock));

        // Test array of addresses
        $validFilterWithMultipleAddresses = [
            'address' => [
                '0x742d35Cc6839C6B87C5982456b6A6E5b9c0E1234',
                '0x742d35Cc6839C6B87C5982456b6A6E5b9c0E5678'
            ]
        ];
        $this->assertTrue(FilterValidator::validate($validFilterWithMultipleAddresses));

        // Test invalid address in array
        $invalidFilterWithBadAddress = [
            'address' => [
                '0x742d35Cc6839C6B87C5982456b6A6E5b9c0E1234',
                'invalid_address'
            ]
        ];
        $this->assertFalse(FilterValidator::validate($invalidFilterWithBadAddress));

        // Test single invalid address
        $invalidAddress = [
            'address' => 'invalid_address'
        ];
        $this->assertFalse(FilterValidator::validate($invalidAddress));

        // Test nested topics array
        $nestedTopics = [
            'topics' => [
                ['0x123456', '0x789abc'],
                '0xdef012'
            ]
        ];
        $this->assertTrue(FilterValidator::validate($nestedTopics));

        // Test invalid nested topic
        $invalidNestedTopic = [
            'topics' => [
                ['0x123456', 'invalid_hex']
            ]
        ];
        $this->assertFalse(FilterValidator::validate($invalidNestedTopic));

        // Test invalid single topic
        $invalidSingleTopic = [
            'topics' => ['invalid_hex']
        ];
        $this->assertFalse(FilterValidator::validate($invalidSingleTopic));
    }

    /**
     * Test PostValidator::validate method
     */
    public function testPostValidator()
    {
        // Test valid post data
        $validPost = [
            'from' => '0x04f96a5e25610293e42a73908e93ccc8c4d4dc0edcfa9fa872f50cb214e08ebf61a03e245533f97284d442460f2998cd41858798ddfd4d661997d3940272b717b1',
            'to' => '0x04f96a5e25610293e42a73908e93ccc8c4d4dc0edcfa9fa872f50cb214e08ebf61a03e245533f97284d442460f2998cd41858798ddfd4d661997d3940272b717b1',
            'topics' => ['0x04f96a5e25610293e42a73908e93ccc8c4d4dc0edcfa9fa872f50cb214e08ebf61a03e245533f97284d442460f2998cd41858798ddfd4d661997d3940272b717b1'],
            'payload' => '0x123456',
            'priority' => '0x1',
            'ttl' => '0x100'
        ];
        $this->assertTrue(PostValidator::validate($validPost));

        // Test invalid post data
        $this->assertFalse(PostValidator::validate('not an array'));

        // Test invalid from
        $invalidFrom = [
            'from' => 'invalid',
            'topics' => ['0x04f96a5e25610293e42a73908e93ccc8c4d4dc0edcfa9fa872f50cb214e08ebf61a03e245533f97284d442460f2998cd41858798ddfd4d661997d3940272b717b1'],
            'payload' => '0x123456',
            'priority' => '0x1',
            'ttl' => '0x100'
        ];
        $this->assertFalse(PostValidator::validate($invalidFrom));

        // Test invalid to
        $invalidTo = [
            'to' => 'invalid',
            'topics' => ['0x04f96a5e25610293e42a73908e93ccc8c4d4dc0edcfa9fa872f50cb214e08ebf61a03e245533f97284d442460f2998cd41858798ddfd4d661997d3940272b717b1'],
            'payload' => '0x123456',
            'priority' => '0x1',
            'ttl' => '0x100'
        ];
        $this->assertFalse(PostValidator::validate($invalidTo));

        // Test missing topics
        $missingTopics = [
            'payload' => '0x123456',
            'priority' => '0x1',
            'ttl' => '0x100'
        ];
        $this->assertFalse(PostValidator::validate($missingTopics));

        // Test invalid topics array
        $invalidTopicsArray = [
            'topics' => 'not an array',
            'payload' => '0x123456',
            'priority' => '0x1',
            'ttl' => '0x100'
        ];
        $this->assertFalse(PostValidator::validate($invalidTopicsArray));

        // Test invalid topic in array
        $invalidTopic = [
            'topics' => ['invalid_identity'],
            'payload' => '0x123456',
            'priority' => '0x1',
            'ttl' => '0x100'
        ];
        $this->assertFalse(PostValidator::validate($invalidTopic));

        // Test missing payload
        $missingPayload = [
            'topics' => ['0x04f96a5e25610293e42a73908e93ccc8c4d4dc0edcfa9fa872f50cb214e08ebf61a03e245533f97284d442460f2998cd41858798ddfd4d661997d3940272b717b1'],
            'priority' => '0x1',
            'ttl' => '0x100'
        ];
        $this->assertFalse(PostValidator::validate($missingPayload));

        // Test invalid payload
        $invalidPayload = [
            'topics' => ['0x04f96a5e25610293e42a73908e93ccc8c4d4dc0edcfa9fa872f50cb214e08ebf61a03e245533f97284d442460f2998cd41858798ddfd4d661997d3940272b717b1'],
            'payload' => 'invalid_hex',
            'priority' => '0x1',
            'ttl' => '0x100'
        ];
        $this->assertFalse(PostValidator::validate($invalidPayload));

        // Test missing priority
        $missingPriority = [
            'topics' => ['0x04f96a5e25610293e42a73908e93ccc8c4d4dc0edcfa9fa872f50cb214e08ebf61a03e245533f97284d442460f2998cd41858798ddfd4d661997d3940272b717b1'],
            'payload' => '0x123456',
            'ttl' => '0x100'
        ];
        $this->assertFalse(PostValidator::validate($missingPriority));

        // Test invalid priority
        $invalidPriority = [
            'topics' => ['0x04f96a5e25610293e42a73908e93ccc8c4d4dc0edcfa9fa872f50cb214e08ebf61a03e245533f97284d442460f2998cd41858798ddfd4d661997d3940272b717b1'],
            'payload' => '0x123456',
            'priority' => 'invalid',
            'ttl' => '0x100'
        ];
        $this->assertFalse(PostValidator::validate($invalidPriority));

        // Test missing ttl
        $missingTtl = [
            'topics' => ['0x04f96a5e25610293e42a73908e93ccc8c4d4dc0edcfa9fa872f50cb214e08ebf61a03e245533f97284d442460f2998cd41858798ddfd4d661997d3940272b717b1'],
            'payload' => '0x123456',
            'priority' => '0x1'
        ];
        $this->assertFalse(PostValidator::validate($missingTtl));

        // Test invalid ttl
        $invalidTtl = [
            'topics' => ['0x04f96a5e25610293e42a73908e93ccc8c4d4dc0edcfa9fa872f50cb214e08ebf61a03e245533f97284d442460f2998cd41858798ddfd4d661997d3940272b717b1'],
            'payload' => '0x123456',
            'priority' => '0x1',
            'ttl' => 'invalid'
        ];
        $this->assertFalse(PostValidator::validate($invalidTtl));
    }

    /**
     * Test ShhFilterValidator::validate method
     */
    public function testShhFilterValidator(): void
    {
        // Test with valid filter (requires topics with hex values)
        $validFilter = [
            'topics' => ['0x1234']
        ];
        $this->assertTrue(ShhFilterValidator::validate($validFilter));

        // Test with valid filter with 'to' parameter (must be hex identity)
        $validFilterWithTo = [
            'to' => '0x1234567890abcdef',
            'topics' => ['0x1234']
        ];
        $this->assertTrue(ShhFilterValidator::validate($validFilterWithTo));

        // Test with invalid input
        $this->assertFalse(ShhFilterValidator::validate('invalid'));

        // Test with missing topics
        $this->assertFalse(ShhFilterValidator::validate([]));

        // Test with invalid topics array
        $invalidTopicsArray = [
            'topics' => 'not an array'
        ];
        $this->assertFalse(ShhFilterValidator::validate($invalidTopicsArray));

        // Test with invalid 'to' identity
        $invalidTo = [
            'to' => 'invalid_identity',
            'topics' => ['0x1234']
        ];
        $this->assertFalse(ShhFilterValidator::validate($invalidTo));

        // Test nested array topics
        $nestedTopics = [
            'topics' => [
                ['0x123456', '0x789abc']
            ]
        ];
        $this->assertTrue(ShhFilterValidator::validate($nestedTopics));

        // Test invalid nested topic
        $invalidNestedTopic = [
            'topics' => [
                ['0x123456', 'invalid_hex']
            ]
        ];
        $this->assertFalse(ShhFilterValidator::validate($invalidNestedTopic));

        // Test null topic (should be allowed)
        $nullTopic = [
            'topics' => [null]
        ];
        $this->assertTrue(ShhFilterValidator::validate($nullTopic));

        // Test invalid non-null topic
        $invalidTopic = [
            'topics' => ['invalid_hex']
        ];
        $this->assertFalse(ShhFilterValidator::validate($invalidTopic));
    }

    /**
     * Test TransactionValidator::validate method
     */
    public function testTransactionValidator(): void
    {
        // Test with valid transaction (requires 'from' field)
        $validTx = [
            'from' => '0x0000000000000000000000000000000000000001'
        ];
        $this->assertTrue(TransactionValidator::validate($validTx));

        // Test with valid transaction with 'to' field
        $validTxWithTo = [
            'from' => '0x0000000000000000000000000000000000000001',
            'to' => '0x0000000000000000000000000000000000000002'
        ];
        $this->assertTrue(TransactionValidator::validate($validTxWithTo));

        // Test with invalid input
        $this->assertFalse(TransactionValidator::validate('invalid'));

        // Test with missing 'from' field
        $this->assertFalse(TransactionValidator::validate([]));

        // Test with invalid 'from' address
        $this->assertFalse(TransactionValidator::validate(['from' => 'invalid']));

        // Test with invalid 'to' address
        $invalidTo = [
            'from' => '0x0000000000000000000000000000000000000001',
            'to' => 'invalid_address'
        ];
        $this->assertFalse(TransactionValidator::validate($invalidTo));

        // Test with valid gas
        $validGas = [
            'from' => '0x0000000000000000000000000000000000000001',
            'gas' => '0x5208'
        ];
        $this->assertTrue(TransactionValidator::validate($validGas));

        // Test with invalid gas
        $invalidGas = [
            'from' => '0x0000000000000000000000000000000000000001',
            'gas' => 'invalid'
        ];
        $this->assertFalse(TransactionValidator::validate($invalidGas));

        // Test with valid gasPrice
        $validGasPrice = [
            'from' => '0x0000000000000000000000000000000000000001',
            'gasPrice' => '0x9184e72a000'
        ];
        $this->assertTrue(TransactionValidator::validate($validGasPrice));

        // Test with invalid gasPrice
        $invalidGasPrice = [
            'from' => '0x0000000000000000000000000000000000000001',
            'gasPrice' => 'invalid'
        ];
        $this->assertFalse(TransactionValidator::validate($invalidGasPrice));

        // Test with valid value
        $validValue = [
            'from' => '0x0000000000000000000000000000000000000001',
            'value' => '0xde0b6b3a7640000'
        ];
        $this->assertTrue(TransactionValidator::validate($validValue));

        // Test with invalid value
        $invalidValue = [
            'from' => '0x0000000000000000000000000000000000000001',
            'value' => 'invalid'
        ];
        $this->assertFalse(TransactionValidator::validate($invalidValue));

        // Test with valid data
        $validData = [
            'from' => '0x0000000000000000000000000000000000000001',
            'data' => '0x608060405234801561001057600080fd5b5060'
        ];
        $this->assertTrue(TransactionValidator::validate($validData));

        // Test with invalid data
        $invalidData = [
            'from' => '0x0000000000000000000000000000000000000001',
            'data' => 'invalid_hex'
        ];
        $this->assertFalse(TransactionValidator::validate($invalidData));

        // Test with valid nonce
        $validNonce = [
            'from' => '0x0000000000000000000000000000000000000001',
            'nonce' => '0x1'
        ];
        $this->assertTrue(TransactionValidator::validate($validNonce));

        // Test with invalid nonce
        $invalidNonce = [
            'from' => '0x0000000000000000000000000000000000000001',
            'nonce' => 'invalid'
        ];
        $this->assertFalse(TransactionValidator::validate($invalidNonce));
    }
}
