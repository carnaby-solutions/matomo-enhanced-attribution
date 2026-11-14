<?php

/**
 *  EnhancedAttribution.php
 *
 * Enhanced attribution tracking and goal URL analysis
 * @link https://carnaby.solutions/enhanced-attribution-matomo-plugin/
 *
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\EnhancedAttribution;

use Piwik\Plugins\EnhancedAttribution\Commands\TestGoalUrlsPerformance;

/**/

class EnhancedAttribution extends \Piwik\Plugin
{
    /**
     * Register plugin events
     *
     * @return array
     */
    public function registerEvents()
    {
        return [
            'Console.addCommands' => 'addConsoleCommands',
        ];
    }

    /**
     * Add console commands to the application
     *
     * @param array $commands
     */
    public function addConsoleCommands(&$commands)
    {
        $commands[] = new TestGoalUrlsPerformance();
    }

}
