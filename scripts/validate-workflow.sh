#!/bin/bash

# Local Workflow Validation Script
# This script simulates the GitHub Actions workflow locally

set -e

echo "🚀 Starting Local Workflow Validation"
echo "====================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Check dependencies
echo -e "\n${YELLOW}📦 Step 1: Checking Dependencies${NC}"
if ! command -v composer &> /dev/null; then
    echo -e "${RED}❌ Composer not found${NC}"
    exit 1
fi

if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker not found${NC}"
    exit 1
fi

if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP not found${NC}"
    exit 1
fi

echo -e "${GREEN}✅ All dependencies found${NC}"

# Step 2: Install Composer dependencies
echo -e "\n${YELLOW}📥 Step 2: Installing Dependencies${NC}"
composer install --prefer-dist --no-progress

# Step 3: Start Ganache
echo -e "\n${YELLOW}🔗 Step 3: Starting Ganache${NC}"
cd docker
docker compose down 2>/dev/null || true
docker compose up -d ganache

echo "⏳ Waiting for Ganache to start..."
sleep 15

# Verify Ganache with retries
for i in {1..5}; do
    if curl -f -s -X POST --data '{"jsonrpc":"2.0","method":"net_version","params":[],"id":1}' -H "Content-Type: application/json" http://127.0.0.1:8545 > /dev/null; then
        echo -e "${GREEN}✅ Ganache is running${NC}"
        break
    else
        echo "⏳ Attempt $i: Ganache not ready yet, waiting..."
        sleep 5
        if [ $i -eq 5 ]; then
            echo -e "${RED}❌ Failed to connect to Ganache${NC}"
            cd ..
            exit 1
        fi
    fi
done

cd ..

# Step 4: Run PHPUnit with Coverage
echo -e "\n${YELLOW}🧪 Step 4: Running PHPUnit Tests with Coverage${NC}"
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-clover coverage.xml --coverage-text --colors=never

# Step 5: Run Behat Tests
echo -e "\n${YELLOW}🥒 Step 5: Running Behat Functional Tests${NC}"
vendor/bin/behat --format=progress

# Step 6: Check Coverage
echo -e "\n${YELLOW}📊 Step 6: Checking Code Coverage${NC}"
php scripts/check-coverage.php

# Step 7: Cleanup
echo -e "\n${YELLOW}🧹 Step 7: Cleanup${NC}"
cd docker
docker compose down
cd ..

# Success
echo -e "\n${GREEN}🎉 All workflow steps completed successfully!${NC}"
echo "=================================="
echo -e "${GREEN}✅ Dependencies installed${NC}"
echo -e "${GREEN}✅ Ganache started and verified${NC}"
echo -e "${GREEN}✅ PHPUnit tests passed${NC}"
echo -e "${GREEN}✅ Behat tests passed${NC}"
echo -e "${GREEN}✅ Code coverage above 75%${NC}"
echo -e "${GREEN}✅ Cleanup completed${NC}"
echo ""
echo "🚀 Ready for GitHub Actions workflow!"
