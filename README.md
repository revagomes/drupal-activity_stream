# Activity Stream

[![CI](https://github.com/revagomes/drupal-actstream/actions/workflows/ci.yml/badge.svg?branch=2.0.x)](https://github.com/revagomes/drupal-actstream/actions/workflows/ci.yml)
[![Drupal 10.3+/11](https://img.shields.io/badge/Drupal-10.3%2B%20%7C%2011-0678BE.svg)](https://www.drupal.org/project/actstream_entity)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html)

**Activity Stream** is a Drupal module that aggregates your web activity from external services — RSS/Atom feeds, social networks, and any service you wire up — into native Drupal content entities. Each incoming item becomes an `actstream_item` entity and is displayed as an activity statement of the form *Actor VERB Object*.

This is a lifestream / activity aggregator in the tradition of [Storytlr](https://github.com/storytlr/storytlr) and similar tools, built on Drupal's entity and plugin systems.

---

## Requirements

- PHP 8.1+
- Drupal 10.3 or 11

---

## Installation

Install via Composer, then enable the module with Drush:

```bash
composer require drupal/actstream
drush en actstream -y
```

The module creates the `actstream_item` entity type and the `actstream_account` database table on install.

---

## Viewing activity streams

| URL | Description |
|-----|-------------|
| `/actstream` | Site-wide stream — all items from all users |
| `/user/{uid}/actstream` | Per-user stream |
| `/user/{uid}/actstream/accounts` | Per-user service account settings |

Both listing pages are paged and render each item through the `actstream_item` theme hook (template: `actstream-item.html.twig`).

---

## Adding new services

Any module can register a new service type and provide items for it.

1. **Declare the service** in `hook_actstream_services()` — return a definition array with `type`, `name`, `verb`, and `icon` keys.

2. **Add per-user config fields** in `hook_form_actstream_accounts_form_alter()` — append fields to the accounts form and attach a submit handler that calls `actstream_account_save()`.

3. **Fetch items** in `hook_actstream_SERVICE_items_fetch()` — contact the remote API and return normalised item arrays. Each item needs `title`, `body`, `link`, `timestamp`, `guid`, and ideally `raw`.

4. **Optionally alter items** in `hook_actstream_SERVICE_items_alter()` — filter, enrich, or reorder items before they are saved.

5. **Optionally adjust rendering** in `hook_preprocess_actstream_item()` — modify the statement render array, add template variables, etc.

Full documentation with working examples for every hook is in [`actstream.api.php`](actstream.api.php).

---

## Sub-modules

### `actstream_feed`

Provides RSS/Atom feed ingestion via [SimplePie](https://simplepie.org/). Exposes a helper function `actstream_feed_items_fetch()` that any service module can use instead of writing a custom HTTP + parsing layer.

Enable it alongside the core module:

```bash
drush en actstream_feed -y
```

### `actstream_twitter`

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
find src actstream.module actstream.install \
  actstream_feed/actstream_feed.module \
  actstream_twitter/actstream_twitter.module \
  -name '*.php' -exec php -l {} \;
```

CI runs on every push and pull request against `2.0.x` via [GitHub Actions](.github/workflows/ci.yml).

---

## Project links

- Drupal.org project page: <https://www.drupal.org/sandbox/revagomes/actstream_entity>
- Issue queue: <https://www.drupal.org/project/issues/actstream_entity>
- GitHub mirror: <https://github.com/revagomes/drupal-actstream>

---

## License

GPL-2.0-or-later. See [LICENSE](LICENSE) for the full text.
