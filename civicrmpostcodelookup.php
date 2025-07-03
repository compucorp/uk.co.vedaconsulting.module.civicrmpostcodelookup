<?php

require_once 'civicrmpostcodelookup.civix.php';
use CRM_Civicrmpostcodelookup_ExtensionUtil as E;

// Postcode lookup providers
// FIXME: Move this list to option values
$GLOBALS["providers"] = [
  'afd' => 'AFD',
  'civipostcode' => 'CiviPostcode',
  'experian' => 'Experian',
  'postcodeanywhere' => 'PostcodeAnywhere',
  'getaddressio'  => 'GetAddress'
];

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function civicrmpostcodelookup_civicrm_config(&$config) {
  _civicrmpostcodelookup_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function civicrmpostcodelookup_civicrm_install() {
  \Civi::settings()->set('api_details', '');
  _civicrmpostcodelookup_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function civicrmpostcodelookup_civicrm_enable() {
  _civicrmpostcodelookup_civix_civicrm_enable();
}

/**
 * Add navigation for Postcode Lookup under "Administer" menu
 */
function civicrmpostcodelookup_civicrm_navigationMenu(&$menu) {
  $item[] =  [
    'label'      => E::ts('Postcode Lookup'),
    'name'       => 'Postcode Lookup',
    'url'        => 'civicrm/admin/postcodelookup/settings?reset=1',
    'permission' => 'administer CiviCRM',
    'operator'   => NULL,
    'separator'  => TRUE,
    'active'     => 1
  ];
  _civicrmpostcodelookup_civix_insert_navigation_menu($menu, 'Administer', $item[0]);
  _civicrmpostcodelookup_civix_navigationMenu($menu);
}

function civicrmpostcodelookup_civicrm_buildForm($formName, &$form) {
  $postCodeLookupPages = [
    'CRM_Contact_Form_Contact'
    , 'CRM_Contact_Form_Inline_Address'
    , 'CRM_Profile_Form_Edit'
    , 'CRM_Event_Form_Registration_Register'
    , 'CRM_Contribute_Form_Contribution_Main'
    , 'CRM_Event_Form_ManageEvent_Location'
    , 'CRM_Financial_Form_Payment'
    , 'CRM_Contact_Form_Domain'
  ];
  if (in_array($formName, $postCodeLookupPages)) {
    // Assign the postcode lookup provider to form, so that we can call the related function in AJAX
    $settingsStr = \Civi::settings()->get('api_details');
    $settingsArray = unserialize($settingsStr);
    $form->assign('civiPostCodeLookupProvider', $settingsArray['provider']);
    $settingsArray['location_type_id'] = $settingsArray['location_type_id'] ?? [];
    $settingsArray['location_type_id'][] = 'Primary';
    $form->assign('civiPostCodeLookupLocationTypeJson', json_encode($settingsArray['location_type_id']));
  }
}

/**
 * Implementation of hook_civicrm_permission
 *
 * @param array $permissions
 * @return void
 */
function civicrmpostcodelookup_civicrm_permission(&$permissions) {
  $permissions['access postcode lookup'] = [
    'label' => E::ts('CiviCRM: Access CiviCRM Postcode lookups'),
    'description' => E::ts('Allows the user to lookup postcodes.')
  ];
}
