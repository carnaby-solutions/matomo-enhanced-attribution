<?php
/**
 *  Enhanced Attribution Get Goal URLs Detailed
 *
 * @link https://www.carnaby.se/matomo-enhanced-attribution/
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */


# http://web/index.php?date=2022-07-12&module=EnhancedAttribution&format=html&action=getGoalUrls&period=month&idSite=1&segment=&widget=&showtitle=1&random=6045

namespace Piwik\Plugins\EnhancedAttribution\Reports;

use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;

/**
 * This class defines a new report.
 *
 * See {@link http://developer.piwik.org/api-reference/Piwik/Plugin/Report} for more information.
 */
class GetGoalUrlsDetailed extends Base
{
    protected function init()
    {
        parent::init();

        $this->name          = Piwik::translate('EnhancedAttribution_GoalUrls');
        $this->documentation = Piwik::translate('List goal urls');


#        $this->actionToLoadSubTables = $this->action;

        $toDisplay =     array_keys(array(
            'channel' => '',
            'source' => '',
            'campaign_medium' => '',
            'campaign_name' => '',
            'goal_id' => '',
            'goal_name' => '',
            'server_time' => '',
            'date_s' => '',
            'idvisitor' => '',
            'idvisit' => '',
            'time_s' => '',
            // Visit data columns
            'visitor_count_visits' => '',
            'visitor_returning' => '',
            'location_country' => '',
            'location_city' => '',
            'config_os' => '',
            'config_browser_name' => '',
            'config_device_type' => ''
        ));


        $this->order   = 2;
        $this->metrics = array_merge($toDisplay,array('conversion_url'));

        // By default standard metrics are defined but you can customize them by defining an array of metric names
        // $this->metrics       = array('nb_visits', 'nb_hits');

        // Uncomment the next line if your report does not contain any processed metrics, otherwise default
        // processed metrics will be assigned
       $this->processedMetrics =  array();

        // Uncomment the next line if your report defines goal metrics
        #´ $this->hasGoalMetrics = true;

        // Uncomment the next line if your report should be able to load subtables. You can define any action here
        // $this->actionToLoadSubTables = $this->action;

        // Uncomment the next line if your report always returns a constant count of rows, for instance always
        // 24 rows for 1-24hours
        // $this->constantRowsCount = true;

        // If a subcategory is specified, the report will be displayed in the menu under this menu item
        $this->subcategoryId = 'Goals_GoalUrls';

    }

    /**
     * Here you can configure how your report should be displayed. For instance whether your report supports a search
     * etc. You can also change the default request config. For instance change how many rows are displayed by default.
     *
     * @param ViewDataTable $view
     */


    public function configureView(ViewDataTable $view)
    {
        // The ViewDataTable must be configured so the display is perfect for the report.
        // We do this by modifying properties on the ViewDataTable::$config object.

        // Configure display settings following Matomo standards
        $view->config->show_table_all_columns = true;
        $view->config->addTranslation('conversion_url', 'Conversion URL');
        
        // Standard pagination controls (like Actions plugin)
        $view->config->show_limit_control = true;
        $view->config->show_pagination_control = true;
        $view->config->show_offset_information = true;
        
        // Set default rows to display (following Actions plugin standard)
        $view->requestConfig->filter_limit = 100;
        
        // Enable search and export functionality for large datasets
        $view->config->show_search = true;
        $view->config->show_export = true;
        $view->config->show_export_as_rss_feed = false;
        
        // Add translations for new columns
        $view->config->addTranslation('visitor_count_visits', Piwik::translate('EnhancedAttribution_visitor_count_visits'));
        $view->config->addTranslation('visitor_returning', Piwik::translate('EnhancedAttribution_visitor_returning'));
        $view->config->addTranslation('location_country', Piwik::translate('EnhancedAttribution_location_country'));
        $view->config->addTranslation('location_city', Piwik::translate('EnhancedAttribution_location_city'));
        $view->config->addTranslation('config_os', Piwik::translate('EnhancedAttribution_config_os'));
        $view->config->addTranslation('config_browser_name', Piwik::translate('EnhancedAttribution_config_browser_name'));
        $view->config->addTranslation('config_device_type', Piwik::translate('EnhancedAttribution_config_device_type'));

#        $view->config->columns_to_display = array_merge(array('label'), $this->metrics);

        $toDisplay =     array_keys(array(
            'channel' => '',
            'source' => '',
            'campaign_medium' => '',
            'campaign_name' => '',
            'goal_id' => '',
            'goal_name' => '',
            'server_time' => '',
            'date_s' => '',
            'idvisitor' => '',
            'idvisit' => '',
            'time_s' => '',
            // Visit data columns
            'visitor_count_visits' => '',
            'visitor_returning' => '',
            'location_country' => '',
            'location_city' => '',
            'config_os' => '',
            'config_browser_name' => '',
            'config_device_type' => ''
        ));

        $view->config->columns_to_display = array_merge(array('conversion_url'),  $toDisplay);

    }


    /**
     * Here you can define related reports that will be shown below the reports. Just return an array of related
     * report instances if there are any.
     *
     * @return \Piwik\Plugin\Report[]
     */
    public function getRelatedReports()
    {
        return array(); // eg return array(new XyzReport());
    }

    /**
     * A report is usually completely automatically rendered for you but you can render the report completely
     * customized if you wish. Just overwrite the method and make sure to return a string containing the content of the
     * report. Don't forget to create the defined twig template within the templates folder of your plugin in order to
     * make it work. Usually you should NOT have to overwrite this render method.
     *
     * @return string
    public function render()
    {
        $view = new View('@EnhancedAttribution/getGoalUrls');
        $view->myData = array();

        return $view->render();
    }
    */

    /**
     * By default your report is available to all users having at least view access. If you do not want this, you can
     * limit the audience by overwriting this method.
     *
     * @return bool
    public function isEnabled()
    {
        return Piwik::hasUserSuperUserAccess()
    }
     */
}
