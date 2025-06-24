# EnhancedAttribution Report Data Documentation

## GetGoalUrlsDetailed Report

This report provides comprehensive goal conversion data by joining conversion data with detailed visitor information from the `matomo_log_visit` table.

### Data Sources

The report combines data from:
- `matomo_log_conversion` - Goal conversion events
- `matomo_log_visit` - Visitor session data
- `matomo_goal` - Goal configuration and names

### Available Columns

#### Core Conversion Data
| Column | Description | Source Table |
|--------|-------------|--------------|
| `conversion_url` | Goal conversion URL | log_conversion.url |
| `goal_id` | Goal identifier | log_conversion.idgoal |
| `goal_name` | Goal display name | goal.name |
| `server_time` | Conversion timestamp | log_conversion.server_time |
| `date_s` | Conversion date (Y-m-d format) | Derived from server_time |
| `time_s` | Conversion time (H:i:s format) | Derived from server_time |
| `idvisit` | Visit identifier | log_conversion.idvisit |
| `idvisitor` | Visitor identifier (hex format) | log_conversion.idvisitor |

#### Traffic Source Data
| Column | Description | Source Table |
|--------|-------------|--------------|
| `channel` | Traffic channel (direct, search, campaign, social, website) | Derived from referer_type |
| `source` | Traffic source name | log_conversion.referer_name or campaign_source |
| `campaign_medium` | Campaign medium | log_conversion.campaign_medium |
| `campaign_name` | Campaign name | log_conversion.referer_name |

#### Visitor Behavior Data
| Column | Description | Source Table |
|--------|-------------|--------------|
| `visitor_count_visits` | Total visits by this visitor | log_visit.visitor_count_visits |
| `visitor_returning` | Visitor type (new/returning) | Derived from log_visit.visitor_returning |

#### Geographic Data
| Column | Description | Source Table |
|--------|-------------|--------------|
| `location_country` | Country code (ISO 3166-1 alpha-2) | log_visit.location_country |
| `location_city` | City name | log_visit.location_city |

#### Technical Data
| Column | Description | Source Table |
|--------|-------------|--------------|
| `config_os` | Operating system | log_visit.config_os |
| `config_browser_name` | Browser name | log_visit.config_browser_name |
| `config_device_type` | Device type (desktop, mobile, tablet) | log_visit.config_device_type |

### Channel Classification

The `channel` field is derived from `referer_type` using the following mapping:

| Referer Type | Channel Value | Description |
|--------------|---------------|-------------|
| `1` (DIRECT_ENTRY) | `direct` | Direct traffic (typed URL, bookmarks) |
| `2` (SEARCH_ENGINE) | `search` | Search engine referrals |
| `3` (WEBSITE) | `website` | Other website referrals |
| `6` (CAMPAIGN) | `campaign` | Campaign traffic (UTM parameters) |
| `7` (SOCIAL_NETWORK) | `social` | Social media referrals |

### Segment Support

The report supports Matomo's segment filtering functionality. Segments can be applied to filter conversions based on any available visitor or visit attributes.

**Example segment usage:**
- `location_country==SWE` - Only Swedish visitors
- `config_device_type==1` - Only desktop devices
- `visitor_returning==1` - Only returning visitors
- `config_browser_name==Chrome` - Only Chrome browser users

### Data Processing Notes

1. **Visitor ID Format**: The `idvisitor` is converted from binary to hexadecimal format for readability
2. **Returning Visitor Logic**: `visitor_returning` field is converted to human-readable values:
   - `1` → `"returning"`
   - `0` → `"new"`
3. **Date/Time Parsing**: `server_time` is parsed to separate date and time components
4. **Source Attribution**: Traffic source varies by channel type following Matomo's attribution rules
5. **Performance Optimization**: The current implementation uses a streamlined query that focuses on the most commonly used fields for better performance

### Performance Considerations

- The report uses an INNER JOIN between `log_conversion` and `log_visit` tables
- Large date ranges may impact performance due to the comprehensive data retrieval
- Consider using segments to limit result sets for better performance
- Date-based filtering is applied at the SQL level for efficiency

### API Usage

The report can be accessed via the API:

```
module=API&method=EnhancedAttribution.getGoalUrlsDetailed&idSite=1&period=month&date=2025-05&segment=location_country==SWE
```

### Display Configuration

The report is configured to display in the Goals section under the "Goal URLs" subcategory, providing a comprehensive view of goal conversions with rich visitor context.