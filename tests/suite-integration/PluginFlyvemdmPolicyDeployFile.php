<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2016 by the FusionInventory Development Team.
 *
 * This file is part of Flyve MDM Plugin for GLPI.
 *
 * Flyve MDM Plugin for GLPI is a subproject of Flyve MDM. Flyve MDM is a mobile
 * device management software.
 *
 * Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 * along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 * ------------------------------------------------------------------------------
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/flyve-mdm-glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Glpi\Test\CommonTestCase;
use PluginFlyvemdmPolicy;
use PluginFlyvemdmFleet;
use PluginFlyvemdmFleet_Policy;
use PluginFlyvemdmFile;
use stdClass;


class PluginFlyvemdmPolicyDeployFile extends CommonTestCase {
   public function beforeTestMethod($method) {
      $this->resetState();
      parent::beforeTestMethod($method);
      $this->setupGLPIFramework();
      $this->login('glpi', 'glpi');
   }

   /**
    * @engine inline
    */
   public function testApplyPolicy() {
      global $DB;

      // Create an application (directly in DB) because we are not uploading any file
      // Create an file (directly in DB)
      $fileName = 'flyve-user-manual.pdf';
      $fileTable = PluginFlyvemdmFile::getTable();
      $entityId = $_SESSION['glpiactive_entity'];
      $query = "INSERT INTO $fileTable (
         `name`,
         `source`,
         `entities_id`,
         `version`
      )
      VALUES (
         '$fileName',
         '2/12345678_flyve-user-manual.pdf',
         '$entityId',
         '1'
      )";
      $DB->query($query);
      $mysqlError = $DB->error();
      $file = new \PluginFlyvemdmFile();
      $this->boolean($file->getFromDBByQuery("WHERE `name`='$fileName'"))->isTrue($mysqlError);

      $policyDataDeploy = new PluginFlyvemdmPolicy();
      $this->boolean($policyDataDeploy->getFromDBBySymbol('deployFile'))->isTrue();

      $fleet = $this->createFleet();

      $fleetFk = \PluginFlyvemdmFleet::getForeignKeyField();
      $policyFk = \PluginFlyvemdmPolicy::getForeignKeyField();

      // check failure if no value
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $fleet_policy->add([
         $fleetFk    => $fleet->getID(),
         $policyFk   => $policyDataDeploy->getID(),
         'itemtype'  => get_class($file),
         'items_id'  => $file->getID()
      ]);
      $this->boolean($fleet_policy->isNewItem())->isTrue(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check failure if no destination
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
      $value = new stdClass();
      $value->remove_on_delete = '1';

      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $fleet_policy->add([
         $fleetFk    => $fleet->getID(),
         $policyFk   => $policyDataDeploy->getID(),
         'itemtype'  => get_class($file),
         'items_id'  => $file->getID(),
         'value'     => json_encode($value, JSON_UNESCAPED_SLASHES)
      ]);
      $this->boolean($fleet_policy->isNewItem())->isTrue(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check failure if no remove on delete flag
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
      $value = new stdClass();
      $value->destination = "%SDCARD%/path/to/";

      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $addSuccess = $fleet_policy->add([
         $fleetFk    => $fleet->getID(),
         $policyFk   => $policyDataDeploy->getID(),
         'itemtype'  => get_class($file),
         'items_id'  => $file->getID(),
         'value'     => json_encode($value, JSON_UNESCAPED_SLASHES)
      ]);
      $this->boolean($fleet_policy->isNewItem())->isTrue(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check failure if not itemId
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
      $value = new stdClass();
      $value->remove_on_delete = '1';
      $value->destination = "%SDCARD%/path/to/";

      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $fleet_policy->add([
         $fleetFk    => $fleet->getID(),
         $policyFk   => $policyDataDeploy->getID(),
         'itemtype'  => get_class($file),
         'items_id'  => $file->getID(),
         'value'     => json_encode($value, JSON_UNESCAPED_SLASHES)
      ]);
      $this->boolean($fleet_policy->isNewItem())->isTrue(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check failure if no itemtype
      $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];
      $value = new stdClass();
      $value->remove_on_delete = '1';
      $value->destination = "%SDCARD%/path/to/";

      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $fleet_policy->add([
         $fleetFk    => $fleet->getID(),
         $policyFk   => $policyDataDeploy->getID(),
         'items_id'  => $file->getID(),
         'value'     => json_encode($value, JSON_UNESCAPED_SLASHES)
      ]);
      $this->boolean($fleet_policy->isNewItem())->isTrue(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check add the policy to fleet with correct parameters suceeds
      $fleet_policy = $this->applyAddFilePolicy($policyDataDeploy, $file, $fleet);
      $this->boolean($fleet_policy->isNewItem())->isFalse(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check adding a deploy policy cannot be done twice
      $fleet_policy = $this->applyAddFilePolicy($policyDataDeploy, $file, $fleet);
      $this->boolean($fleet_policy->isNewItem())->isTrue(json_encode($_SESSION['MESSAGE_AFTER_REDIRECT'], JSON_PRETTY_PRINT));

      // Check remove deployment policy
      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $fleet_policy->getFromDBForItems($fleet, $policyDataDeploy);

      $this->boolean($fleet_policy->delete([
         'id' => $fleet_policy->getID(),
      ]))->isTrue();
   }

   private function createFleet() {
      $fleet = $this->newMockInstance(\PluginFlyvemdmFleet::class, '\MyMock');
      $fleet->getMockController()->post_addItem = function() {};
      $fleet->add([
         'entities_id'     => $_SESSION['glpiactive_entity'],
         'name'            => 'a fleet'
      ]);
      $this->boolean($fleet->isNewItem())->isFalse();

      return $fleet;
   }

   private function applyAddFilePolicy(\PluginFlyvemdmPolicy $policyData, \PluginFlyvemdmFile $file, \PluginFlyvemdmFleet $fleet) {
      $value = new stdClass();
      $value->remove_on_delete = '1';
      $value->destination = "%SDCARD%/path/to/";

      $fleet_policy = new PluginFlyvemdmFleet_Policy();
      $fleet_policy->add([
         'plugin_flyvemdm_fleets_id'   => $fleet->getID(),
         'plugin_flyvemdm_policies_id' => $policyData->getID(),
         'value'                       => $value,
         'itemtype'                    => get_class($file),
         'items_id'                    => $file->getID(),
      ]);

      return $fleet_policy;
   }
}
