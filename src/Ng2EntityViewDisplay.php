<?php

namespace Drupal\ng2_entity;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\pdb\ComponentDiscovery;

/**
 * Class EntityViewDisplay.
 *
 * @package Drupal\ng2_entity
 */
class Ng2EntityViewDisplay implements Ng2EntityViewDisplayInterface {

  use StringTranslationTrait;

  /**
   * View mode name.
   *
   * @const string
   */
  const VIEW_MODE = 'angular2_component';

  /**
   * UuidInterface Service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * Token Service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * ImmutableConfig.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $pdbConfig;

  /**
   * ImmutableConfig.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $ng2Config;

  /**
   * Components.
   *
   * @var array
   */
  protected $components = [];

  /**
   * Ng2EntityViewDisplay constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   translation Service.
   * @param \Drupal\Core\Utility\Token $token
   *   Token Service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   UUID Service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   config_factory Service.
   * @param \Drupal\pdb\ComponentDiscovery $pdb_component_discovery
   *   ComponentDiscovery Service.
   */
  public function __construct(TranslationInterface $translation, Token $token, UuidInterface $uuid, ConfigFactoryInterface $config_factory, ComponentDiscovery $pdb_component_discovery) {
    // Setup translation service.
    $this->stringTranslation = $translation;
    // Setup UUID service.
    $this->uuid = $uuid;
    // Setup Token service.
    $this->token = $token;
    // Get "pdb_ng2.settings" settings.
    $this->pdbConfig = $config_factory->get('pdb_ng2.settings');
    // Get "ng2_entity.ng2entityviewdisplayconfig" settings.
    $this->ng2Config = $config_factory->get('ng2_entity.ng2entityviewdisplayconfig');
    // Retrieve all component info by component discovery service.
    $this->initComponents($pdb_component_discovery->getComponents());
  }

