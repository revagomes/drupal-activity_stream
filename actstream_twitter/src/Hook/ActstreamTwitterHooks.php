<?php

namespace Drupal\actstream_twitter\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;

/**
 * Hook implementations for the actstream_twitter module.
 */
class ActstreamTwitterHooks {

  use StringTranslationTrait;

  /**
   * Constructs a new ActstreamTwitterHooks instance.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionList
   *   The module extension list.
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
    private readonly ModuleExtensionList $extensionList,
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
    $module_path = $this->extensionList->getPath('actstream_twitter');
    return [
      'twitter' => [
        'type' => 'twitter',
        'name' => $this->t('X (Twitter)'),
        'verb' => $this->t('posted'),
        'icon' => $module_path . '/twitter.png',
      ],
    ];
  }

  /**
   * Implements hook_actstream_twitter_items_fetch().
   */
  #[Hook('actstream_twitter_items_fetch')]
  public function fetchItems(int $uid, mixed $data): array {
    $bearer_token = $this->configFactory
      ->get('actstream_twitter.settings')
      ->get('bearer_token');

    // Normalise legacy storage (bare string) to array.
    if (is_string($data)) {
      $data = ['username' => $data];
    }

    if (empty($bearer_token) || empty($data['username'])) {
      return [];
    }

    $username = $data['username'];
    $logger = $this->loggerFactory->get('actstream_twitter');

    try {
      $headers = ['Authorization' => 'Bearer ' . $bearer_token];

      // Step 1: resolve username to numeric user ID.
      $user_response = $this->httpClient->get(
        'https://api.twitter.com/2/users/by/username/' . rawurlencode($username),
        ['headers' => $headers]
      );
      $user_payload = json_decode((string) $user_response->getBody(), TRUE);

      if (empty($user_payload['data']['id'])) {
        $logger->warning('X API: user not found for username @u.', ['@u' => $username]);
        return [];
      }
      $x_user_id = $user_payload['data']['id'];

      // Step 2: fetch the user's recent posts.
      $tweets_response = $this->httpClient->get(
        'https://api.twitter.com/2/users/' . $x_user_id . '/tweets',
        [
          'headers' => $headers,
          'query' => [
            'tweet.fields' => 'created_at,author_id',
            'max_results' => 20,
            'exclude' => 'retweets,replies',
          ],
        ]
      );
      $tweets_payload = json_decode((string) $tweets_response->getBody(), TRUE);

      if (empty($tweets_payload['data'])) {
        return [];
      }

      $items = [];
      foreach ($tweets_payload['data'] as $tweet) {
        $tweet_id = $tweet['id'];
        $post_url = 'https://x.com/' . rawurlencode($username) . '/status/' . $tweet_id;
        $timestamp = isset($tweet['created_at'])
          ? (int) (new \DateTimeImmutable($tweet['created_at']))->getTimestamp()
          : time();

        $items[] = [
          'title' => mb_substr($tweet['text'], 0, 255),
          'body' => $tweet['text'],
          'link' => $post_url,
          'timestamp' => $timestamp,
          'guid' => 'twitter:' . $tweet_id,
          'raw' => json_encode($tweet),
        ];
      }
      return $items;
    }
    catch (\Exception $e) {
      $logger->error('X API error for uid @uid: @msg', [
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
    $data = actstream_account_load('twitter', $uid);

    // Normalise legacy storage (bare string) to array.
    if (is_string($data)) {
      $data = ['username' => $data];
    }

    $form['actstream_twitter'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('X (Twitter)'),
    ];
    $form['actstream_twitter']['actstream_twitter_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('X (Twitter) username'),
      '#default_value' => $data['username'] ?? '',
      '#description' => $this->t('Your X username without the @ sign (e.g. drupal).'),
      '#field_prefix' => '@',
    ];

    $form['#submit'][] = [$this, 'formSubmit'];
  }

  /**
   * Form submit handler for the X (Twitter) account configuration.
   */
  public function formSubmit(array &$form, FormStateInterface $form_state): void {
    $user = $form['#user'];
    $uid = $user ? $user->id() : $this->currentUser->id();
    $username = trim(ltrim($form_state->getValue('actstream_twitter_username', ''), '@'));

    if ($username !== '') {
      actstream_account_save('twitter', ['username' => $username], $uid);
    }
    else {
      actstream_account_delete('twitter', $uid);
    }
  }

}
