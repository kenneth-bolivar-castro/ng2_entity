<?php
/**
 * Created by PhpStorm.
 * User: kenneth
 * Date: 24/06/16
 * Time: 02:39 PM
 */

namespace Drupal\ng2_entity;


interface Ng2EntityViewDisplayInterface {

  /**
   * Retrieve component based on machine name.
   * @param $machine_name string Given machine name.
   * @return array
   *   Component defintion.
   */
  public function getComponentByMachineName($machine_name);

  /**
   * Create EntityViewModes based on given entity types.
   * @param array $types Given entity types to create entity view.
   * @param bool $show_message Check if message is required.
   */
  public function createEntityViewModes(array $types, $show_message = FALSE);

  /**
   * Remove EntityViewModes based on given entity types.
   * @param array $types Given entity types to remove entity view.
   * @param bool $show_message Check if message is required.
   */
  public function removeEntityViewModes(array $types, $show_message = FALSE);
}