  /**
   * Retrieve all componet info.
   *
   * @param array $components
   *   PDB Components.
   */
  protected function initComponents(array $components) {
    // Detine component attribute by reducing component array.
    $this->components = array_reduce($components, function ($components, $component) {
      // Store component info array.
      $components[$component->info['machine_name']] = $component->info;
      return $components;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentByMachineName($machine_name) {
    // Check key component exists.
    if (array_key_exists($machine_name, $this->components)) {
      return $this->components[$machine_name];
    }
    return NULL;
  }

  /**
   * Implementation of entity_view hook.
   *
   * @param array $build
   *   Build.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   Display.
   * @param string $view_mode
   *   View_mode.
   */
  public function hookEntityView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    // Check given view mode parameter.
    if (self::VIEW_MODE == $view_mode) {
      // Retrieve components settings from Third party settings.
      $machine_name = $display->getThirdPartySetting('ng2_entity', 'components_settings');
      // Define angular2_component theme and its variables.
      $build['#theme'] = 'angular2_component';
      $build['#entity'] = $entity;
      $build['#component'] = $this->getComponentByMachineName($machine_name);
    }
  }

  /**
   * Implementation of form_FORM_ID_alter hook.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function hookFormEntityViewDisplayEditAlter(array &$form, FormStateInterface $form_state) {
    // Retrieve build info form current form state.
    $build_info = $form_state->getBuildInfo();
    // Check instance type of callback object.
    if (!array_key_exists('callback_object', $build_info)) {
      return;
    }
    // Entity should be instance of EntityViewDisplay.
    $entity = $build_info['callback_object']->getEntity();
    if (!$entity instanceof EntityViewDisplayInterface) {
      return;
    }
    // Check current view mode name.
    if (self::VIEW_MODE != $entity->getMode()) {
      return;
    }
    // Define component settings details container.
    $elements = [
      '#type' => 'details',
      '#title' => 'Component Settings',
      '#tree' => TRUE,
      '#open' => TRUE,
    ];
    // Retrieve options from PDB discovery service.
    $options = array_reduce(\Drupal::service('pdb.component_discovery')
      ->getComponents(), function ($options, $component) {
        // Presentation should be "ng2" and it should have "entity_display" key.
        // "entity_display" should be "view_mode" type.
        if ('ng2' == $component->info['presentation'] &&
          array_key_exists('entity_display', $component->info) &&
          'view_mode' == $component->info['entity_display']
        ) {
          // Add new option to carry variable.
          $options[$component->info['machine_name']] = $component->info['name'];
        }
        // Return variable to carry across array_reduce().
        return $options;
      });
    // Define radios input.
    $elements['settings'] = [
      '#type' => 'radios',
      '#title' => 'Angular2 Component',
      '#default_value' => $entity->getThirdPartySetting('ng2_entity', 'components_settings', FALSE),
      '#options' => $options,
    ];
    // Append elements to given form.
    $form['components'] = $elements;
    // Add new callback function as first function to
    // execute after submit this form.
    array_unshift($form['actions']['submit']['#submit'], '\Drupal\ng2_entity\Ng2EntityViewDisplay::callbackFormEntityViewDisplayEditSubmitAlter');
  }

  /**
   * Callback to "hookFormEntityViewDisplayEditAlter" implementation.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public static function callbackFormEntityViewDisplayEditSubmitAlter(array &$form, FormStateInterface $form_state) {
    // Retrieve current entity.
    $entity = $form_state->getFormObject()->getEntity();

    // Check if settings component already exist.
    if ($settings = $form_state->getValue('components')['settings']) {
      // Then define new Third party settings value.
      $entity->setThirdPartySetting('ng2_entity', 'components_settings', $settings);
    }
    else {
      // Otherwise remove Third party settings.
      $entity->unsetThirdPartySetting('ng2_entity', 'components_settings');
    }
  }

  /**
   * Return full URL based on given URI.
   *
   * @param string $uri
   *   Given URI to check.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   Full URL based on given value.
   *
   * @internal
   */
  protected function fromUri($uri) {
    return Url::fromUri($uri)->toString();
  }

  /**
   * Parse and retrieve field value from given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $entity
   *   Entity.
   * @param string $data
   *   Metadata.
   *
   * @return null|string
   *   Field value.
   *
   * @internal
   */
  protected function getFieldValue(ContentEntityBase $entity, $data) {
    // Explode given fieldName to metadata.
    $metadata = explode(':', $data);
    $field = array_shift($metadata);
    // After retrieve field check, then check if it exists and has any value.
    if ($entity->hasField($field) && !$entity->{$field}->isEmpty()) {
      // Check for metadata and entity object inside current field.
      if (!empty($metadata) && !empty($entity->{$field}->entity)) {
        // Build token from metadata to call "replace()" from token service.
        return $this->token->replace('[' . implode(':', $metadata) . ']', [$entity->{$field}->entity->getEntityTypeId() => $entity->{$field}->entity]);
      }
      // Check for metadata.
      elseif (!empty($metadata)) {
        // Init value.
        $value = NULL;
        // Retrieve first value as key.
        $key = array_shift($metadata);
        // Retrieve first item and its value.
        $values = $entity->{$field}->first()->getValue();
        // Check if key exists into values retrieved.
        if (array_key_exists($key, $values)) {
          // Define value from values and key.
          $value = $values[$key];
        }
        // Get callback definition from metadata.
        $callback = reset($metadata);
        if (!empty($callback)) {
          // Execute as internal function of current instance.
          // TODO: Allow external functions also check before execute it.
          $value = call_user_func([$this, $callback], $value);
        }
        // TODO: Expose hook_alter.
        // Returns parsed value.
        return $value;
      }
      // If metadata doesn't exist return raw value.
      return $entity->{$field}->value;
    }
    // If field neither exists or doesn't have any value.
    return NULL;
  }

  /**
   * Implementation of preprocess_HOOK hook for angular2-component.html.twig.
   *
   * @param array $variables
   *   Variables.
   */
  public function hookPreprocessAngular2Component(array &$variables) {
    // Check entity and component variables.
    if (empty($variables['entity']) || empty($variables['component'])) {
      return;
    }
    // Retrieve entity and component.
    $entity = $variables['entity'];
    $component = $variables['component'];
    // Init markup.
    $markup = '';
    // Init attributes and properties metadata.
    $metadata = [
      'attributes' => [],
      'properties' => [],
    ];
    // Map metadata values.
    array_map(function ($key) use (&$metadata, $component, $entity) {
      // Check key already exist into given component.
      if (!empty($component[$key])) {
        // Define key values by reducing component definition.
        $metadata[$key] = array_reduce($component[$key], function ($carry, $pair) use ($entity) {
          // Map values to carry based on metadata and given entity.
          $carry += array_map(function ($data) use ($entity) {
            return $this->getFieldValue($entity, $data);
          }, $pair);
          // Return carry values.
          return $carry;
        }, []);
        // If key is attributes.
        if ('attributes' == $key) {
          // Get attributes parsed.
          $attributes = $metadata['attributes'];
          // Prepare attributes values to be used.
          $metadata[$key] = array_reduce(array_keys($attributes), function ($carry, $key) use ($attributes) {
            // Setup proper format to be included into angular2 component.
            $carry[] = sprintf('%s="%s"', $key, $attributes[$key]);
            return $carry;
          }, []);
        }
      }
    }, array_keys($metadata));
    // Create new UUID value.
    $uuid = $this->uuid->generate();
    // Check attributes metadata to implode array into string.
    $attributes = !empty($metadata['attributes']) ? ' ' . implode(' ', $metadata['attributes']) : '';
    // Build angular2 tag.
    $markup .= "<{$component['machine_name']} id='instance-id-{$uuid}'{$attributes}></{$component['machine_name']}>";
    // Expose as ng2_tag to be available within template.
    $variables['ng2_tag'] = $markup;
    // Attach PDB library.
    $variables['#attached']['library'][] = 'pdb_ng2/pdb.ng2.config';
    // Define "ng2" component within "drupalSettings".
    $variables['#attached']['drupalSettings']['pdb']['ng2'] = [
      'module_path' => drupal_get_path('module', 'pdb_ng2'),
      'development_mode' => $this->pdbConfig->get('development_mode'),
      'global_injectables' => [],
      'components' => [
        "instance-id-{$uuid}" => [
          'uri' => $component['path'],
          'element' => $component['machine_name'],
          'properties' => $metadata['properties'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createEntityViewModes(array $types, $show_message = FALSE) {
    // Define view mode label.
    $label = $this->t('Angular 2 Component');
    // Walk though all given entity types.
    array_map(function ($entity_type) use ($label, $show_message) {
      // Define entity view mode id.
      $id = $entity_type . '.' . self::VIEW_MODE;
      // If it already exists, then avoid to create it.
      if (EntityViewMode::load($id)) {
        return;
      }
      // Create new angular2_component entity view mode.
      EntityViewMode::create([
        'id' => $id,
        'label' => $label,
        'targetEntityType' => $entity_type,
      ])->save();
      // Check if message is required.
      if ($show_message) {
        // Display successful message.
        drupal_set_message($this->t('Saved %label view mode within @entity-type.', [
          '%label' => $label,
          '@entity-type' => $entity_type,
        ]));
      }
    }, $types);
  }

  /**
   * {@inheritdoc}
   */
  public function removeEntityViewModes(array $types, $show_message = FALSE) {
    // Get through all given entity types.
    array_map(function ($entity_type) use ($show_message) {
      // Remove angular2_component view mode from given entity type.
      if ($entity = EntityViewMode::load($entity_type . '.' . self::VIEW_MODE)) {
        $entity->delete();
        // Check if message is required.
        if ($show_message) {
          // Display warning message.
          drupal_set_message($this->t('Removed %label view mode within @entity-type.', [
            '%label' => $entity->label(),
            '@entity-type' => $entity_type,
          ]), 'warning');
        }
      }
    }, $types);
  }

  /**
   * Implementation of install hook.
   */
  public function hookInstall() {
    // Retrieve entity types selected.
    $types = array_filter($this->ng2Config->get('entity_types'));
    // Create entity view modes.
    $this->createEntityViewModes($types);
  }

  /**
   * Implementation of uninstall hook.
   */
  public function hookUninstall() {
    // Retrieve entity types selected.
    $types = array_filter($this->ng2Config->get('entity_types'));
    // Remove entity view modes.
    $this->removeEntityViewModes($types);
  }

}
