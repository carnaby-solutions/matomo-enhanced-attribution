## Changelog

### 2.0.0 - 2026-11-14

- Matomo 6 support. Requires Matomo 6, PHP 8.1+ and MarketingCampaignsReporting 6.x
- Same fixes as 1.2.0 (segments, multiple-period dates, declared dependency)
- Action segments (e.g. `pageUrl`) no longer return too few rows when a limit is set
- Matomo 5 users: stay on 1.x

### 1.2.0 - 2026-11-14

- Segments are now actually applied (they were silently ignored before)
- Fixed `date=last7` / `previous30` and other multiple-period dates
- MarketingCampaignsReporting is now a declared dependency
- Matomo 6 is supported from version 2.0.0

### 1.1.0 - 2025-07-10

- Code cleanup, documentation added 

### 1.1.2 - 2025-07-10

- Renamed Class to conform with PSR-4 standards

