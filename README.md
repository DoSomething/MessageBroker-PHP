mbc-logging-gateway
==================

Consumer for the Message Broker system that process logging entries. Uses the mb-logging-api to create entries in the Mongo mb-logging database.

Supported logging type:
- 'file-import'
- user-import-xxx:
  - 'user-import-niche'
  - 'user-import-att-ichannel'
  - 'user-import-hercampus'
  - 'user-import-teenlife'
- 'vote'
