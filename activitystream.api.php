<?php

/**
 * @file
 * Hooks provided by the Activity Stream module.
 *
 * Activity Stream aggregates web activity from external services (RSS feeds,
 * social networks, etc.) into Drupal activitystream_item entities. Modules
 * can register new service types, configure per-user credentials, fetch
 * items from remote APIs, and alter the display of activity items.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Declares one or more activity stream service types.
 *
 * Each key in the returned array is the machine name of the service (e.g.
 * "github", "mastodon"). This machine name is used throughout the module as
 * the $service parameter and forms part of the hook names for fetch/alter
 * callbacks.
 *
 * @return array
 *   An associative array of service definitions keyed by service machine name.
 *   Each definition is itself an associative array with the following keys:
 *   - type (string, required): The machine name of the service. Must match the
 *     array key.
 *   - name (string, required): Human-readable label for the service, shown in
 *     admin UI and permission labels.
 *   - verb (string, required): Past-tense action verb used in the activity
 *     statement, e.g. "posted", "starred", "checked in". Displayed as the
 *     middle word of the "Actor VERB Object" statement.
 *   - icon (string, required): Absolute or root-relative path to a small image
 *     file (PNG recommended, 16×16 or 32×32 px) that represents the service.
 *     Obtain this path via the module extension list service so the path
 *     remains correct regardless of Drupal installation subdirectory.
 *
 * @see activitystream_services_load()
 * @see hook_activitystream_SERVICE_items_fetch()
 *
 * @code
 * function mymodule_activitystream_services(): array {
 *   $module_path = \Drupal::service('extension.list.module')
 *     ->getPath('mymodule');
 *
 *   return [
 *     'github' => [
 *       'type'  => 'github',
 *       'name'  => t('GitHub'),
 *       'verb'  => t('pushed to'),
 *       'icon'  => '/' . $module_path . '/images/github.png',
 *     ],
 *     'mastodon' => [
 *       'type'  => 'mastodon',
 *       'name'  => t('Mastodon'),
 *       'verb'  => t('tooted'),
 *       'icon'  => '/' . $module_path . '/images/mastodon.png',
 *     ],
 *   ];
 * }
 * @endcode
 */
function hook_activitystream_services(): array {
  $module_path = \Drupal::service('extension.list.module')
    ->getPath('mymodule');

  return [
    'myservice' => [
      'type'  => 'myservice',
      'name'  => t('My Service'),
      'verb'  => t('posted'),
      'icon'  => '/' . $module_path . '/myservice.png',
    ],
  ];
}

/**
 * Adds per-user configuration fields to the activitystream accounts form.
 *
 * The accounts form is displayed at /user/{uid}/activity-stream/accounts and
 * allows each user to connect their external service credentials. Use this
 * hook to append your service's configuration fields (API key, username, feed
 * URL, etc.) to that form.
 *
 * To persist the submitted values, attach a custom submit handler via
 * $form['#submit']. Inside the handler, retrieve the values from
 * $form_state->getValues() and save them with activitystream_account_save().
 *
 * @param array $form
 *   The form array. $form['#user'] contains the UserInterface object for the
 *   account being edited, or NULL if the user cannot be determined.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current form state.
 *
 * @see activitystream_account_load()
 * @see activitystream_account_save()
 *
 * @code
 * function mymodule_form_activitystream_accounts_form_alter(
 *   array &$form,
 *   FormStateInterface $form_state,
 * ): void {
 *   /** @var \Drupal\user\UserInterface|null $user *\/
 *   $user = $form['#user'] ?? NULL;
 *   $uid  = $user instanceof UserInterface ? (int) $user->id() : NULL;
 *
 *   // Load previously saved credentials for this user.
 *   $defaults = activitystream_account_load('myservice', $uid) ?? [];
 *
 *   $form['myservice'] = [
 *     '#type'        => 'details',
 *     '#title'       => t('My Service'),
 *     '#open'        => !empty($defaults),
 *     '#tree'        => TRUE,
 *   ];
 *
 *   $form['myservice']['username'] = [
 *     '#type'          => 'textfield',
 *     '#title'         => t('Username'),
 *     '#default_value' => $defaults['username'] ?? '',
 *     '#description'   => t('Your My Service username.'),
 *   ];
 *
 *   $form['myservice']['api_key'] = [
 *     '#type'          => 'password',
 *     '#title'         => t('API key'),
 *     '#description'   => t('Leave blank to keep the existing key.'),
 *   ];
 *
 *   // Attach a submit handler that runs before the form is saved.
 *   $form['#submit'][] = 'mymodule_activitystream_accounts_form_submit';
 * }
 *
 * function mymodule_activitystream_accounts_form_submit(
 *   array &$form,
 *   FormStateInterface $form_state,
 * ): void {
 *   /** @var \Drupal\user\UserInterface|null $user *\/
 *   $user = $form['#user'] ?? NULL;
 *   $uid  = $user instanceof UserInterface ? (int) $user->id() : NULL;
 *
 *   $values = $form_state->getValue('myservice', []);
 *
 *   // Preserve the existing API key if the field was left blank.
 *   $existing = activitystream_account_load('myservice', $uid) ?? [];
 *   if (empty($values['api_key'])) {
 *     $values['api_key'] = $existing['api_key'] ?? '';
 *   }
 *
 *   activitystream_account_save('myservice', $values, $uid);
 * }
 * @endcode
 */
