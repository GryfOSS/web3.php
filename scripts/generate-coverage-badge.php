#!/usr/bin/env php
<?php

/**
 * Generate a dynamic coverage badge URL for the README
 */

if (!file_exists('coverage.xml')) {
    echo "ERROR: coverage.xml file not found.\n";
    exit(1);
}

$xml = simplexml_load_file('coverage.xml');
if (!$xml) {
    echo "ERROR: Could not parse coverage.xml file.\n";
    exit(1);
}

$metrics = $xml->xpath('//metrics');
if (empty($metrics)) {
    echo "ERROR: No metrics found in coverage report.\n";
    exit(1);
}

$totalElements = 0;
$coveredElements = 0;

foreach ($metrics as $metric) {
    $elements = (int)$metric['elements'];
    $covered = (int)$metric['coveredelements'];

    if ($elements > 0) {
        $totalElements += $elements;
        $coveredElements += $covered;
    }
}

if ($totalElements === 0) {
    $coverage = 0;
} else {
    $coverage = ($coveredElements / $totalElements) * 100;
}

$coveragePercent = round($coverage, 1);

// Determine badge color
if ($coverage >= 90) {
    $color = 'brightgreen';
} elseif ($coverage >= 75) {
    $color = 'green';
} elseif ($coverage >= 60) {
    $color = 'yellow';
} elseif ($coverage >= 40) {
    $color = 'orange';
} else {
    $color = 'red';
}

// Generate badge URL
$badgeUrl = "https://img.shields.io/badge/coverage-{$coveragePercent}%25-{$color}.svg";

echo $badgeUrl;
