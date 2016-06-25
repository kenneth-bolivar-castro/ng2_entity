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
}