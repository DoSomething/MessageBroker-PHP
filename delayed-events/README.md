Delayed Events Consumer
=================

Message Broker - Consumer

A consumer app to manage Delayed Events on Mobile Commons.

- Listens for new signups and reportbacks
- When user is signing up for a campaign, we need to add them to `mobilecommons_group_doing` group in MoCo, [exposed in Gambit API](http://ds-mdata-responder-staging.herokuapp.com/v1/campaigns)
- When user reported back, we need to remove them from `mobilecommons_group_doing` and add to `mobilecommons_group_completed`
