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

/**
 * API for plugin EnhancedAttribution
 *
 * @method static \Piwik\Plugins\EnhancedAttribution\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * Returns start & end dates for the range described by a period and optional lastN
     * argument.
     *
     * Handles single periods (date=2025-05-15), ranges (period=range&date=2025-05-01,2025-05-31)
     * and multiple periods (date=last7, date=previous30, date=2025-05-01,2025-05-31).
     *
     * @param string $date
     * @param string $period The period type ('day', 'week', 'month', 'year' or 'range').
     * @param mixed $lastN Not used, kept for backwards compatibility.
     *
     * @return Date[] [start, end]
     */
    public static function getDateRangeForPeriod($date, $period, $lastN = false)
    {
        if ($period == 'range') {
            $oPeriod = new Period\Range('day', $date);
        } elseif (Period::isMultiplePeriod($date, $period)) {
            $oPeriod = new Period\Range($period, $date);
        } else {
            $oPeriod = Period\Factory::build($period, Date::factory($date));
        }

        return [$oPeriod->getDateStart(), $oPeriod->getDateEnd()];
    }


    /**
     * Get goal URLs detailed with visit data and segment support
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @param int $limit Optional limit for number of rows (0 = no limit)
     * @return DataTable
     */
    public function getGoalUrlsDetailed($idSite, $period, $date, $segment = false, $limit = 0)
    {

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

        $bind = [$idSite, $startDateStr . ' 00:00:00', $endDateStr . ' 23:59:59'];


        $rows = Db::fetchAll($sql, $bind);


        $sql = 'SELECT idgoal, name, idsite FROM '
            . Common::prefixTable('goal') . " WHERE idsite = ? ";

        $goalData = Db::fetchAll($sql, [$idSite]);
        # print_r($goalData);
        $goals = [];
        foreach ($goalData as $sepGoal) {
            $goals[$sepGoal['idgoal']] = $sepGoal['name'];

        }
        #        print_r($goals);
        $table = new DataTable();

        // Cache channel matrix outside loop for performance
        $channelMatrix = [
            Common::REFERRER_TYPE_DIRECT_ENTRY => 'direct',
            Common::REFERRER_TYPE_SEARCH_ENGINE => 'search',
            Common::REFERRER_TYPE_CAMPAIGN => 'campaign',
            Common::REFERRER_TYPE_SOCIAL_NETWORK => 'social',
            Common::REFERRER_TYPE_WEBSITE => 'website',
        ];

        foreach ($rows as $row) {
            // Optimize: only populate fields that have data, avoid massive empty array initialization
            $serverTime = $row['server_time'];
            $timestamp = strtotime($serverTime);

            // Process current visit attribution first
            $source = '';
            $campaignMedium = '';
            $campaignName = '';

            switch ($row['referer_type']) {
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

            $returnData = [
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
            ];

            $table->addRowFromArray([Row::COLUMNS => $returnData]);
        }

        return $table;
    }

}