function hook_form_activitystream_accounts_form_alter(
  array &$form,
  FormStateInterface $form_state,
): void {
  /** @var \Drupal\user\UserInterface|null $user */
  $user = $form['#user'] ?? NULL;
  $uid  = $user instanceof UserInterface ? (int) $user->id() : NULL;

  $defaults = activitystream_account_load('myservice', $uid) ?? [];

  $form['myservice'] = [
    '#type'  => 'details',
    '#title' => t('My Service'),
    '#open'  => !empty($defaults),
    '#tree'  => TRUE,
  ];

  $form['myservice']['username'] = [
    '#type'          => 'textfield',
    '#title'         => t('Username'),
    '#default_value' => $defaults['username'] ?? '',
  ];

  $form['#submit'][] = 'hook_form_activitystream_accounts_form_alter_submit';
}

/**
 * Fetches activity items for a specific service.
 *
 * Replace SERVICE in the hook name with the machine name of your service as
 * registered in hook_activitystream_services(). For example, a service with
 * machine name "github" implements
 * hook_activitystream_github_items_fetch().
 *
 * This hook is called during cron and on manual refresh. It should contact
 * the remote API and return an array of normalised item arrays.
 *
 * Each item array must contain the following keys:
 * - title (string, required): Short human-readable title used as the entity
 *   label (e.g. the commit message, post title, or truncated status text).
 * - body (string, required): Full body text or HTML of the activity item.
 * - link (string, required): Canonical URL of the item on the external
 *   service (used for de-duplication and display).
 * - timestamp (int, required): Unix timestamp of when the activity occurred.
 * - guid (string, required): Globally unique identifier for the item as
 *   provided by the remote service. Used for de-duplication. If the service
 *   does not provide a GUID, use the canonical link URL.
 * - raw (string, desired): The verbatim service response for this item
 *   (serialised JSON, XML, etc.) stored in the activitystream_raw field for
 *   archiving and future re-processing. Omitting this key is allowed but
 *   discouraged.
 *
 * If the service provides an RSS or Atom feed, you may delegate to
 * activitystream_feed_items_fetch() provided by the activitystream_feed
 * sub-module instead of writing a custom HTTP client.
 *
 * @param int $uid
 *   The Drupal user ID for whom items are being fetched.
 * @param mixed $data
 *   The unserialized account data previously saved by
 *   activitystream_account_save() for this user and service. Typically an
 *   associative array with credentials or configuration.
 *
 * @return array
 *   An indexed array of item arrays (see above). Return an empty array if
 *   there are no new items or if the credentials are missing/invalid.
 *
 * @see hook_activitystream_services()
 * @see hook_activitystream_SERVICE_items_alter()
 * @see activitystream_items_fetch()
 *
 * @code
 * function mymodule_activitystream_myservice_items_fetch(
 *   int $uid,
 *   mixed $data,
 * ): array {
 *   if (empty($data['api_key']) || empty($data['username'])) {
 *     return [];
 *   }
 *
 *   try {
 *     $response = \Drupal::httpClient()->get(
 *       'https://api.myservice.example/users/' . $data['username'] . '/activity',
 *       ['headers' => ['Authorization' => 'Bearer ' . $data['api_key']]],
 *     );
 *     $body   = (string) $response->getBody();
 *     $events = json_decode($body, TRUE, 512, JSON_THROW_ON_ERROR);
 *   }
 *   catch (\Throwable $e) {
 *     \Drupal::logger('mymodule')->error(
 *       'My Service fetch failed for uid @uid: @message',
 *       ['@uid' => $uid, '@message' => $e->getMessage()],
 *     );
 *     return [];
 *   }
 *
 *   $items = [];
 *   foreach ($events as $event) {
 *     $items[] = [
 *       'title'     => $event['title'],
 *       'body'      => $event['body'] ?? '',
 *       'link'      => $event['url'],
 *       'timestamp' => strtotime($event['created_at']),
 *       'guid'      => $event['id'],
 *       'raw'       => json_encode($event),
 *     ];
 *   }
 *   return $items;
 * }
 * @endcode
 */
function hook_activitystream_SERVICE_items_fetch(int $uid, mixed $data): array {
  return [
    [
      'title'     => 'Example activity item',
      'body'      => 'Full description of the activity.',
      'link'      => 'https://myservice.example/item/1',
      'timestamp' => \Drupal::time()->getRequestTime(),
      'guid'      => 'myservice-item-1',
      'raw'       => '{"id":1,"title":"Example activity item"}',
    ],
  ];
}

