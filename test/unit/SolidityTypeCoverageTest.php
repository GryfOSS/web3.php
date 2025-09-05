<?php

namespace Test\Unit;

use Test\TestCase;
use Web3\Contracts\SolidityType;

class SolidityTypeCoverageTest extends TestCase
{
    /**
     * Test SolidityType magic methods and utility functions
     */
    public function testSolidityTypeMagicMethods(): void
    {
        $solidityType = new SolidityType();

        // Test magic __get method - accessing non-existent property
        $result = $solidityType->nonExistentProperty;
        $this->assertFalse($result);

        // Test magic __set method - setting non-existent property
        $solidityType->nonExistentProperty = 'value';
        // Test that the setter method doesn't exist
        $this->assertFalse(method_exists($solidityType, 'setNonExistentProperty'));
    }

    /**
     * Test nestedTypes method
     */
    public function testNestedTypes(): void
    {
        $solidityType = new SolidityType();

        // Test with array type
        $result = $solidityType->nestedTypes('uint256[]');
        $this->assertEquals(['[]'], $result);

        // Test with multi-dimensional array
        $result = $solidityType->nestedTypes('uint256[5][10]');
        $this->assertEquals(['[5]', '[10]'], $result);

        // Test with non-array type
        $result = $solidityType->nestedTypes('uint256');
        $this->assertFalse($result);
    }

    /**
     * Test nestedTypes with invalid input
     */
    public function testNestedTypesInvalidInput(): void
    {
        $solidityType = new SolidityType();

        $this->expectException('InvalidArgumentException');
        $solidityType->nestedTypes(123);
    }

    /**
     * Test nestedName method
     */
    public function testNestedName(): void
    {
        $solidityType = new SolidityType();

        // Test with array type
        $result = $solidityType->nestedName('uint256[]');
        $this->assertEquals('uint256', $result);

        // Test with multi-dimensional array - only strips the last array notation
        $result = $solidityType->nestedName('uint256[5][10]');
        $this->assertEquals('uint256[5]', $result);

        // Test with non-array type
        $result = $solidityType->nestedName('uint256');
        $this->assertEquals('uint256', $result);
    }

    /**
     * Test nestedName with invalid input
     */
    public function testNestedNameInvalidInput(): void
    {
        $solidityType = new SolidityType();

        $this->expectException('InvalidArgumentException');
        $solidityType->nestedName(123);
    }

    /**
     * Test array type detection methods
     */
    public function testArrayTypeDetection(): void
    {
        $solidityType = new SolidityType();

        // Test isDynamicArray
        $this->assertTrue($solidityType->isDynamicArray('uint256[]'));
        $this->assertFalse($solidityType->isDynamicArray('uint256[5]'));
        $this->assertFalse($solidityType->isDynamicArray('uint256'));

        // Test isStaticArray
        $this->assertTrue($solidityType->isStaticArray('uint256[5]'));
        $this->assertFalse($solidityType->isStaticArray('uint256[]'));
        $this->assertFalse($solidityType->isStaticArray('uint256'));
    }

    /**
     * Test array type detection with invalid input
     */
    public function testArrayTypeDetectionInvalidInput(): void
    {
        $solidityType = new SolidityType();

        $this->expectException('InvalidArgumentException');
        $solidityType->isDynamicArray(123);
    }

    /**
     * Test staticArrayLength method
     */
    public function testStaticArrayLength(): void
    {
        $solidityType = new SolidityType();

        // Test with static array
        $result = $solidityType->staticArrayLength('uint256[5]');
        $this->assertEquals(5, $result);

        // Test with multi-dimensional static array - returns the last array length
        $result = $solidityType->staticArrayLength('uint256[5][10]');
        $this->assertEquals(10, $result);

        // Test with dynamic array should return 1
        $result = $solidityType->staticArrayLength('uint256[]');
        $this->assertEquals(1, $result);

        // Test with non-array type
        $result = $solidityType->staticArrayLength('uint256');
        $this->assertEquals(1, $result);
    }

    /**
     * Test staticArrayLength with invalid input
     */
    public function testStaticArrayLengthInvalidInput(): void
    {
        $solidityType = new SolidityType();

        $this->expectException('InvalidArgumentException');
        $solidityType->staticArrayLength(123);
    }

    /**
     * Test staticPartLength method
     */
    public function testStaticPartLength(): void
    {
        $solidityType = new SolidityType();

        // Test basic cases - this method should return some length value
        $result = $solidityType->staticPartLength('uint256');
        $this->assertIsNumeric($result);

        // Test with array type
        $result = $solidityType->staticPartLength('uint256[]');
        $this->assertIsNumeric($result);
    }

    /**
     * Test staticPartLength with invalid input
     */
    public function testStaticPartLengthInvalidInput(): void
    {
        $solidityType = new SolidityType();

        $this->expectException('InvalidArgumentException');
        $solidityType->staticPartLength(123);
    }

    /**
     * Test isDynamicType method
     */
    public function testIsDynamicType(): void
    {
        $solidityType = new SolidityType();

        // This method should return false by default in the base class
        $this->assertFalse($solidityType->isDynamicType());
    }
}
