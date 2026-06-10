# Activity Stream

[![CI](https://github.com/revagomes/drupal-activitystream_entity/actions/workflows/ci.yml/badge.svg?branch=2.0.x)](https://github.com/revagomes/drupal-activitystream_entity/actions/workflows/ci.yml)
[![Drupal 10.3+/11](https://img.shields.io/badge/Drupal-10.3%2B%20%7C%2011-0678BE.svg)](https://www.drupal.org/project/activitystream_entity)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)

**Activity Stream** is a Drupal module that aggregates your web activity from external services — RSS/Atom feeds, social networks, and any service you wire up — into native Drupal content entities. Each incoming item becomes an `activitystream_item` entity and is displayed as an activity statement of the form *Actor VERB Object*.

This is a lifestream / activity aggregator in the tradition of [Storytlr](https://github.com/storytlr/storytlr) and similar tools, built on Drupal's entity and plugin systems.

---

## Requirements

- PHP 8.1+
- Drupal 10.3 or 11

---

## Installation

Install via Composer, then enable the module with Drush:

```bash
composer require drupal/activitystream_entity
drush en activitystream -y
```

The module creates the `activitystream_item` entity type and the `activitystream_account` database table on install.

---

## Viewing activity streams

| URL | Description |
|-----|-------------|
| `/activity-stream` | Site-wide stream — all items from all users |
| `/user/{uid}/activity-stream` | Per-user stream |
| `/user/{uid}/activity-stream/accounts` | Per-user service account settings |

Both listing pages are paged and render each item through the `activitystream_item` theme hook (template: `activitystream-item.html.twig`).

---

## Adding new services

Any module can register a new service type and provide items for it.

1. **Declare the service** in `hook_activitystream_services()` — return a definition array with `type`, `name`, `verb`, and `icon` keys.

2. **Add per-user config fields** in `hook_form_activitystream_accounts_form_alter()` — append fields to the accounts form and attach a submit handler that calls `activitystream_account_save()`.

3. **Fetch items** in `hook_activitystream_SERVICE_items_fetch()` — contact the remote API and return normalised item arrays. Each item needs `title`, `body`, `link`, `timestamp`, `guid`, and ideally `raw`.

4. **Optionally alter items** in `hook_activitystream_SERVICE_items_alter()` — filter, enrich, or reorder items before they are saved.

5. **Optionally adjust rendering** in `hook_preprocess_activitystream_item()` — modify the statement render array, add template variables, etc.

Full documentation with working examples for every hook is in [`activitystream.api.php`](activitystream.api.php).

---

## Sub-modules

### `activitystream_feed`

Provides RSS/Atom feed ingestion via [SimplePie](https://simplepie.org/). Exposes a helper function `activitystream_feed_items_fetch()` that any service module can use instead of writing a custom HTTP + parsing layer.

Enable it alongside the core module:

```bash
drush en activitystream_feed -y
```

### `activitystream_twitter`

A stub sub-module that previously integrated with the Twitter API v1.1. **Twitter/X API v1.1 has been deprecated and is no longer freely available.** This sub-module is kept in the repository for historical reference only and does not function. It will be removed or replaced with an X API v2 implementation in a future release.

---

## Development

Install dev dependencies:

```bash
composer install
```

Run PHP CodeSniffer (Drupal + DrupalPractice standards):

```bash
vendor/bin/phpcs --standard=phpcs.xml.dist
```

Check all PHP files for syntax errors:

```bash
find src activitystream.module activitystream.install \
  activitystream_feed/activitystream_feed.module \
  activitystream_twitter/activitystream_twitter.module \
  -name '*.php' -exec php -l {} \;
```

CI runs on every push and pull request against `2.0.x` via [GitHub Actions](.github/workflows/ci.yml).

---

## Project links

- Drupal.org project page: <https://www.drupal.org/sandbox/revagomes/activitystream_entity>
- Issue queue: <https://www.drupal.org/project/issues/activitystream_entity>
- GitHub mirror: <https://github.com/revagomes/drupal-activitystream_entity>

---

## License

GPL-2.0-or-later. See [LICENSE](LICENSE) for the full text.
