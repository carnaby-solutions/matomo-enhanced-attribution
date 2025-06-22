<?php
/**
 *  Enhanced Attribution Base Report
 *
 * @link https://www.carnaby.se/matomo-enhanced-attribution/
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\EnhancedAttribution\Reports;

use Piwik\Plugin\Report;

abstract class Base extends Report
{
    protected function init()
    {
        $this->categoryId = 'Goals_Goals';
    }
}
