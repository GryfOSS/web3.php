#!/bin/bash

# Web3.php Full Test Suite Runner
# This script runs both PHPUnit unit tests and Behat functional tests

set -e

echo "🚀 Starting Web3.php Full Test Suite"
echo "===================================="

# Check if Ganache is running
echo "📡 Checking Ganache connection..."
if curl -s -X POST --data '{"jsonrpc":"2.0","method":"net_version","params":[],"id":1}' -H "Content-Type: application/json" http://127.0.0.1:8545 > /dev/null; then
    echo "✅ Ganache is running"
else
    echo "❌ Ganache is not running. Starting Ganache..."
    cd docker && docker compose up -d ganache
    echo "⏳ Waiting for Ganache to start..."
    sleep 5
    cd ..
fi

echo ""
echo "🧪 Running PHPUnit Tests"
echo "========================"
vendor/bin/phpunit

echo ""
echo "🥒 Running Behat Functional Tests"
echo "================================="
vendor/bin/behat

echo ""
echo "🎉 All tests completed successfully!"
echo "=================================="
echo "✅ PHPUnit unit tests: PASSED"
echo "✅ Behat functional tests: PASSED"
echo ""
echo "📊 Test Coverage:"
echo "- Wallet creation and validation"
echo "- Gas estimation for transactions"
echo "- ETH transfers between accounts"
echo "- Transaction details retrieval by hash"
echo "- Balance verification"
echo "- Smart contract interactions"
echo ""
echo "🔗 Test Reports:"
echo "- PHPUnit coverage: See coverage_html/ directory"
echo "- Behat results: Console output above"
