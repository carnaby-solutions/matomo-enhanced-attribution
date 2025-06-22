<?php
/**
 *  Enhanced Attribution API
 *
 * @link  https://www.carnaby.se/matomo-enhanced-attribution/
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\EnhancedAttribution;

use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Date;
use Piwik\Period;
use Piwik\Common;
use Piwik\Db;
use Piwik\Segment;
use Piwik\Archive;
use Piwik\Plugins\EnhancedAttribution\RecordBuilders\GoalUrlAggregator;

/**
 * API for plugin EnhancedAttribution
 *
 * @method static \Piwik\Plugins\EnhancedAttribution\API getInstance()
 */
class API extends \Piwik\Plugin\API
{



#http://web/index.php?module=API&action=index&date=2022-07-12&period=day&idSite=1#?period=month&date=2022-07-12&idSite=1&category=Goals_Goals&subcategory=Goals_GoalUrls
#    http://web/index.php?module=API&method=EnhancedAttribution.getAnswerToLife.


    /**
     * Returns start & end dates for the range described by a period and optional lastN
     * argument.
     *
     * @param string|bool $date The start date of the period (or the date range of a range
     *                           period).
     * @param string $period The period type ('day', 'week', 'month', 'year' or 'range').
     * @param bool|int $lastN Whether to include the last N periods in the range or not.
     *                         Ignored if period == range.
     *
     * @return Date[]   array of Date objects or array(false, false)
     */
    public static function getDateRangeForPeriod($date, $period, $lastN = false)
    {
        $lastN = false;
        if ($date === false) {
            return array(false, false);
        }

        $isMultiplePeriod = Period\Range::isMultiplePeriod($date, $period);

        // if the range is just a normal period (or the period is a range in which case lastN is ignored)
        if ($period == 'range') {
            $oPeriod = new Period\Range('day', $date);
            $startDate = $oPeriod->getDateStart();
            $endDate = $oPeriod->getDateEnd();
        } else if ($lastN == false && !$isMultiplePeriod) {
            $oPeriod = Period\Factory::build($period, Date::factory($date));
            $startDate = $oPeriod->getDateStart();
            $endDate = $oPeriod->getDateEnd();
        } else { // if the range includes the last N periods or is a multiple period
            if (!$isMultiplePeriod) {
                list($date, $lastN) = EvolutionViz::getDateRangeAndLastN($period, $date, $lastN);
            }
            list($startDate, $endDate) = explode(',', $date);

            $startDate = Date::factory($startDate);
            $endDate = Date::factory($endDate);
        }
        return array($startDate, $endDate);
    }


