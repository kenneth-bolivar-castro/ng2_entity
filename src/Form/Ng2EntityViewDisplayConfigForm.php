<?php

namespace Drupal\ng2_entity\Form;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class EntityViewDisplayConfigForm.
 *
 * @package Drupal\ng2_entity\Form
 */
class Ng2EntityViewDisplayConfigForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * EntityViewDisplayConfigForm constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManager $entity_type_manager
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ng2_entity.ng2entityviewdisplayconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_view_display_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve all entity types that support view modes.
    $contentEntityTypes = array_filter($this->entityTypeManager->getDefinitions(), function ($entity_type) {
      return ($entity_type->get('field_ui_base_route') && $entity_type->hasViewBuilderClass());
    });
    // Retrieve current configuration.
    $config = $this->config('ng2_entity.ng2entityviewdisplayconfig');
    // Define new checkboxes input.
    $form['entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity types'),
      '#description' => $this->t('Select entity types to enable Angular 2 component view mode.'),
      '#options' => array_map(function ($entityType) {
        return $entityType->getLabel();
      }, $contentEntityTypes),
      '#default_value' => !empty($config->get('entity_types')) ?
        $config->get('entity_types') : [],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Retrieve current types selected.
    $types = $this->config('ng2_entity.ng2entityviewdisplayconfig')
      ->get('entity_types');
    // Compare values just selected and settings defined.
    if ($types && ($diff = array_diff($types, $form_state->getValue('entity_types')))) {
      // Get thought all entity types disabled.
      foreach ($diff as $entityType) {
        // Since entity type was disabled, then remove angular2_component view mode.
        if ($entity = EntityViewMode::load($entityType . '.angular2_component')) {
          $entity->delete();
          drupal_set_message($this->t('Removed %label view mode within @entity-type.', [
            '%label' => $entity->label(),
            '@entity-type' => $entityType
          ]), 'warning');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Define view mode label.
    $label = $this->t('Angular 2 Component');
    // Walk though entity types selected.
    foreach (array_filter($form_state->getValue('entity_types')) as $entityType) {
      // Define entity view mode id.
      $id = $entityType . '.angular2_component';
      // If it already exists, then avoid to create it.
      if (EntityViewMode::load($id)) {
        continue;
      }
      // Create new angular2_component entity view mode.
      EntityViewMode::create([
        'id' => $id,
        'label' => $label,
        'targetEntityType' => $entityType,
      ])->save();
      drupal_set_message($this->t('Saved %label view mode within @entity-type.', [
        '%label' => $label,
        '@entity-type' => $entityType
      ]));
    }
    // Save entity types into configuration.
    $this->config('ng2_entity.ng2entityviewdisplayconfig')
      ->set('entity_types', $form_state->getValue('entity_types'))
      ->save();
  }

}
