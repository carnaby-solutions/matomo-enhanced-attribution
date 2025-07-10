## FAQ

__How is "Goal url" defined__

The plugin use the traditional definion as used by other analytics suites: the URL of the page where a goal
conversion occurs. 

__What data is used to determine the "Goal url"__

The `url` field in the `log_conversion` table is used to determine the goal URL.

The [Matomo Database Schema](https://developer.matomo.org/guides/database-schema#conversions) describe this as "the URL that caused this conversion to be tracked". 

