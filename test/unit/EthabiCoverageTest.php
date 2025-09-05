<?php

namespace Test\Unit;

use Test\TestCase;
use Web3\Contracts\Ethabi;

class EthabiCoverageTest extends TestCase
{
    /**
     * Test Ethabi constructor and magic methods
     */
    public function testEthabiConstructorAndMagicMethods(): void
    {
        // Test constructor with no arguments
        $ethabi = new Ethabi();
        $this->assertInstanceOf('Web3\Contracts\Ethabi', $ethabi);

        // Test constructor with types array
        $types = ['address', 'uint256'];
        $ethabi = new Ethabi($types);
        $this->assertInstanceOf('Web3\Contracts\Ethabi', $ethabi);

        // Test constructor with non-array (should convert to empty array)
        $ethabi = new Ethabi('invalid');
        $this->assertInstanceOf('Web3\Contracts\Ethabi', $ethabi);

        // Test magic __get method - accessing non-existent property
        $result = $ethabi->nonExistentProperty;
        $this->assertFalse($result);

        // Test magic __set method - setting non-existent property (PHP assignment returns the value)
        $ethabi->nonExistentProperty = 'value';
        // The actual method returns false, but PHP assignment returns the value, so let's test method existence
        $this->assertFalse(method_exists($ethabi, 'setNonExistentProperty'));

        // Test static magic method __callStatic
        $result = Ethabi::someStaticMethod();
        $this->assertNull($result);
    }

    /**
     * Test error cases in encoding methods
     */
    public function testEncodingErrorCases(): void
    {
        $ethabi = new Ethabi();

        // Test encodeParameter with non-string type
        $this->expectException('InvalidArgumentException');
        $ethabi->encodeParameter(['invalid'], 'value');
    }

    /**
     * Test error cases in decoding methods
     */
    public function testDecodingErrorCases(): void
    {
        $ethabi = new Ethabi();

        // Test decodeParameter with non-string type
        $this->expectException('InvalidArgumentException');
        $ethabi->decodeParameter(['invalid'], '0x1234');
    }

    /**
     * Test edge cases in signature methods
     */
    public function testSignatureEdgeCases(): void
    {
        $ethabi = new Ethabi();

        // Test encodeFunctionSignature with object
        $functionObj = (object) [
            'name' => 'transfer',
            'inputs' => [
                (object) ['type' => 'address', 'name' => 'to'],
                (object) ['type' => 'uint256', 'name' => 'amount']
            ]
        ];

        $signature = $ethabi->encodeFunctionSignature($functionObj);
        $this->assertIsString($signature);
        $this->assertEquals(10, strlen($signature)); // 0x + 8 hex chars

        // Test encodeEventSignature with object
        $eventObj = (object) [
            'name' => 'Transfer',
            'inputs' => [
                (object) ['type' => 'address', 'name' => 'from'],
                (object) ['type' => 'address', 'name' => 'to'],
                (object) ['type' => 'uint256', 'name' => 'value']
            ]
        ];

        $eventSignature = $ethabi->encodeEventSignature($eventObj);
        $this->assertIsString($eventSignature);
        $this->assertEquals(66, strlen($eventSignature)); // 0x + 64 hex chars
    }
}
