<?php
/**
 *  EnhancedAttribution.php
 *
 * Enhanced attribution tracking and goal URL analysis
 * @link https://www.carnaby.se/matomo/plugins/enhancedattribution/
 * 
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\EnhancedAttribution;

use Piwik\Plugins\EnhancedAttribution\RecordBuilders\GoalUrlAggregator;
use Piwik\Plugins\EnhancedAttribution\Commands\TestGoalUrlsPerformance;

class EnhancedAttribution extends \Piwik\Plugin
{
    public function registerEvents()
    {
        return [
            'CronArchive.getArchivingAPIMethodForPlugin' => 'getArchivingAPIMethodForPlugin',
            'Console.addCommands' => 'addConsoleCommands',
        ];
    }

    public function addConsoleCommands(&$commands)
    {
        $commands[] = new TestGoalUrlsPerformance();
    }

    // support archiving just this plugin via core:archive
    public function getArchivingAPIMethodForPlugin(&$method, $plugin)
    {
        if ($plugin == 'EnhancedAttribution') {
            $method = 'EnhancedAttribution.getGoalUrlsAggregate';
        }
    }

    /**
     * Register record builders for archiving
     */
    public function getRecordBuilders(): array
    {
        return [
            GoalUrlAggregator::class,
        ];
    }
}
