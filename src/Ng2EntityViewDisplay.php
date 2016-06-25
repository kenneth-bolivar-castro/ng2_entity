<?php

namespace Drupal\ng2_entity;

use Drupal\Core\Url;
use Drupal\pdb\ComponentDiscovery;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EntityViewDisplay.
 *
 * @package Drupal\ng2_entity
 */
class Ng2EntityViewDisplay implements Ng2EntityViewDisplayInterface {

  /**
   * @const string
   */
  const VIEW_MODE = 'angular2_component';

  /**
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * @var array
   */
  protected $components = [];

  /**
   * EntityViewDisplay constructor.
   * @param \Drupal\pdb\ComponentDiscovery $pdb_component_discovery
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Utility\Token $token
   */
  public function __construct(Token $token, UuidInterface $uuid, ConfigFactoryInterface $config_factory, ComponentDiscovery $pdb_component_discovery) {
    // Setup UUID service.
    $this->uuid = $uuid;
    // Setup Token service.
    $this->token = $token;
    // Get PDB NG settings.
    $this->config = $config_factory->get('pdb_ng2.settings');
    // Retrieve all component info by component discovery service.
    $this->initComponents($pdb_component_discovery->getComponents());
  }

  /**
   * Retrieve all componet info.
   * @param $components array PDB Components.
   */
  protected function initComponents($components) {
    // Detine component attribute by reducing component array.
    $this->components = array_reduce($components, function($components, $component) {
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
    if(array_key_exists($machine_name, $this->components)) {
      return $this->components[$machine_name];
    }
    return NULL;
  }

  /**
   * Implements hook_entity_view().
   * @param array $build
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   * @param $view_mode
   */
  public function hookEntityView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    // Check given view mode parameter.
    if (self::VIEW_MODE == $view_mode) {
      // Retrieve components settings from Third party settings.
      $machineName = $display->getThirdPartySetting('ng2_entity', 'components_settings');
      // Define angular2_component theme and its variables.
      $build['#theme'] = 'angular2_component';
      $build['#entity'] = $entity;
      $build['#component'] = $this->getComponentByMachineName($machineName);
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function hookFormEntityViewDisplayEditAlter(&$form, FormStateInterface $form_state) {
    // Retrieve build info form current form state.
    $buildInfo = $form_state->getBuildInfo();
    // Check instance type of callback object.
    if (!array_key_exists('callback_object', $buildInfo)) {
      return;
    }
    // Entity should be instance of EntityViewDisplay.
    $entity = $buildInfo['callback_object']->getEntity();
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
    // Add new callback function as first function to execute after submit this form.
    array_unshift($form['actions']['submit']['#submit'], sprintf('\%s::%s', self::class, 'callbackFormEntityViewDisplayEditSubmitAlter'));
  }

  /**
   * Callback function to handle submission of forms altered by "hookFormEntityViewDisplayEditAlter" implementation.
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
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
   * Return full URL based on given URI
   * @internal
   * @param $uri string Given URI to check.
   * @return \Drupal\Core\GeneratedUrl|string
   *   Full URL based on given value.
   */
  protected function fromUri($uri) {
    return Url::fromUri($uri)->toString();
  }

  /**
   * Parse and retrieve field value from given entity.
   * @internal
   * @param $entity
   * @param $fieldName
   * @return null|string
   */
  protected function getFieldValue($entity, $fieldName) {
    // Explode given fieldName to metadata.
    $metadata = explode(':', $fieldName);
    $field = array_shift($metadata);
    // After retrieve field check, then check if it exists and has any value.
    if ($entity->hasField($field) && !$entity->{$field}->isEmpty()) {
      // Check for metadata and entity object inside current field.
      if (!empty($metadata) && !empty($entity->{$field}->entity)) {
        // Build token from metadata values and call "replace()" from token service.
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
   * Implements hook_preprocess_HOOK() for angular2-component.html.twig.
   * @param $variables
   */
  public function hookPreprocessAngular2Component(&$variables) {
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
          // Map values to carry based on fieldName metadata and given entity.
          $carry += array_map(function ($fieldName) use ($entity) {
            return $this->getFieldValue($entity, $fieldName);
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
            // Setup proper format to be included into angular2 c.omponent.
            $carry[] = sprintf('%s="%s"', $key, $attributes[$key]);
            return $carry;
          }, []);
        }
      }
    }, array_keys($metadata));
    // Create new UUID value.
    $uuid = $this->uuid->generate();
    // Check attributes metadata to implode array into string.
    $attributes = !empty($metadata['attributes']) ?
      ' ' . implode(' ', $metadata['attributes']) : '';
    // Build angular2 tag.
    $markup .= "<{$component['machine_name']} id='instance-id-{$uuid}'{$attributes}></{$component['machine_name']}>";
    // Expose as ng2_tag to be available within template.
    $variables['ng2_tag'] = $markup;
    // Attach PDB library.
    $variables['#attached']['library'][] = 'pdb_ng2/pdb.ng2.config';
    // Define "ng2" component within "drupalSettings".
    $variables['#attached']['drupalSettings']['pdb']['ng2'] = [
      'module_path' => drupal_get_path('module', 'pdb_ng2'),
      'development_mode' => $this->config->get('development_mode'),
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
}
