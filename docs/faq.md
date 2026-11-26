## FAQ

__How is "Goal url" defined__

The plugin use the traditional definion as used by other analytics suites: the URL of the page where a goal
conversion occurs. 

__What data is used to determine the "Goal url"__

The `url` field in the `log_conversion` table is used to determine the goal URL.

The [Matomo Database Schema](https://developer.matomo.org/guides/database-schema#conversions) describe this as "the URL that caused this conversion to be tracked". 

__Why is MarketingCampaignsReporting required?__

The campaign source and medium for each conversion are stored in columns that the
[MarketingCampaignsReporting](https://plugins.matomo.org/MarketingCampaignsReporting) plugin adds to the
`log_conversion` table. Without it those columns don't exist, so Matomo won't let you activate EnhancedAttribution
until MarketingCampaignsReporting is installed and activated.

__Why does the report show only 100 rows?__

Matomo's API returns at most 100 rows by default. Add `filter_limit=-1` to get all conversions, or e.g.
`filter_limit=500` for a specific number.

__Do I need to run archiving (`core:archive`) to see data?__

No. The report reads conversions directly from Matomo's raw log tables, so new conversions show up right away,
including today's.

__Why don't old conversions show up anymore?__

Because the report reads raw log data. If *Regularly delete old raw data* is enabled (Administration → Privacy →
Anonymize data), conversions older than that limit are gone from this report, even though Matomo's archived Goals
reports still show the totals.

__Why are some conversions missing compared to the Goals report?__

Only conversions with a recorded URL are included. Conversions tracked without a URL (for example server-side
tracking that doesn't send `url`) are skipped. Also keep in mind that this report lists individual conversions,
while the Goals reports show aggregated numbers.

__Which attribution model is used for source and channel?__

The one Matomo already uses for goal conversions: the referrer Matomo stored with the conversion. EnhancedAttribution
doesn't re-attribute anything, it shows Matomo's attribution per conversion URL.

__Can I use segments, including page segments like `pageUrl=@/pricing`?__

Yes, all Matomo segments work, including action segments. A conversion is only listed once, even if several pages in
the visit match the segment.

__Can I use `date=last30` or `previous7`?__

Yes. Multiple-period dates (`lastN`, `previousN`) and `period=range` return all conversions in the whole span as one
list.

__Which plugin version do I need?__

Matomo 6: version 2.x. Matomo 5: version 1.x. The Marketplace offers the right one automatically.

__How do I get the data into Excel, Looker Studio or Power BI?__

Use the API with `format=csv` (or `json`), `filter_limit=-1` and a `token_auth` with view access to the site:

```
https://matomo.example.com/index.php?module=API&method=EnhancedAttribution.getGoalUrlsDetailed&idSite=1&period=month&date=2026-11-01&format=csv&filter_limit=-1&token_auth=YOUR_TOKEN
```

__Will large date ranges be slow?__

The report queries raw logs, so a full year on a busy site can take a while. Narrow it down with a segment or a
shorter period. You can time the query with
`./console enhancedattribution:test-performance --date=YYYY-MM-DD`.

__Does the report contain personal data?__

It includes the visitor ID (`idvisitor`), city and the exact time of each conversion, so treat exports the way you
treat other visitor-level data under GDPR. What the report shows depends on what Matomo has stored, so Matomo's
anonymization settings apply here too.
