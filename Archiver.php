<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\EnhancedAttribution;

use Piwik\DataAccess\LogAggregator;
use Piwik\Plugins\EnhancedAttribution\RecordBuilders\GoalUrlAggregator;

/**
 * Archiver for EnhancedAttribution plugin
 * 
 * This class provides backward compatibility and direct access to LogAggregator
 * The actual archiving logic is implemented in RecordBuilders
 */
class Archiver extends \Piwik\Plugin\Archiver
{
    public const GOAL_URLS_AGGREGATE_RECORD_NAME = GoalUrlAggregator::GOAL_URLS_AGGREGATE_RECORD_NAME;
    public const GOAL_URLS_BY_CHANNEL_RECORD_NAME = GoalUrlAggregator::GOAL_URLS_BY_CHANNEL_RECORD_NAME;
    public const GOAL_URLS_BY_SOURCE_RECORD_NAME = GoalUrlAggregator::GOAL_URLS_BY_SOURCE_RECORD_NAME;

    /**
     * Get the LogAggregator instance for custom queries
     * 
     * @return LogAggregator
     */
    public function getLogAggregator(): LogAggregator
    {
        return $this->getProcessor()->getLogAggregator();
    }

    /**
     * Archive goal URLs with custom logic if needed
     * This is called by the archiving system but the main logic is in RecordBuilders
     */
    public function aggregateDayReport()
    {
        // The RecordBuilders handle the main archiving logic
        // This method can be used for additional custom processing if needed
    }

    /**
     * Archive multiple day reports into periods
     */
    public function aggregateMultipleReports()
    {
        // Period aggregation is handled automatically by Matomo
        // Custom period aggregation logic can be added here if needed
    }
}