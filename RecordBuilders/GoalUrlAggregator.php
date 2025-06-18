<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\EnhancedAttribution\RecordBuilders;

use Piwik\ArchiveProcessor;
use Piwik\ArchiveProcessor\Record;
use Piwik\ArchiveProcessor\RecordBuilder;
use Piwik\DataAccess\LogAggregator;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Common;
use Piwik\Metrics;

class GoalUrlAggregator extends RecordBuilder
{
    public const GOAL_URLS_AGGREGATE_RECORD_NAME = 'EnhancedAttribution_goal_urls_aggregate';
    public const GOAL_URLS_BY_CHANNEL_RECORD_NAME = 'EnhancedAttribution_goal_urls_by_channel';
    public const GOAL_URLS_BY_SOURCE_RECORD_NAME = 'EnhancedAttribution_goal_urls_by_source';

    public function __construct()
    {
        parent::__construct(
            maxRowsInTable: 1000,
            maxRowsInSubtable: 100,
            columnToSortByBeforeTruncation: 'nb_conversions'
        );
    }

    public function getRecordMetadata(ArchiveProcessor $archiveProcessor): array
    {
        return [
            Record::make(Record::TYPE_BLOB, self::GOAL_URLS_AGGREGATE_RECORD_NAME),
            Record::make(Record::TYPE_BLOB, self::GOAL_URLS_BY_CHANNEL_RECORD_NAME),
            Record::make(Record::TYPE_BLOB, self::GOAL_URLS_BY_SOURCE_RECORD_NAME),
            Record::make(Record::TYPE_NUMERIC, 'EnhancedAttribution_total_goal_conversions'),
            Record::make(Record::TYPE_NUMERIC, 'EnhancedAttribution_unique_goal_urls'),
        ];
    }

    protected function aggregate(ArchiveProcessor $archiveProcessor): array
    {
        $logAggregator = $archiveProcessor->getLogAggregator();
        $idSites = $archiveProcessor->getParams()->getIdSites();
        
        if (empty($idSites)) {
            return [];
        }

        // Aggregate goal URLs with basic metrics
        $goalUrlsTable = $this->aggregateGoalUrls($logAggregator);
        
        // Aggregate by channel
        $channelTable = $this->aggregateByChannel($logAggregator);
        
        // Aggregate by source
        $sourceTable = $this->aggregateBySource($logAggregator);
        
        // Calculate summary metrics
        $totalConversions = $this->getTotalConversions($logAggregator);
        $uniqueUrls = $this->getUniqueUrlCount($logAggregator);

        return [
            self::GOAL_URLS_AGGREGATE_RECORD_NAME => $goalUrlsTable,
            self::GOAL_URLS_BY_CHANNEL_RECORD_NAME => $channelTable,
            self::GOAL_URLS_BY_SOURCE_RECORD_NAME => $sourceTable,
            'EnhancedAttribution_total_goal_conversions' => $totalConversions,
            'EnhancedAttribution_unique_goal_urls' => $uniqueUrls,
        ];
    }

    private function aggregateGoalUrls(LogAggregator $logAggregator): DataTable
    {
        // Use LogAggregator's conversion aggregation methods which properly handle segmentation
        $dimensions = ['url'];
        $additionalSelects = [
            'SUM(log_conversion.revenue) as revenue',
            'AVG(log_conversion.revenue) as avg_order_revenue'
        ];
        
        $query = $logAggregator->queryConversionsByDimension($dimensions, false, $additionalSelects);
        
        if ($query === false) {
            return new DataTable();
        }

        $table = new DataTable();
        
        while ($row = $query->fetch()) {
            // Skip rows with empty URLs
            if (empty($row['url'])) {
                continue;
            }
            
            $columns = [
                'conversion_url' => $row['url'],
                'nb_conversions' => (int) ($row['nb_conversions'] ?? 0),
                'nb_visits_converted' => (int) ($row['nb_visits_converted'] ?? 0),
                'nb_visitors_converted' => (int) ($row['nb_visitors_converted'] ?? 0),
                'revenue' => (float) ($row['revenue'] ?? 0),
                'avg_order_revenue' => (float) ($row['avg_order_revenue'] ?? 0),
            ];
            
            $table->addRowFromArray([Row::COLUMNS => $columns]);
        }

        return $table;
    }

