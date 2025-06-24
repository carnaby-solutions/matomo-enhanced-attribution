# Matomo EnhancedAttribution Plugin

https://www.carnaby.se/matomo-enhanced-attribution/

## Description

Enhanced attribution tracking and goal URL analysis. 

## Features

- **Enhanced Goal URL Analysis**: Detailed tracking of goal conversion URLs with visitor data
- **Visitor Attribution**: Indicator for return visits, source, medium & channel 
- **Geographic Data**: Country, region, and city information for goal conversions
- **Technical Information**: Operating system, browser, device type, and screen resolution data

## Requirements

- Matomo 4.0.0 or higher (compatible up to Matomo 6.0.0)
- PHP 7.4 or higher
- MySQL/MariaDB database

## Installation

1. Copy the plugin files to your Matomo installation:
   ```
   /path/to/matomo/plugins/EnhancedAttribution/
   ```

2. Activate the plugin through the Matomo admin interface or via console:
   ```bash
   ./console plugin:activate EnhancedAttribution
   ```

## API Methods

### getGoalUrlsDetailed

Returns detailed goal URL data with visitor information and segment support.

**Parameters:**
- `idSite` (int): Site ID
- `period` (string): Period type ('day', 'week', 'month', 'year', 'range')
- `date` (string): Date or date range
- `segment` (string, optional): Matomo segment definition
- `limit` (int, optional): Maximum number of rows to return (0 = no limit)

**Example API call:**
```
?module=API&method=EnhancedAttribution.getGoalUrlsDetailed&idSite=1&period=day&date=2025-05-15&format=json
```

**Example return data:**
```json
[
  {
    "label": "https://carnaby.se/checkout/success",
    "channel": "campaign",
    "source": "google",
    "campaign_medium": "cpc",
    "campaign_name": "summer_sale_2025",
    "goal_id": "1",
    "goal_name": "Purchase Completed",
    "server_time": "2025-05-15 14:32:18",
    "date_s": "2025-05-15",
    "time_s": "14:32:18",
    "idvisit": "12345",
    "idvisitor": "a1b2c3d4e5f6789",
    "visitor_count_visits": "3",
    "visitor_returning": "returning",
    "location_country": "se",
    "location_city": "Stockholm",
    "config_os": "WIN",
    "config_browser_name": "CH",
    "config_device_type": "0"
  },
  {
    "conversion_url": "https://carnaby.se/newsletter/signup",
    "channel": "social",
    "source": "facebook",
    "campaign_medium": "",
    "campaign_name": "",
    "goal_id": "2",
    "goal_name": "Newsletter Signup",
    "server_time": "2025-05-15 09:15:42",
    "date_s": "2025-05-15",
    "time_s": "09:15:42",
    "idvisit": "67890",
    "idvisitor": "x9y8z7w6v5u4321",
    "visitor_count_visits": "1",
    "visitor_returning": "new",
    "location_country": "us",
    "location_city": "New York",
    "config_os": "MAC",
    "config_browser_name": "SF",
    "config_device_type": "0"
  },
  {
    "conversion_url": "https://carnaby.se/contact/form-submitted",
    "channel": "direct",
    "source": "-",
    "campaign_medium": "",
    "campaign_name": "",
    "goal_id": "3",
    "goal_name": "Contact Form",
    "server_time": "2025-05-15 16:45:12",
    "date_s": "2025-05-15",
    "time_s": "16:45:12",
    "idvisit": "54321",
    "idvisitor": "m1n2o3p4q5r6890",
    "visitor_count_visits": "2",
    "visitor_returning": "returning",
    "location_country": "gb",
    "location_city": "London",
    "config_os": "IOS",
    "config_browser_name": "MF",
    "config_device_type": "1"
  }
]
```

## Data Fields

The plugin enriches goal conversion data with the following fields:

- **Visitor Behavior**: Visit count, returning visitor status, time since first visit
- **Geographic Data**: Country, region, city
- **Technical Info**: OS, browser, device type, screen resolution
- **Conversion Data**: Goal URL, conversion timestamp, referrer information

## Console Commands

### test:goalurls-performance

Performance testing command for goal URL queries.

```bash
./console enhancedattribution:test-goalurls-performance
```

## Development

### File Structure

- `EnhancedAttribution.php` - Main plugin class with event registration
- `API.php` - API methods for data retrieval
- `Archiver.php` - Legacy archiver (deprecated, replaced by record builders)
- `RecordBuilders/GoalUrlAggregator.php` - Modern archiving logic
- `Commands/TestGoalUrlsPerformance.php` - Performance testing command

### Database Tables

The plugin primarily works with these Matomo tables:
- `matomo_log_conversion` - Goal conversion data
- `matomo_log_visit` - Visitor session data
- `matomo_log_link_visit_action` - Page view actions

## License

GPL v3 or later

## Support

For issues and support, please refer to the plugin's issue tracker or Matomo community forums.