/**
 * Alters the list of items fetched for a specific service.
 *
 * Replace SERVICE in the hook name with the machine name of your service as
 * registered in hook_activitystream_services(). For example, a service with
 * machine name "github" implements
 * hook_activitystream_github_items_alter().
 *
 * This hook runs after hook_activitystream_SERVICE_items_fetch() has been
 * called on all implementing modules. Use it to filter, reorder, enrich, or
 * reformat items before they are saved as entities.
 *
 * @param array $items
 *   The indexed array of item arrays assembled by fetch hooks. Alter in place.
 *   Each item has the same structure documented in
 *   hook_activitystream_SERVICE_items_fetch().
 * @param int $uid
 *   The Drupal user ID for whom items are being processed.
 * @param mixed $data
 *   The unserialized account data for this user and service.
 *
 * @see hook_activitystream_SERVICE_items_fetch()
 * @see activitystream_items_fetch()
 *
 * @code
 * function mymodule_activitystream_myservice_items_alter(
 *   array &$items,
 *   int $uid,
 *   mixed $data,
 * ): void {
 *   foreach ($items as &$item) {
 *     // Strip HTML from titles to keep them plain text.
 *     $item['title'] = strip_tags($item['title']);
 *
 *     // Append a UTM parameter to track clicks.
 *     $item['link'] .= '?utm_source=drupal_activitystream';
 *   }
 *   unset($item);
 *
 *   // Filter out items older than 30 days.
 *   $cutoff = \Drupal::time()->getRequestTime() - (30 * 24 * 60 * 60);
 *   $items  = array_filter($items, fn(array $i) => $i['timestamp'] >= $cutoff);
 *   // Re-index.
 *   $items  = array_values($items);
 * }
 * @endcode
 */
function hook_activitystream_SERVICE_items_alter(
  array &$items,
  int $uid,
  mixed $data,
): void {
  // Filter out items with no title.
  $items = array_filter($items, fn(array $item) => !empty($item['title']));
  $items = array_values($items);
}

/**
 * Modifies template variables for an activity stream item before rendering.
 *
 * This hook runs during preprocessing of the activitystream_item template
 * (activitystream-item.html.twig). It is invoked after
 * template_preprocess_activitystream_item() has populated the default
 * variables.
 *
 * Available variables in $variables:
 * - activitystream_item (\Drupal\activitystream\Entity\ActivityStreamItem):
 *   The fully-loaded entity for the item being rendered.
 * - service (array): The service definition array as returned by
 *   hook_activitystream_services(). Contains keys: type, name, verb, icon.
 * - statement (array): A render array describing the "Actor VERB Object"
 *   sentence. Contains three sub-keys:
 *   - actor: A render array for the username (theme: username).
 *   - verb: A render array with the service verb markup.
 *   - object: A render array for the link to the original item.
 * - service_icon (array): Render array for the service icon image.
 * - permalink_icon (array): Render array for the permalink icon link.
 * - external_icon (array): Render array for the external link icon.
 * - time_ago (array): Render array with a human-readable "X ago" string.
 *
 * @param array $variables
 *   An associative array of template variables. Modify in place.
 *
 * @see template_preprocess_activitystream_item()
 *
 * @code
 * function mymodule_preprocess_activitystream_item(array &$variables): void {
 *   /** @var \Drupal\activitystream\Entity\ActivityStreamItem $item *\/
 *   $item    = $variables['activitystream_item'];
 *   $service = $variables['service'];
 *
 *   // Override the verb with a service-specific label.
 *   if (($service['type'] ?? '') === 'myservice') {
 *     $variables['statement']['verb'] = [
 *       '#markup' => t('shared a track from'),
 *     ];
 *   }
 *
 *   // Append a custom CSS class to the actor element.
 *   $variables['statement']['actor']['#attributes']['class'][] = 'myservice-actor';
 *
 *   // Expose the item's raw field value to the template for custom rendering.
 *   $raw = $item->get('activitystream_raw')->value;
 *   if (!empty($raw)) {
 *     $decoded = json_decode($raw, TRUE);
 *     $variables['myservice_cover_url'] = $decoded['cover_image_url'] ?? '';
 *   }
 * }
 * @endcode
 */
function hook_preprocess_activitystream_item(array &$variables): void {
  /** @var \Drupal\activitystream\Entity\ActivityStreamItem $item */
  $item    = $variables['activitystream_item'];
  $service = $variables['service'];

  // Example: append the service name to the object link title.
  if (!empty($service['name'])) {
    $variables['statement']['object']['#suffix'] = ' — ' . $service['name'];
  }
}

/**
 * @} End of "addtogroup hooks".
 */