    private function aggregateByChannel(LogAggregator $logAggregator): DataTable
    {
        $channelMatrix = [
            Common::REFERRER_TYPE_DIRECT_ENTRY   => 'direct',
            Common::REFERRER_TYPE_SEARCH_ENGINE  => 'search',
            Common::REFERRER_TYPE_CAMPAIGN       => 'campaign',
            Common::REFERRER_TYPE_SOCIAL_NETWORK => 'social',
            Common::REFERRER_TYPE_WEBSITE        => 'website',
        ];

        // Use LogAggregator method with just referer_type dimension (without log_visit prefix)
        $dimensions = ['referer_type'];
        $additionalSelects = [
            'SUM(log_conversion.revenue) as revenue'
        ];
        
        // Add condition to only include conversions with URLs
        $where = 'log_conversion.url IS NOT NULL AND log_conversion.url != ""';
        
        $query = $logAggregator->queryConversionsByDimension($dimensions, $where, $additionalSelects);
        
        if ($query === false) {
            return new DataTable();
        }

        $table = new DataTable();
        
        while ($row = $query->fetch()) {
            $refererType = (int) ($row['referer_type'] ?? 0);
            $channelName = $channelMatrix[$refererType] ?? 'unknown';
            
            $columns = [
                'label' => $channelName,
                'nb_conversions' => (int) ($row['nb_conversions'] ?? 0),
                'nb_visits_converted' => (int) ($row['nb_visits_converted'] ?? 0),
                'nb_visitors_converted' => (int) ($row['nb_visitors_converted'] ?? 0),
                'revenue' => (float) ($row['revenue'] ?? 0),
            ];
            
            $table->addRowFromArray([Row::COLUMNS => $columns]);
        }

        return $table;
    }

    private function aggregateBySource(LogAggregator $logAggregator): DataTable
    {
        // Use LogAggregator method with referrer dimensions (without log_visit prefix)
        $dimensions = ['referer_type', 'referer_name'];
        $additionalSelects = [
            'SUM(log_conversion.revenue) as revenue'
        ];
        
        // Add condition to only include conversions with URLs
        $where = 'log_conversion.url IS NOT NULL AND log_conversion.url != ""';
        
        $query = $logAggregator->queryConversionsByDimension($dimensions, $where, $additionalSelects);
        
        if ($query === false) {
            return new DataTable();
        }

        $table = new DataTable();
        
        while ($row = $query->fetch()) {
            $refererType = (int) ($row['referer_type'] ?? 0);
            $refererName = $row['referer_name'] ?? '';
            
            // Generate label based on referrer type
            $label = 'Unknown';
            switch ($refererType) {
                case Common::REFERRER_TYPE_DIRECT_ENTRY:
                    $label = 'Direct';
                    break;
                case Common::REFERRER_TYPE_CAMPAIGN:
                    $label = !empty($refererName) ? $refererName : 'Unknown Campaign';
                    break;
                case Common::REFERRER_TYPE_SEARCH_ENGINE:
                case Common::REFERRER_TYPE_SOCIAL_NETWORK:
                case Common::REFERRER_TYPE_WEBSITE:
                    $label = !empty($refererName) ? $refererName : 'Unknown';
                    break;
            }
            
            $columns = [
                'label' => $label,
                'nb_conversions' => (int) ($row['nb_conversions'] ?? 0),
                'nb_visits_converted' => (int) ($row['nb_visits_converted'] ?? 0),
                'nb_visitors_converted' => (int) ($row['nb_visitors_converted'] ?? 0),
                'revenue' => (float) ($row['revenue'] ?? 0),
            ];
            
            $table->addRowFromArray([Row::COLUMNS => $columns]);
        }

        return $table;
    }

    private function getTotalConversions(LogAggregator $logAggregator): int
    {
        // Use LogAggregator's queryConversionsByDimension with no dimensions to get totals
        $where = 'log_conversion.url IS NOT NULL AND log_conversion.url != ""';
        $query = $logAggregator->queryConversionsByDimension([], $where);
        
        if ($query === false) {
            return 0;
        }

        $row = $query->fetch();
        return (int) ($row['nb_conversions'] ?? 0);
    }

    private function getUniqueUrlCount(LogAggregator $logAggregator): int
    {
        // Use LogAggregator's queryConversionsByDimension with url dimension to count unique URLs
        $dimensions = ['url'];
        $where = 'log_conversion.url IS NOT NULL AND log_conversion.url != ""';
        $query = $logAggregator->queryConversionsByDimension($dimensions, $where);
        
        if ($query === false) {
            return 0;
        }

        $count = 0;
        while ($row = $query->fetch()) {
            if (!empty($row['url'])) {
                $count++;
            }
        }
        
        return $count;
    }
}