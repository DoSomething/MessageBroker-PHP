mbp-logging-reports
===================

Producer for Message Broker system to generate scheduled reports based on the contents of the logging database.

####User Imports
- `$ php mbc-logging-reports.php [niche | afterschool]`

#####Use
Run as daily cron job to generate email reports and Slack alerts.

#####Import Budgets
See application constants:
```
  const NICHE_USER_BUDGET = 33333;
  const AFTERSCHOOL_USER_BUDGET = 'Unlimited';
```
for user import budgets that define alert triggers.
