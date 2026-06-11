<?php

namespace Drupal\activity_stream\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\activity_stream\ActivityStreamItemAccessControlHandler;
use Drupal\activity_stream\ActivityStreamItemListBuilder;
use Drupal\activity_stream\Form\ActivityStreamItemForm;

/**
 * Defines the Activity Stream item entity type.
 */
#[ContentEntityType(
  id: 'activity_stream_item',
  label: new TranslatableMarkup('Activity Stream item'),
  label_singular: new TranslatableMarkup('activity stream item'),
  label_plural: new TranslatableMarkup('Activity Stream items'),
  label_collection: new TranslatableMarkup('Activity Stream items'),
  base_table: 'activity_stream_item',
  entity_keys: [
    'id' => 'id',
    'label' => 'title',
    'langcode' => 'langcode',
    'uuid' => 'uuid',
    'uid' => 'uid',
    'status' => 'status',
  ],
  handlers: [
    'storage' => SqlContentEntityStorage::class,
    'view_builder' => EntityViewBuilder::class,
    'list_builder' => ActivityStreamItemListBuilder::class,
    'form' => [
      'default' => ActivityStreamItemForm::class,
      'delete' => ContentEntityDeleteForm::class,
    ],
    'access' => ActivityStreamItemAccessControlHandler::class,
  ],
  links: [
    'canonical' => '/activity_stream/{activity_stream_item}',
    'edit-form' => '/activity_stream/{activity_stream_item}/edit',
    'delete-form' => '/activity_stream/{activity_stream_item}/delete',
    'collection' => '/admin/content/activity_stream',
  ],
  admin_permission: 'administer activity_stream',
)]
class ActivityStreamItem extends ContentEntityBase implements ActivityStreamItemInterface {

  /**
   * {@inheritdoc}
   */
  public function getService(): string {
    return (string) $this->get('service')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLink(): string {
    return (string) $this->get('activity_stream_link')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getGuid(): string {
    return (string) $this->get('activity_stream_guid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished(): bool {
    return (bool) $this->get('status')->value;
  }

  /**
   * Default value callback for the 'uid' base field.
   *
   * @return array
   *   An array with the current user's ID as target_id.
   */
  public static function getDefaultEntityOwner(): array {
    return [['target_id' => \Drupal::currentUser()->id()]];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Serial ID.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    // UUID.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setReadOnly(TRUE);

    // Language code.
    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(new TranslatableMarkup('Language'));

    // Title.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Service identifier.
    $fields['service'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Service'))
      ->setDescription(new TranslatableMarkup('Identifier of the source service (e.g. feed, twitter).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Authored by (owner).
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Authored by'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Published status.
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Published'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Creation timestamp.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Changed timestamp.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'));

    // External link URL.
    $fields['activity_stream_link'] = BaseFieldDefinition::create('uri')
      ->setLabel(new TranslatableMarkup('Link'))
      ->setDescription(new TranslatableMarkup('External URL of the original item.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'uri_link',
        'weight' => 15,
      ])
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Globally unique identifier.
    $fields['activity_stream_guid'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('GUID'))
      ->setDescription(new TranslatableMarkup('Globally unique identifier.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 20,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Raw service response.
    $fields['activity_stream_raw'] = BaseFieldDefinition::create('string_long')
      ->setLabel(new TranslatableMarkup('Raw data'))
      ->setDescription(new TranslatableMarkup('Raw service response.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    // Body content.
    $fields['activity_stream_body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Body'))
      ->setDescription(new TranslatableMarkup('Content of the activity item.'))
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'weight' => 30,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
