<?php

namespace Drupal\ng2_entity;

/**
 * Interface Ng2EntityViewDisplayInterface.
 *
 * @package Drupal\ng2_entity
 */
interface Ng2EntityViewDisplayInterface {

  /**
   * Retrieve component based on machine name.
   *
   * @param string $machine_name
   *   Given machine name.
   *
   * @return array
   *   Component definition.
   */
  public function getComponentByMachineName($machine_name);

  /**
   * Create EntityViewModes based on given entity types.
   *
   * @param array $types
   *   Given entity types to create entity view.
   * @param bool $show_message
   *   Check if message is required.
   */
  public function createEntityViewModes(array $types, $show_message = FALSE);

  /**
   * Remove EntityViewModes based on given entity types.
   *
   * @param array $types
   *   Given entity types to remove entity view.
   * @param bool $show_message
   *   Check if message is required.
   */
  public function removeEntityViewModes(array $types, $show_message = FALSE);

}