    /**
     * Get goal URLs detailed with visit data and segment support
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @param int $limit Optional limit for number of rows (0 = no limit)
     * @return DataTable
     */
    public function getGoalUrlsDetailed($idSite, $period, $date, $segment = false, $limit = 0)
    {
        // Use Archive to get pre-processed data if segments are involved
        if (!empty($segment)) {
            // For segments, use the archived aggregated data which properly handles segmentation
            return $this->getGoalUrlsAggregate($idSite, $period, $date, $segment);
        }

        list($startDate, $endDate) = $this->getDateRangeForPeriod($date, $period, false);
        $startDateStr = $startDate->toString();
        $endDateStr = $endDate->toString();
        
        // Check for common request parameters that might limit results
        $filterLimit = Common::getRequestVar('filter_limit', -1, 'int');
        if ($filterLimit > 0) {
            $limit = $filterLimit;
        }

        // FASTER: Remove rarely used fields for additional performance gain
        $sql = 'SELECT 
                    c.idgoal, 
                    c.idvisit, 
                    c.server_time, 
                    CONV(HEX(c.idvisitor), 16, 16) AS visitorId,
                    c.url, 
                    c.referer_name, 
                    c.referer_type, 
                    c.campaign_medium, 
                    c.campaign_source,
                    v.visitor_count_visits,
                    v.visitor_returning,
                    v.location_country,
                    v.location_city,
                    v.config_os,
                    v.config_browser_name,
                    v.config_device_type
                FROM ' . Common::prefixTable('log_conversion') . ' c
                INNER JOIN ' . Common::prefixTable('log_visit') . ' v ON c.idvisit = v.idvisit
                WHERE c.idsite = ? 
                  AND c.url IS NOT NULL 
                  AND c.url != ""
                  AND c.server_time >= ? 
                  AND c.server_time <= ?
                ORDER BY c.server_time DESC' . ($limit > 0 ? ' LIMIT ' . (int)$limit : '');

        $bind = array($idSite, $startDateStr . ' 00:00:00', $endDateStr . ' 23:59:59');
        $rows = Db::fetchAll($sql, $bind);


        $sql =  'SELECT idgoal, name, idsite FROM '
            .  Common::prefixTable('goal') . " WHERE idsite = ? ";

        $goalData= Db::fetchAll($sql, array($idSite));
        #print_r($goalData);
        $goals = array();
        foreach ($goalData as $sepGoal) {
            $goals[$sepGoal['idgoal']] = $sepGoal['name'];

        }
#        print_r($goals);
        $table = new DataTable();
        
        // Cache channel matrix outside loop for performance
        $channelMatrix = array(
            Common::REFERRER_TYPE_DIRECT_ENTRY   => 'direct',
            Common::REFERRER_TYPE_SEARCH_ENGINE  => 'search',
            Common::REFERRER_TYPE_CAMPAIGN       => 'campaign',
            Common::REFERRER_TYPE_SOCIAL_NETWORK => 'social',
            Common::REFERRER_TYPE_WEBSITE        => 'website',
        );

        foreach ($rows as $row) {
            // Optimize: only populate fields that have data, avoid massive empty array initialization
            $serverTime = $row['server_time'];
            $timestamp = strtotime($serverTime);
            
            // Process current visit attribution first
            $source = '';
            $campaignMedium = '';
            $campaignName = '';
            
            switch($row['referer_type']) {
                case Common::REFERRER_TYPE_DIRECT_ENTRY:
                    $source = '-';
                    break;
                case Common::REFERRER_TYPE_SEARCH_ENGINE:
                case Common::REFERRER_TYPE_SOCIAL_NETWORK:
                case Common::REFERRER_TYPE_WEBSITE:
                    $source = $row['referer_name'] ?? '';
                    break;
                case Common::REFERRER_TYPE_CAMPAIGN:
                    $source = $row['campaign_source'] ?? '';
                    $campaignMedium = $row['campaign_medium'] ?? '';
                    $campaignName = $row['referer_name'] ?? '';
                    break;
            }
            
            $returnData = array(
                'conversion_url' => $row['url'],
                'channel' => $channelMatrix[$row['referer_type']] ?? 'unknown',
                'source' => $source,
                'campaign_medium' => $campaignMedium,
                'campaign_name' => $campaignName,
                'goal_id' => $row['idgoal'],
                'goal_name' => $goals[$row['idgoal']] ?? '',
                'server_time' => $serverTime,
                'date_s' => date('Y-m-d', $timestamp),
                'time_s' => date('H:i:s', $timestamp),
                'idvisit' => $row['idvisit'],
                'idvisitor' => $row['visitorId'],
                'visitor_count_visits' => $row['visitor_count_visits'] ?? '',
                'visitor_returning' => $row['visitor_returning'] ? 'returning' : 'new',
                'location_country' => $row['location_country'] ?? '',
                'location_city' => $row['location_city'] ?? '',
                'config_os' => $row['config_os'] ?? '',
                'config_browser_name' => $row['config_browser_name'] ?? '',
                'config_device_type' => $row['config_device_type'] ?? ''
            );

#            print_r($returnData);


            $table->addRowFromArray(array(Row::COLUMNS => $returnData));
        }

 #       die;
#        $table->addRowFromArray(array(Row::COLUMNS => array('label'=>'https://www.exempel.org', 'cust_goals' => 33,'cust_source'=>'ex1','cust_medium' =>'ex2')));



 #       $subTable1 = new DataTable();

#        $subTable1->addRowFromArray(array(Row::COLUMNS => array('label'=>'Superbra', 'cust_goals' => 321)));

  #    $rowSub = new Row();
 #       $rowSub->setSubtable($subTable1);
 #       $table->addRow($rowSub);


        return $table;
    }

    /**
     * Get aggregated goal URLs data from archived records
     * 
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getGoalUrlsAggregate($idSite, $period, $date, $segment = false)
    {
        $archive = Archive::build($idSite, $period, $date, $segment);
        $dataTable = $archive->getDataTable(GoalUrlAggregator::GOAL_URLS_AGGREGATE_RECORD_NAME);
        
        return $dataTable;
    }

    /**
     * Get goal URLs aggregated by channel
     * 
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getGoalUrlsByChannel($idSite, $period, $date, $segment = false)
    {
        $archive = Archive::build($idSite, $period, $date, $segment);
        $dataTable = $archive->getDataTable(GoalUrlAggregator::GOAL_URLS_BY_CHANNEL_RECORD_NAME);
        
        return $dataTable;
    }

    /**
     * Get goal URLs aggregated by source
     * 
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getGoalUrlsBySource($idSite, $period, $date, $segment = false)
    {
        $archive = Archive::build($idSite, $period, $date, $segment);
        $dataTable = $archive->getDataTable(GoalUrlAggregator::GOAL_URLS_BY_SOURCE_RECORD_NAME);
        
        return $dataTable;
    }

    /**
     * Get total goal conversions count
     * 
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return int
     */
    public function getTotalGoalConversions($idSite, $period, $date, $segment = false)
    {
        $archive = Archive::build($idSite, $period, $date, $segment);
        $value = $archive->getNumeric('EnhancedAttribution_total_goal_conversions');
        
        return (int) $value;
    }

    /**
     * Get unique goal URLs count
     * 
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return int
     */
    public function getUniqueGoalUrls($idSite, $period, $date, $segment = false)
    {
        $archive = Archive::build($idSite, $period, $date, $segment);
        $value = $archive->getNumeric('EnhancedAttribution_unique_goal_urls');
        
        return (int) $value;
    }
}
