#!/usr/bin/env php
<?php

/**
 * Check code coverage percentage from PHPUnit clover XML report
 */

if (!file_exists('coverage.xml')) {
    echo "ERROR: coverage.xml file not found. Make sure PHPUnit ran with --coverage-clover option.\n";
    exit(1);
}

$xml = simplexml_load_file('coverage.xml');
if (!$xml) {
    echo "ERROR: Could not parse coverage.xml file.\n";
    exit(1);
}

// Get metrics from the coverage report
$metrics = $xml->xpath('//metrics');
if (empty($metrics)) {
    echo "ERROR: No metrics found in coverage report.\n";
    exit(1);
}

$totalElements = 0;
$coveredElements = 0;

// Sum up all metrics (lines, methods, classes, etc.)
foreach ($metrics as $metric) {
    $elements = (int)$metric['elements'];
    $covered = (int)$metric['coveredelements'];

    if ($elements > 0) {
        $totalElements += $elements;
        $coveredElements += $covered;
    }
}

if ($totalElements === 0) {
    echo "WARNING: No code elements found to measure coverage.\n";
    exit(0);
}

$coverage = ($coveredElements / $totalElements) * 100;
$requiredCoverage = 75.0;

echo "Code Coverage Report:\n";
echo "====================\n";
echo "Total elements: {$totalElements}\n";
echo "Covered elements: {$coveredElements}\n";
echo "Coverage: " . round($coverage, 2) . "%\n";
echo "Required: {$requiredCoverage}%\n";
echo "\n";

if ($coverage >= $requiredCoverage) {
    echo "✅ SUCCESS: Code coverage (" . round($coverage, 2) . "%) meets the required {$requiredCoverage}%\n";
    exit(0);
} else {
    echo "❌ ERROR: Code coverage (" . round($coverage, 2) . "%) is below the required {$requiredCoverage}%\n";
    exit(1);
}
