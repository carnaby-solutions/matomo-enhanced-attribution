<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\EnhancedAttribution\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\EnhancedAttribution\API;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to test Goal URLs performance with detailed timing
 */
class TestGoalUrlsPerformance extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('enhancedattribution:test-performance');
        $this->setDescription('Test Goal URLs performance with detailed timing information');
        $this->addOptionalValueOption('idsite', null, 'Site ID to test', 1);
        $this->addOptionalValueOption('date', null, 'Date to test (YYYY-MM-DD)', '2025-05-15');
        $this->addOptionalValueOption('period', null, 'Period to test', 'day');
        $this->addOptionalValueOption('segment', null, 'Segment to apply', false);
        $this->addOptionalValueOption('iterations', null, 'Number of iterations to run', 1);
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        
        $idSite = (int) $input->getOption('idsite');
        $date = $input->getOption('date');
        $period = $input->getOption('period');
        $segment = $input->getOption('segment');
        $iterations = (int) $input->getOption('iterations');

        $output->writeln('<info>EnhancedAttribution Goal URLs Performance Test</info>');
        $output->writeln('=====================================');
        $output->writeln("Site ID: $idSite");
        $output->writeln("Date: $date");
        $output->writeln("Period: $period");
        $output->writeln("Segment: " . ($segment ?: 'none'));
        $output->writeln("Iterations: $iterations");
        $output->writeln('');

        $api = API::getInstance();
        $totalTimes = [];
        $results = [];

        for ($i = 1; $i <= $iterations; $i++) {
            $output->writeln("<comment>Iteration $i/$iterations</comment>");
            
            // Memory usage before
            $memoryBefore = memory_get_usage(true);
            $peakBefore = memory_get_peak_usage(true);
            
            // Time the API call
            $startTime = microtime(true);
            
            try {
                $result = $api->getGoalUrlsDetailed($idSite, $period, $date, $segment);
                $endTime = microtime(true);
                
                $executionTime = $endTime - $startTime;
                $totalTimes[] = $executionTime;
                
                // Memory usage after
                $memoryAfter = memory_get_usage(true);
                $peakAfter = memory_get_peak_usage(true);
                
                $rowCount = $result->getRowsCount();
                $results[] = $rowCount;
                
                $output->writeln("  ✓ Execution time: " . number_format($executionTime * 1000, 2) . " ms");
                $output->writeln("  ✓ Rows returned: $rowCount");
                $output->writeln("  ✓ Memory used: " . $this->formatBytes($memoryAfter - $memoryBefore));
                $output->writeln("  ✓ Peak memory: " . $this->formatBytes($peakAfter - $peakBefore));
                
                // Show first few URLs for verification
                if ($i === 1 && $rowCount > 0) {
                    $output->writeln("  Sample URLs:");
                    $sampleCount = min(3, $rowCount);
                    foreach ($result->getRows() as $index => $row) {
                        if ($index >= $sampleCount) break;
                        $columns = $row->getColumns();
                        $output->writeln("    - " . ($columns['conversion_url'] ?? 'N/A'));
                    }
                }
                
            } catch (\Exception $e) {
                $output->writeln("  ✗ Error: " . $e->getMessage());
                return 1;
            }
            
            $output->writeln('');
            
            // Small delay between iterations to avoid overwhelming the system
            if ($i < $iterations) {
                usleep(100000); // 100ms delay
            }
        }

        // Calculate statistics
        $avgTime = array_sum($totalTimes) / count($totalTimes);
        $minTime = min($totalTimes);
        $maxTime = max($totalTimes);
        $avgRows = array_sum($results) / count($results);

        $output->writeln('<info>Performance Summary</info>');
        $output->writeln('===================');
        $output->writeln("Average execution time: " . number_format($avgTime * 1000, 2) . " ms");
        $output->writeln("Minimum execution time: " . number_format($minTime * 1000, 2) . " ms");
        $output->writeln("Maximum execution time: " . number_format($maxTime * 1000, 2) . " ms");
        $output->writeln("Average rows returned: " . number_format($avgRows, 1));
        
        if (count($totalTimes) > 1) {
            $variance = 0;
            foreach ($totalTimes as $time) {
                $variance += pow($time - $avgTime, 2);
            }
            $stdDev = sqrt($variance / count($totalTimes));
            $output->writeln("Standard deviation: " . number_format($stdDev * 1000, 2) . " ms");
        }

        // Performance assessment
        $output->writeln('');
        $output->writeln('<info>Performance Assessment</info>');
        if ($avgTime < 0.1) {
            $output->writeln('<fg=green>✓ EXCELLENT: Under 100ms average</fg=green>');
        } elseif ($avgTime < 0.5) {
            $output->writeln('<fg=yellow>⚠ GOOD: Under 500ms average</fg=yellow>');
        } elseif ($avgTime < 1.0) {
            $output->writeln('<fg=yellow>⚠ ACCEPTABLE: Under 1 second average</fg=yellow>');
        } elseif ($avgTime < 3.0) {
            $output->writeln('<fg=red>✗ SLOW: Under 3 seconds average</fg=red>');
        } else {
            $output->writeln('<fg=red>✗ VERY SLOW: Over 3 seconds average</fg=red>');
        }

        // Recommendations
        if ($avgTime > 0.5) {
            $output->writeln('');
            $output->writeln('<comment>Performance Recommendations:</comment>');
            if ($avgRows > 100) {
                $output->writeln('- Consider reducing the SQL LIMIT or adding pagination');
            }
            if ($segment) {
                $output->writeln('- Segment queries are typically slower; consider using archived data');
            }
            $output->writeln('- Check database indexes on log_conversion and log_visit tables');
            $output->writeln('- Consider caching results for frequently accessed data');
        }

        return 0;
    }

    private function formatBytes($bytes)
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
}