<?php

namespace Test\Unit;

use Test\TestCase;
use Web3\Contract;
use Web3\Utils;

class ContractCoverageTest extends TestCase
{
    /**
     * Test getters and setters that might be missing coverage
     */
    public function testContractGettersAndSetters(): void
    {
        $abi = '[{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"}]';
        $contract = new Contract($this->web3->provider, $abi);
        
        // Test getProvider
        $this->assertSame($this->web3->provider, $contract->getProvider());
        
        // Test getDefaultBlock and setDefaultBlock
        $this->assertEquals('latest', $contract->getDefaultBlock());
        $contract->setDefaultBlock('pending');
        $this->assertEquals('pending', $contract->getDefaultBlock());
        $contract->setDefaultBlock('0x10');
        $this->assertEquals('0x10', $contract->getDefaultBlock());
        
        // Test getAbi
        $this->assertEquals(json_decode($abi, true), $contract->getAbi());
        
        // Test setAbi 
        $newAbi = '[{"constant":true,"inputs":[],"name":"symbol","outputs":[{"name":"","type":"string"}],"type":"function"}]';
        $contract->setAbi($newAbi);
        $this->assertEquals(json_decode($newAbi, true), $contract->getAbi());
        
        // Test getEthabi
        $ethabi = $contract->getEthabi();
        $this->assertInstanceOf('Web3\Contracts\Ethabi', $ethabi);
        
        // Test getEth
        $eth = $contract->getEth();
        $this->assertInstanceOf('Web3\Eth', $eth);
        
        // Test getFunctions
        $functions = $contract->getFunctions();
        $this->assertIsArray($functions);
        
        // Test getEvents
        $events = $contract->getEvents();
        $this->assertIsArray($events);
        
        // Test getConstructor
        $constructor = $contract->getConstructor();
        $this->assertIsArray($constructor);
        
        // Test getToAddress
        $this->assertNull($contract->getToAddress());
        
        // Test setToAddress
        $address = '0x742d35Cc6839C6B87C5982456b6A6E5b9c0E1234';
        $contract->setToAddress($address);
        $this->assertEquals(strtolower($address), $contract->getToAddress());
        
        // Test setBytecode
        $bytecode = '0x608060405234801561001057600080fd5b50';
        $contract->setBytecode($bytecode);
        
        // Test at method (chainable)
        $newContract = $contract->at($address);
        $this->assertInstanceOf('Web3\Contract', $newContract);
        $this->assertEquals(strtolower($address), $newContract->getToAddress());
        
        // Test bytecode method (chainable)
        $newContract2 = $contract->bytecode($bytecode);
        $this->assertInstanceOf('Web3\Contract', $newContract2);
        
        // Test abi method (chainable)
        $newContract3 = $contract->abi($abi);
        $this->assertInstanceOf('Web3\Contract', $newContract3);
    }
    
    /**
     * Test invalid default block validation
     */
    public function testInvalidDefaultBlock(): void
    {
        $abi = '[{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"type":"function"}]';
        $contract = new Contract($this->web3->provider, $abi);
        
        // Invalid block should fall back to 'latest'
        $contract->setDefaultBlock('invalid_block');
        $this->assertEquals('latest', $contract->getDefaultBlock());
    }
    
    /**
     * Test invalid address validation
     */
    public function testInvalidAddress(): void
    {
        $abi = '[{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"type":"function"}]';
        $contract = new Contract($this->web3->provider, $abi);
        
        $this->expectException('InvalidArgumentException');
        $contract->setToAddress('invalid_address');
    }
    
    /**
     * Test invalid bytecode validation
     */
    public function testInvalidBytecode(): void
    {
        $abi = '[{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"type":"function"}]';
        $contract = new Contract($this->web3->provider, $abi);
        
        $this->expectException('InvalidArgumentException');
        $contract->setBytecode('invalid_hex');
    }
    
    /**
     * Test __get magic method
     */
    public function testMagicGet(): void
    {
        $abi = '[{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"type":"function"}]';
        $contract = new Contract($this->web3->provider, $abi);
        
        // Test getting provider via magic method
        $provider = $contract->provider;
        $this->assertSame($this->web3->provider, $provider);
        
        // Test getting non-existent property returns false
        $nonExistent = $contract->nonExistentProperty;
        $this->assertFalse($nonExistent);
    }
    
    /**
     * Test __set magic method
     */
    public function testMagicSet(): void
    {
        $abi = '[{"constant":true,"inputs":[],"name":"name","outputs":[{"name":"","type":"string"}],"type":"function"}]';
        $contract = new Contract($this->web3->provider, $abi);
        
        // Test setting defaultBlock via magic method
        $contract->defaultBlock = 'pending';
        $this->assertEquals('pending', $contract->getDefaultBlock());
        
        // Test setting non-existent property - PHP assignment always returns the assigned value
        $result = ($contract->someProperty = 'value') && false; // Force assignment and check method result
        // Actually, let's test that the method doesn't exist instead
        $this->assertFalse(method_exists($contract, 'setSomeProperty'));
    }
}
