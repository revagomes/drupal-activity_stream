<?php

namespace Drupal\actstream_facebook_page\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;

/**
 * Hook implementations for the actstream_facebook_page module.
 */
class ActstreamFacebookPageHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new ActstreamFacebookPageHooks instance.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly AccountProxyInterface $currentUser,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Implements hook_actstream_services().
   */
  #[Hook('actstream_services')]
  public function actstreamServices(): array {
    return [
      'facebook_page' => [
        'type' => 'facebook_page',
        'name' => $this->t('Facebook Page'),
        'verb' => $this->t('posted'),
        'icon' => 'https://www.facebook.com/favicon.ico',
      ],
    ];
  }

  /**
   * Implements hook_actstream_facebook_page_items_fetch().
   */
  #[Hook('actstream_facebook_page_items_fetch')]
  public function fetchItems(int $uid, mixed $data): array {
    $config = $this->configFactory->get('actstream_facebook_page.settings');
    $access_token = $config->get('access_token');
    $page_id = is_array($data) ? ($data['page_id'] ?? '') : (string) $data;

    if (empty($access_token) || empty($page_id)) {
      return [];
    }

    $logger = $this->loggerFactory->get('actstream_facebook_page');

    try {
      $url = 'https://graph.facebook.com/v19.0/' . rawurlencode($page_id) . '/posts?' . http_build_query([
        'fields' => 'id,message,story,created_time,permalink_url',
        'limit' => 20,
        'access_token' => $access_token,
      ]);

      $response = $this->httpClient->get($url);
      $payload = json_decode((string) $response->getBody(), TRUE);

      if (empty($payload['data'])) {
        return [];
      }

      $items = [];
      foreach ($payload['data'] as $post) {
        $text = $post['message'] ?? ($post['story'] ?? '');
        if (empty($text)) {
          continue;
        }
        $timestamp = isset($post['created_time'])
          ? (int) (new \DateTimeImmutable($post['created_time']))->getTimestamp()
          : time();

        $items[] = [
          'title' => mb_substr($text, 0, 255),
          'body' => $text,
          'link' => $post['permalink_url'] ?? '',
          'timestamp' => $timestamp,
          'guid' => 'fb:' . $post['id'],
          'raw' => json_encode($post),
        ];
      }
      return $items;
    }
    catch (\Exception $e) {
      $logger->error('Facebook Page API error for uid @uid: @msg', [
        '@uid' => $uid,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Implements hook_form_actstream_accounts_form_alter().
   */
  #[Hook('form_actstream_accounts_form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state): void {
    $user = $form['#user'];
    $uid = $user ? $user->id() : $this->currentUser->id();
    $data = actstream_account_load('facebook_page', $uid) ?: [];

    $form['actstream_facebook_page'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Facebook Page'),
    ];
    $form['actstream_facebook_page']['actstream_facebook_page_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Page ID'),
      '#default_value' => $data['page_id'] ?? '',
      '#description' => $this->t('The numeric ID or username slug of the Facebook Page (e.g. 123456789 or mypage).'),
    ];

    $form['#submit'][] = [$this, 'formSubmit'];
  }

  /**
   * Form submit handler for the Facebook Page account configuration.
   */
  public function formSubmit(array &$form, FormStateInterface $form_state): void {
    $user = $form['#user'];
    $uid = $user ? $user->id() : $this->currentUser->id();
    $page_id = trim($form_state->getValue('actstream_facebook_page_id') ?? '');

    if ($page_id !== '') {
      actstream_account_save('facebook_page', ['page_id' => $page_id], $uid);
    }
    else {
      actstream_account_delete('facebook_page', $uid);
    }
  }

}
