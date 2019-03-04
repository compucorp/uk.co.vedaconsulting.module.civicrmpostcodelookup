<?php

class CRM_Civicrmpostcodelookup_Page_ServerUpload extends CRM_Civicrmpostcodelookup_Page_Postcode {

  /**
   * Function to get address list based on a Post code
   */
  public static function search() {
    $postcode = self::getPostcode(TRUE);
    $number = CRM_Utils_Request::retrieve('number', 'String');
    $params = ['post_code' => ['LIKE' => "%{$postcode}%"]];

    if ($number) {
      $params['building_number'] = ['LIKE' => "%{$number}%"];
    }

    $result = civicrm_api3('PafPostcodeLookup', 'get', $params);

    if ($result['is_error'] == 1) {
      $addresslist[0]['value'] = '';
      $addresslist[0]['label'] = $result['error_message'];
    }

    if ($result['is_error'] == 0 && !empty($result['values'])) {
      $addresslist = self::getAddressList($result['values'], $postcode);
    }

    // Check CiviCRM version & return result as appropriate
    $civiVersion = CRM_Civicrmpostcodelookup_Utils::getCiviVersion();
    if ($civiVersion < 4.5) {
      foreach ($addresslist as $key => $val) {
        echo "{$val['label']}|{$val['id']}\n";
      }
    } else {
      echo json_encode($addresslist);
    }
    exit;
  }

  /**
   * Gets the address list.
   *
   * @param array $resultData
   * @param string $postcode
   *
   * @return array
   */
  private static function getAddressList($resultData, $postcode) {
    $addressList = array();
    $addressRow = array();
    foreach ($resultData as $key => $addressItem) {
      $addressLineArray = self::formatAddressLines($addressItem, TRUE);
      $addressLineArray = array_filter($addressLineArray);

      $addressRow["id"] = $addressItem['id'];
      $addressRow["value"] = $postcode;
      $addressRow["label"] = @implode(', ', $addressLineArray);
      array_push($addressList, $addressRow);
    }

    if (empty($addressList)) {
      $addressRow["id"] = '';
      $addressRow["value"] = '';
      $addressRow["label"] = 'Postcode Not Found';
      array_push($addressList, $addressRow);
    }

    return $addressList;
  }

  /**
   * Function to get address details based on the Civipostcode address id
   */
  public static function getaddress() {
    $moniker = CRM_Utils_Request::retrieve('id', 'String');
    if (empty($moniker)) {
      exit;
    }

    $address = self::getAddressByMoniker($moniker);
    $response = array(
      'address' => $address
    );

    echo json_encode($response);
    exit;
  }

  /**
   * Gets address by moniker.
   *
   * @param string $moniker
   *
   * @return array
   */
  private static function getAddressByMoniker($moniker) {
    $result = civicrm_api3('PafPostcodeLookup', 'get', [
      'id' => $moniker,
    ]);

    $addressData = $result['values'][$moniker];
    $address = self::formatAddressLines($addressData);


    return $address;
  }

  /**
   * Format address lines based on Royal Mail PAF address assembler
   *
   * @param array $addressData
   * @param bool $forList
   *
   * @return array
   */
  private static function formatAddressLines($addressData, $forList = FALSE) {
    if (empty($addressData)) {
      return;
    }

    // Format address lines based on Royal Mail PAF address assembler (https://github.com/AllenJB/PafUtils)
    require_once 'CRM/PafUtils/Address.php';
    $addressLineObj = new Address();
    $addressLineObj->setUdprn(CRM_Utils_Array::value('udprn', $addressData))
      ->setPostCode(CRM_Utils_Array::value('post_code', $addressData))
      ->setPostTown(CRM_Utils_Array::value('post_town', $addressData))
      ->setDependentLocality(CRM_Utils_Array::value('dependent_locality', $addressData))
      ->setDoubleDependentLocality(CRM_Utils_Array::value('double_dependent_locality', $addressData))
      ->setThoroughfare(CRM_Utils_Array::value('thoroughfare_descriptor', $addressData))
      ->setDependentThoroughfare(CRM_Utils_Array::value('dependent_thoroughfare_descriptor', $addressData))
      ->setBuildingNumber(CRM_Utils_Array::value('building_number', $addressData))
      ->setBuildingName(CRM_Utils_Array::value('building_name', $addressData))
      ->setSubBuildingName(CRM_Utils_Array::value('sub_building_name', $addressData))
      ->setPoBox(CRM_Utils_Array::value('po_box', $addressData))
      ->setDepartmentName(CRM_Utils_Array::value('department_name', $addressData))
      ->setOrganizationName(CRM_Utils_Array::value('organisation_name', $addressData))
      ->setPostcodeType(CRM_Utils_Array::value('postcode_type', $addressData))
      ->setSuOrganizationIndicator(CRM_Utils_Array::value('su_organisation_indicator', $addressData))
      ->setDeliveryPointSuffix(CRM_Utils_Array::value('delivery_point_suffix', $addressData));
    $addressLines = $addressLineObj->getAddressLines();

    if ($forList == FALSE) {
      $address = array('id' => CRM_Utils_Array::value('id', $addressData));
    }

    if (!empty($addressLines[0])) {
      $address["street_address"] = $addressLines[0];
    }
    if (!empty($addressLines[1])) {
      $address["supplemental_address_1"] = $addressLines[1];
    }
    if (!empty($addressLines[2])) {
      $address["supplemental_address_2"] = $addressLines[2];
    }
    $address["town"] = CRM_Utils_Array::value('post_town', $addressData);
    $address["postcode"] = CRM_Utils_Array::value('post_code', $addressData);

    return $address;
  }
}
