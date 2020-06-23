<?php
use CRM_Civicrmpostcodelookup_ExtensionUtil as E;

class CRM_Civicrmpostcodelookup_BAO_PafPostcodeLookup extends CRM_Civicrmpostcodelookup_DAO_PafPostcodeLookup {

  /**
   * Create a new PafPostcodeLookup based on array-data
   *
   * @param array $params key-value pairs
   *
   * @return CRM_Civicrmpostcodelookup_DAO_PafPostcodeLookup|NULL
   */
  public static function create($params) {
    $className = 'CRM_Civicrmpostcodelookup_DAO_PafPostcodeLookup';
    $entityName = 'PafPostcodeLookup';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

}
