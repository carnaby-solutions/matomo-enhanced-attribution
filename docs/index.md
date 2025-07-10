## Documentation

### Requirements

- Matomo 5
- PHP 7.4 or higher
- MySQL/MariaDB database

### Installation

Install via the [Matomo Marketplace](https://plugins.matomo.org/EnhancedAttribution) or manually:


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
    "conversion_url": "https://carnaby.se/blog/google-this-and-that/",
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
    "conversion_url": "https://carnaby.se/article/vector-databases/",
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

### Code linting

Composer is setup to support [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer), a powerful and widely-used tool, to automatically format code and
ensure it adheres to modern coding standards like PSR-12.

`composer format` – This will automatically fix the coding style of your entire project.
`composer check-style` – This will show you which files need formatting without actually modifying them, which is useful for continuous integration (CI) checks.


### File Structure

- `EnhancedAttribution.php` - Main plugin class with event registration
- `API.php` - API methods for data retrieval
- `Commands/TestGoalUrlsPerformance.php` - Performance testing command

### Database Tables

The plugin primarily works with these Matomo tables:
- `matomo_log_conversion` - Goal conversion data
- `matomo_log_visit` - Visitor session data
- `matomo_log_link_visit_action` - Page view actions