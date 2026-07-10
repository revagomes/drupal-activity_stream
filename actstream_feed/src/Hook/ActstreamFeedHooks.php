<?php

namespace Drupal\actstream_feed\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the actstream_feed module.
 */
class ActstreamFeedHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new ActstreamFeedHooks instance.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionList
   *   The module extension list.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    private readonly ModuleExtensionList $extensionList,
    private readonly FileSystemInterface $fileSystem,
    private readonly AccountProxyInterface $currentUser,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Implements hook_actstream_services().
   */
  #[Hook('actstream_services')]
  public function actstreamServices(): array {
    $module_path = $this->extensionList->getPath('actstream_feed');
    return [
      'feed' => [
        'type' => 'feed',
        'name' => $this->t('Feed'),
        'verb' => $this->t('posted'),
        'icon' => $module_path . '/feed.png',
      ],
    ];
  }

  /**
   * Implements hook_actstream_feed_items_fetch().
   */
  #[Hook('actstream_feed_items_fetch')]
  public function fetchItems(int $uid, mixed $feed_urls): array {
    $feed_urls = is_array($feed_urls) ? $feed_urls : [$feed_urls];
    $items = [];
    $temp_dir = $this->fileSystem->getTempDirectory();
    $logger = $this->loggerFactory->get('actstream_feed');

    foreach ($feed_urls as $feed_url) {
      $feed_url = trim($feed_url);
      if (empty($feed_url)) {
        continue;
      }

      $feed = new \SimplePie();
      $feed->set_cache_location($temp_dir);
      $feed->set_cache_duration(300);
      $feed->set_useragent('Activity Stream for Drupal');
      $feed->set_feed_url($feed_url);

      if (!$feed->init()) {
        $logger->error('Feed fetch error for @url: @error', [
          '@url' => $feed_url,
          '@error' => $feed->error(),
        ]);
        continue;
      }

      foreach ($feed->get_items() as $feed_item) {
        $items[] = [
          'title' => $feed_item->get_title(),
          'body' => $feed_item->get_description(),
          'timestamp' => $feed_item->get_date('U'),
          'guid' => $feed_item->get_id(FALSE),
          'raw' => $feed->get_raw_data(),
          // SimplePie HTML-encodes links; undo that since Drupal will encode again.
          'link' => Html::decodeEntities($feed_item->get_permalink()),
        ];
      }
    }

    return $items;
  }

  /**
   * Implements hook_form_actstream_accounts_form_alter().
   */
  #[Hook('form_actstream_accounts_form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    $user = $form['#user'];
    $uid = $user ? $user->id() : $this->currentUser->id();
    $feed_urls = actstream_account_load('feed', $uid);
    $feed_urls_string = is_array($feed_urls) ? implode("\n", $feed_urls) : '';

    $form['actstream_feed'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Feed'),
    ];
    $form['actstream_feed']['actstream_feed_urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Feed URLs'),
      '#description' => $this->t('Multiple RSS or Atom feeds are allowed; place each on a separate line.'),
      '#default_value' => $feed_urls_string,
    ];

    $form['#submit'][] = [$this, 'formSubmit'];
  }

  /**
   * Form submit handler for the feed account configuration.
   */
  public function formSubmit(array &$form, FormStateInterface $form_state): void {
    $user = $form['#user'];
    $uid = $user ? $user->id() : $this->currentUser->id();
    $urls_value = $form_state->getValue('actstream_feed_urls');
    if (!empty($urls_value)) {
      $feed_urls = array_filter(array_map('trim', explode("\n", str_replace('feed://', 'http://', $urls_value))));
      actstream_account_save('feed', $feed_urls, $uid);
    }
    else {
      actstream_account_delete('feed', $uid);
    }
  }

}
