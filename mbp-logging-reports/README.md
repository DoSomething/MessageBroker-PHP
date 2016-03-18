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

### User Import Ststem

- [Introduction](https://github.com/DoSomething/mbp-user-import/wiki)
- [Architecture](https://github.com/DoSomething/mbp-user-import/wiki/2.-Architecture)
- [Setup](https://github.com/DoSomething/mbp-user-import/wiki/3.-Setup)
- [Operation](https://github.com/DoSomething/mbp-user-import/wiki/4.-Operation)
- [Monitoring](https://github.com/DoSomething/mbp-user-import/wiki/5.-Monitoring)
- [Problems / Solutions](https://github.com/DoSomething/mbp-user-import/wiki/7.-Problems-%5C--Solutions)

#### 1. [mbp-user-import](https://github.com/DoSomething/mbp-user-import)

An application (producer) in the Quicksilver (Message Broker) system. Imports user data from CVS formatted files that create message entries in the `userImportQueue`.

#### 2. [mbc-user-import](https://github.com/DoSomething/mbc-user-import)

An application (consumer) in the Quicksilver (Message Broker) system. Processes user data import messages in the `userImportQueue`.

#### 3. [mbp-logging-reports](https://github.com/DoSomething/Quicksilver-PHP/tree/master/mbp-logging-reports)

Generate reports of the on going user import process. Reports are sent through email and Slack.
