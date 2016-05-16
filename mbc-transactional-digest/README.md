## mbc-transactional-digest

### Transactional -> Batch Messaging

When a user signs up for a campaign a transactional message request is sent to the `transactionalExchange`. The `transactionalDigestQueue` is connected to the `transactionalExchange` with the `campaign.signup.transactional` binding key. All campaign sign up transactional message requests will be processed on time intervals to produce a single digest message for the time period.

### Installation

**Production**
- `$ composer install --no-dev`
**Development**
- `*composer install --dev`

### Update

- `$ composer update`