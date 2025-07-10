<?php

/**
 * Direct performance test script for EnhancedAttribution Goal URLs
 *
 * Usage: php test-performance.php [--idsite=1] [--date=2025-05-15] [--iterations=3]
 */

// Abort if not running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Set up Matomo environment
require_once __DIR__ . '/../../bootstrap.php';

use Piwik\API\Request;
use Piwik\Plugins\EnhancedAttribution\API;

// Parse command line arguments
$options = [
    'idsite' => 1,
    'date' => '2025-05-15',
    'period' => 'day',
    'segment' => false,
    'iterations' => 3
];

foreach ($argv as $arg) {
    if (strpos($arg, '--') === 0) {
        list($key, $value) = explode('=', substr($arg, 2), 2);
        if (isset($options[$key])) {
            $options[$key] = $value;
        }
    }
}

echo "EnhancedAttribution Goal URLs Performance Test\n";
echo "=====================================\n";
echo "Site ID: {$options['idsite']}\n";
echo "Date: {$options['date']}\n";
echo "Period: {$options['period']}\n";
echo "Segment: " . ($options['segment'] ?: 'none') . "\n";
echo "Iterations: {$options['iterations']}\n\n";

$api = API::getInstance();
$totalTimes = [];
$results = [];

for ($i = 1; $i <= $options['iterations']; $i++) {
    echo "Iteration $i/{$options['iterations']}\n";

    // Memory usage before
    $memoryBefore = memory_get_usage(true);
    $peakBefore = memory_get_peak_usage(true);

    // Time the API call
    $startTime = microtime(true);

    try {
        $result = $api->getGoalUrlsDetailed(
            (int) $options['idsite'],
            $options['period'],
            $options['date'],
            $options['segment']
        );
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;
        $totalTimes[] = $executionTime;

        // Memory usage after
        $memoryAfter = memory_get_usage(true);
        $peakAfter = memory_get_peak_usage(true);

        $rowCount = $result->getRowsCount();
        $results[] = $rowCount;

        echo "  ✓ Execution time: " . number_format($executionTime * 1000, 2) . " ms\n";
        echo "  ✓ Rows returned: $rowCount\n";
        echo "  ✓ Memory used: " . formatBytes($memoryAfter - $memoryBefore) . "\n";
        echo "  ✓ Peak memory: " . formatBytes($peakAfter - $peakBefore) . "\n";

        // Show first few URLs for verification
        if ($i === 1 && $rowCount > 0) {
            echo "  Sample URLs:\n";
            $sampleCount = min(3, $rowCount);
            foreach ($result->getRows() as $index => $row) {
                if ($index >= $sampleCount) {
                    break;
                }
                $columns = $row->getColumns();
                echo "    - " . ($columns['conversion_url'] ?? 'N/A') . "\n";
            }
        }

    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        exit(1);
    }

    echo "\n";

    // Small delay between iterations
    if ($i < $options['iterations']) {
        usleep(100000); // 100ms delay
    }
}

// Calculate statistics
$avgTime = array_sum($totalTimes) / count($totalTimes);
$minTime = min($totalTimes);
$maxTime = max($totalTimes);
$avgRows = array_sum($results) / count($results);

echo "Performance Summary\n";
echo "===================\n";
echo "Average execution time: " . number_format($avgTime * 1000, 2) . " ms\n";
echo "Minimum execution time: " . number_format($minTime * 1000, 2) . " ms\n";
echo "Maximum execution time: " . number_format($maxTime * 1000, 2) . " ms\n";
echo "Average rows returned: " . number_format($avgRows, 1) . "\n";

if (count($totalTimes) > 1) {
    $variance = 0;
    foreach ($totalTimes as $time) {
        $variance += pow($time - $avgTime, 2);
    }
    $stdDev = sqrt($variance / count($totalTimes));
    echo "Standard deviation: " . number_format($stdDev * 1000, 2) . " ms\n";
}

// Performance assessment
echo "\nPerformance Assessment\n";
if ($avgTime < 0.1) {
    echo "✓ EXCELLENT: Under 100ms average\n";
} elseif ($avgTime < 0.5) {
    echo "⚠ GOOD: Under 500ms average\n";
} elseif ($avgTime < 1.0) {
    echo "⚠ ACCEPTABLE: Under 1 second average\n";
} elseif ($avgTime < 3.0) {
    echo "✗ SLOW: Under 3 seconds average\n";
} else {
    echo "✗ VERY SLOW: Over 3 seconds average\n";
}

// Recommendations
if ($avgTime > 0.5) {
    echo "\nPerformance Recommendations:\n";
    if ($avgRows > 100) {
        echo "- Consider reducing the SQL LIMIT or adding pagination\n";
    }
    if ($options['segment']) {
        echo "- Segment queries are typically slower; consider using archived data\n";
    }
    echo "- Check database indexes on log_conversion and log_visit tables\n";
    echo "- Consider caching results for frequently accessed data\n";
}

function formatBytes($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
