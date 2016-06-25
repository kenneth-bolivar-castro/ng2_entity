
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Developing for PDB
 * Maintainers


INTRODUCTION
------------

Welcome to Angular 2 Entity. This module provides the ability
to expose Drupal entities as Angular 2 components through
Entity Display Modes and Progressive Decouple Blocks (PDB) contributed module.

Since PDB allows to declare Angular 2 components by write it down info.yml files,
this module take advances of that by new keys:

* entity_display
* attributes
* properties

 * For a full description of this module, visit the project page:
   https://www.drupal.org/sandbox/keboca/2755391


REQUIREMENTS
------------

There are no special requirements at this time.


INSTALLATION
------------

 * Enable the module as any other Drupal module.
 * Then enable which entity type should have new angular 2 component view mode:
     - /admin/config/ng2_entity/ng2entityviewdisplayconfig


CONFIGURATION
-------------

 * Navigate to Manage Display of an entity you want to enable new view mode.

 * Enable custom display mode by "Custom Display Settings" and save new settings.

 * Go to "Angular 2 Component" view mode and define component setting to proper Angular 2 component.

 * Then you can expose your entity as Angular 2 Component.


MAINTAINERS
-----------

Author:
 * Kenneth Bolívar (keboca) - https://www.drupal.org/u/keboca
    <me@keboca.com>

This project has been sponsored by:
 * Spark451 Inc.
   Spark451 is Marketing to the Next Degree. Visit
   https://www.spark451.com/ for more information.
