# GitHub Actions Workflow Documentation

This document describes the GitHub Actions workflow for the Web3.php project.

## Workflow Overview

The workflow (`.github/workflows/tests.yml`) runs automatically on:
- Push to `master`, `main`, or `develop` branches
- Pull requests to `master`, `main`, or `develop` branches

## Test Matrix

The workflow runs tests on multiple PHP versions:
- PHP 8.1
- PHP 8.2
- PHP 8.3

## Workflow Steps

### 1. Environment Setup
- Checks out the code
- Sets up PHP with required extensions (mbstring, intl, gmp, bcmath)
- Enables Xdebug for code coverage
- Caches Composer dependencies

### 2. Dependency Installation
- Installs project dependencies via Composer

### 3. Ganache Blockchain
- Starts Ganache via Docker Compose
- Waits for Ganache to be ready (with retries)
- Verifies connection to ensure blockchain is accessible

### 4. PHPUnit Tests
- Runs unit tests with code coverage
- Generates coverage reports in multiple formats:
  - HTML report (`coverage_html/`)
  - Clover XML report (`coverage.xml`)
  - Text output to console

### 5. Behat Functional Tests
- Runs Behat functional tests that interact with Ganache
- Tests wallet creation, gas estimation, transfers, and transaction details

### 6. Code Coverage Verification
- Verifies that code coverage meets the required 75% minimum
- Uses custom script (`scripts/check-coverage.php`) for validation
- Fails the build if coverage is below threshold

### 7. Cleanup
- Stops Ganache containers
- Uploads coverage reports as artifacts (for PHP 8.2 only)

## Coverage Requirements

The workflow enforces a minimum code coverage of **75%**. Currently, the project achieves **91.8%** coverage, which exceeds the requirement.

### Coverage Calculation
- Coverage is calculated based on covered elements vs total elements
- Includes lines, methods, and classes
- Uses PHPUnit's Clover XML format for precise measurements

## Test Types

### Unit Tests (PHPUnit)
- Located in `test/unit/`
- Test individual components and methods
- Mock external dependencies
- Focus on business logic validation

### Functional Tests (Behat)
- Located in `features/`
- Test end-to-end blockchain interactions
- Use real Ganache blockchain
- Validate integration scenarios

## Artifacts

The workflow generates the following artifacts:
- **Coverage Report**: HTML coverage report (uploaded for PHP 8.2)
- **Coverage XML**: Clover format for programmatic access
- **Test Results**: Console output for all test runs

## Badges

The README includes status badges:
- **Tests**: Shows overall test status (passing/failing)
- **Coverage**: Shows current coverage percentage
- Links to GitHub Actions for detailed results

## Local Development

To run the same tests locally:

```bash
# Start Ganache
cd docker && docker compose up -d ganache

# Run PHPUnit with coverage
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-clover coverage.xml --coverage-text

# Run Behat tests
vendor/bin/behat

# Check coverage
php scripts/check-coverage.php

# Stop Ganache
cd docker && docker compose down
```

## Troubleshooting

### Common Issues

1. **Ganache Connection Failures**
   - The workflow includes retry logic for Ganache startup
   - Increases wait time for Docker containers to fully initialize

2. **Coverage Generation**
   - Ensures `XDEBUG_MODE=coverage` is set
   - Uses proper PHPUnit coverage options

3. **Memory Issues**
   - PHP configuration includes increased memory limits
   - Coverage generation can be memory-intensive

4. **Test Timeouts**
   - Blockchain operations can be slow
   - Tests include appropriate timeout handling

### Debug Information

If tests fail, check:
- **Ganache logs**: Container startup and connection issues
- **PHPUnit output**: Unit test failures and coverage details
- **Behat output**: Functional test failures and blockchain interactions
- **Coverage report**: Detailed line-by-line coverage information

## Security

- No secrets or credentials are required
- Uses ephemeral Ganache blockchain (data not persisted)
- Coverage reports are uploaded as temporary artifacts
- No external services for coverage reporting (self-contained)
