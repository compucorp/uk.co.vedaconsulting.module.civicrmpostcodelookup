<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Civicrmpostcodelookup_Form_Setting extends CRM_Core_Form {
  function buildQuickForm() {

    $settingsStr = CRM_Core_BAO_Setting::getItem('CiviCRM Postcode Lookup', 'api_details');

    $settingsArray = unserialize($settingsStr);

    // Postcode loookup Provider
    $this->add(
      'select', // field type
      'provider', // field name
      ts('Provider'), // field label
      $this->getProviderOptions(), // list of options
      true // is required
    );

    // Server URL
    $this->addElement(
      'text',
      'server',
      ts('Server URL'),
      array('size' => 50),
      true
    );

    // API Key
    $this->addElement(
      'text',
      'api_key',
      ts('API Key'),
      array('size' => 50),
      false
    );

     // Serial Number
    $this->addElement(
      'text',
      'serial_number',
      ts('Serial Number'),
      array('size' => 20),
      false
    );

    // Username
    $this->addElement(
      'text',
      'username',
      ts('Username'),
      array('size' => 20),
      false
    );

     // Password
    $this->addElement(
      'text',
      'password',
      ts('Password'),
      array('size' => 20),
      false
    );

    $this->addRadio(
      'import_method',
      ts('Import Method'),
      array('1' => ts('Upload File'), '2' => ts('Import from URL'))
    );

    // PAF file upload
    $this->addElement(
      'file',
      'paf_file',
      ts('Upload PAF File'),
      array('size' => 30, 'maxlength' => 255, 'accept' => '.csv,text/plain'),
      false
    );

    $this->addElement(
      'hidden',
      'paf_file_name',
      "Field to store file name for uploaded PAF file"
    );

    // PAF file URL link
    $this->addElement(
      'text',
      'paf_file_url',
      ts('Import PAF from URL'),
      array('size' => 50),
      false
    );

    //MV#4367 Location Types
    $locationTypes = array_flip(CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id'));

    $this->addCheckBox('location_type_id',
     ts('Location Types'),
      $locationTypes,
      NULL, NULL, NULL, NULL,
      array('&nbsp;&nbsp;')
    );

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    $this->setDefaults($settingsArray);

    $this->addFormRule( array( 'CRM_Civicrmpostcodelookup_Form_Setting', 'formRule' ) );

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    // assign PAF file name value.
    $this->assign('pafFileName', !empty($settingsArray['paf_file_name']) ? $settingsArray['paf_file_name'] : '');
    $this->assign('maxFileSize', $this->_maxFileSize);
    parent::buildQuickForm();
  }

  static function formRule( $values ){

    $errors = array();

    // Server is mandatory fo AFD and CiviPostcode. Server URL is in QAS lib for Experian
    if ($values['provider'] == 'afd' || $values['provider'] == 'civipostcode' || $values['provider'] == 'postcodeanywhere') {
      if (empty($values['server'])) {
        $errors['server'] = ts( "Server URL is mandatory." );
      }
    }

    // Check all mandatory values are entered for AFD
    if ($values['provider'] == 'afd') {
      if (empty($values['serial_number'])) {
        $errors['serial_number'] = ts( "Serial Number is mandatory." );
      }
      if (empty($values['password'])) {
        $errors['password'] = ts( "Password is mandatory." );
      }
    }

    // Check all mandatory values are entered for Civipostcode
    if ($values['provider'] == 'civipostcode') {
      if (empty($values['api_key'])) {
        $errors['api_key'] = ts( "API Key is mandatory." );
      }
    }

    // Check all mandatory values are entered for Experian
    if ($values['provider'] == 'experian') {
      if (empty($values['username'])) {
        $errors['username'] = ts( "Username is mandatory." );
      }
      if (empty($values['password'])) {
        $errors['password'] = ts( "Password is mandatory." );
      }
    }

    // Check all mandatory values are entered for PostcodeAnywhere
    if ($values['provider'] == 'postcodeanywhere') {
      if (empty($values['username'])) {
        $errors['username'] = ts( "Username is mandatory." );
      }
      if (empty($values['api_key'])) {
        $errors['api_key'] = ts( "API Key is mandatory." );
      }
    }

    // Check all mandatory values are entered for getAddressio
    if ($values['provider'] == 'getaddressio') {
      if (empty($values['server'])) {
        $errors['server'] = ts( "Server URL is mandatory." );
      }
      if (empty($values['api_key'])) {
        $errors['api_key'] = ts( "API Key is mandatory." );
      }
    }

    return $errors;
  }

  function postProcess() {
    $values = $this->exportValues();

    // Validates uploaded PAF file
    $isPafFileUpload = $values['provider'] =='serverupload' && $values['import_method'] == 1;
    if ($isPafFileUpload && !empty($this->_submitFiles['paf_file']['tmp_name'])) {
      try{
        $this->validateUploadedPaf($this->_submitFiles['paf_file']);
      } catch(Exception $e) {
        CRM_Core_Session::setStatus($e->getMessage(), 'Error', 'error');

        return;
      }
    }

    // Validates PAF file from remote URL
    $isPafFileUrl = $values['provider'] =='serverupload' && $values['import_method'] == 2;
    if ($isPafFileUrl && !empty($values['paf_file_url'])) {
      try {
        $pafFileUrl = $this->downloadPafFile($values['paf_file_url']);
        $this->validateDownloadedPaf($pafFileUrl);
      } catch(Exception $e) {
        CRM_Core_Session::setStatus($e->getMessage(), 'Error', 'error');

        return;
      }
    }

    $settingsArray = array();
    $settingsArray['provider'] = $values['provider'];

    // AFD
    if ($values['provider'] =='afd')  {
      $settingsArray['server'] = $values['server'];
      $settingsArray['serial_number'] = $values['serial_number'];
      $settingsArray['password'] = $values['password'];
    }

    // Civipostcode
    if ($values['provider'] =='civipostcode')  {
      $settingsArray['server'] = $values['server'];
      $settingsArray['api_key'] = $values['api_key'];
    }

    // Experian
    if ($values['provider'] =='experian')  {
      $settingsArray['username'] = $values['username'];
      $settingsArray['password'] = $values['password'];
    }

    // PostcodeAnywhere
    if ($values['provider'] =='postcodeanywhere')  {
      $settingsArray['server'] = $values['server'];
      $settingsArray['api_key'] = $values['api_key'];
      $settingsArray['username'] = $values['username'];
    }

    // GetAddress.io
    if ($values['provider'] =='getaddressio')  {
      $settingsArray['server'] = $values['server'];
      $settingsArray['api_key'] = $values['api_key'];
    }

    //MV#4367 amend Location Types into settings
    if (!empty($values['location_type_id']))  {
      $settingsArray['location_type_id'] = $values['location_type_id'];
    }

    if ($values['provider'] == 'serverupload') {
      $settingsArray['import_method'] = $values['import_method'];
      $settingsArray['paf_file_url'] = $values['paf_file_url'];
      if ($isPafFileUpload && !empty ($this->_submitFiles['paf_file']['name'])) {
        $settingsArray['paf_file_name'] = $this->_submitFiles['paf_file']['name'];
      }
    }

    $settingsStr = serialize($settingsArray);

    // Process uploaded PAF file
    if ($isPafFileUpload && !empty($this->_submitFiles['paf_file']['tmp_name'])) {
      $this->processPAfFile($this->_submitFiles['paf_file']['tmp_name']);
      unlink($this->_submitFiles['paf_file']['tmp_name']);
    }

    // Process PAF file downloaded from remote Url
    if ($isPafFileUrl && !empty($values['paf_file_url'])) {
      $this->processPAfFile($pafFileUrl);
      unlink($pafFileUrl);
    }

    CRM_Core_BAO_Setting::setItem($settingsStr,
      'CiviCRM Postcode Lookup',
      'api_details'
    );

    $message = "Settings saved.";
    CRM_Core_Session::setStatus($message, 'Postcode Lookup', 'success');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/postcodelookup/settings'));
  }

  function getProviderOptions() {
    $options = array(
      '' => ts('- select -'),
    ) + $GLOBALS["providers"];

    return $options;
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Checks if the file uploaded is a valid file in proper format and
   * if the size does not exceed the max upload limit.
   *
   * @param array $fileData
   */
  private function validateUploadedPaf($fileData) {
    if (!in_array($fileData['type'], ['text/csv', 'text/plain'])) {
      throw new Exception('Only PAF files in CSV format are allowed!');
    }
    $maxFileSize = $this->_maxFileSize;

    if ($fileData['size'] > $maxFileSize) {
      throw new Exception('PAF File size is greater than maximum upload limit!');
    }
  }

  /**
   * Checks if the file downloaded from the remote URL is in
   * the expected file format.
   *
   * @param string $fileLocation
   */
  private function validateDownloadedPaf($fileLocation) {
    $mimeType = mime_content_type($fileLocation);
    if (!in_array($mimeType, ['text/csv', 'text/plain'])) {
      throw new Exception('Only PAF files in CSV format are allowed!');
    }
  }

  /**
   * Imports the PAF file into the paf postcode look up table.
   *
   * @param string $filePath
   */
  private function processPAfFile($filePath) {
    CRM_Core_DAO::executeQuery("TRUNCATE TABLE paf_post_code_lookup");
    CRM_Core_DAO::executeQuery("
      LOAD DATA LOCAL INFILE '{$filePath}' 
      INTO TABLE paf_post_code_lookup
      FIELDS TERMINATED BY ',' 
      LINES TERMINATED BY '\r\n'
      (post_code, post_town, dependent_locality, 
       double_dependent_locality, thoroughfare_descriptor,
       dependent_thoroughfare_descriptor, building_number,building_name,
       sub_building_name, po_box, department_name, organisation_name,
       udprn, postcode_type, su_organisation_indicator, delivery_point_suffix)"
    );
  }

  /**
   * Downloads the PAF csv file to the server and returns the
   * file location on the server.
   *
   * @param string $remotefileUrl
   *
   * @return string
   */
  private function downloadPafFile($remotefileUrl) {
    set_time_limit(0);
    $path = tempnam(sys_get_temp_dir(), 'prefix');
    $fp = fopen ($path, 'w+');
    $ch = curl_init(str_replace(" ","%20", $remotefileUrl));
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    curl_close($ch);

    return $path;
  }
}
