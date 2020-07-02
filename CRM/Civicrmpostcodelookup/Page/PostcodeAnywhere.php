<?php

require_once 'CRM/Core/Page.php';

class CRM_Civicrmpostcodelookup_Page_PostcodeAnywhere extends CRM_Civicrmpostcodelookup_Page_Postcode {

  /*
   * Function to get the Server URL and login credentials
   */
  public static function getPostcodeAnywhereCredentials($action = 1) {
    #################
    #Server settings
    #################
    $settingsStr = \Civi::settings()->get('api_details');
    $settingsArray = unserialize($settingsStr);

    $servertarget = $settingsArray['server'];

    // Action : '1' - Address List, '2' - Address Lookup
    switch ($action) {
      case 1:
        $servertarget = $servertarget . "/PostcodeAnywhere/Interactive/Find/v1.10/xmla.ws";
        break;

      case 2:
        $servertarget = $servertarget . "/PostcodeAnywhere/Interactive/RetrieveById/v1.30/xmla.ws";
        break;

      default:
        $servertarget = $servertarget . "/PostcodeAnywhere/Interactive/Find/v1.10/xmla.ws";
    }

    $apiKey = urlencode($settingsArray['api_key']);
    $username = urlencode($settingsArray['username']);

    $querystring = "Key=$apiKey&UserName=$username";
    return $servertarget ."?" . $querystring;
  }

  /*
   * Function to get address list based on a Post code
   */
  public static function search() {
    // PostcodeAnywhere API works with postcodes when they have a space and when they don't.
    $postcode = self::getPostcode();

    $querystring = self::getPostcodeAnywhereCredentials(1);
    $querystring = $querystring . "&SearchTerm=" . urlencode($postcode);

    //Make the request to Postcode Anywhere and parse the XML returned
    $simpleXMLData = simplexml_load_file($querystring);

    if (!empty($simpleXMLData)) {
      $addresslist = self::getAddressList($simpleXMLData, $postcode);
    }

    echo json_encode($addresslist);
    exit;
  }

  private static function getAddressList($simpleXMLData, $postcode) {
    $addressList = [];
    $addressRow = [];
    $AddressListItem = (array) $simpleXMLData->Rows;
    $AddressListItems = $AddressListItem['Row'];

    foreach ($AddressListItems as $key => $addressItem) {
      $addressItemArray = (array) $addressItem;
      $addressRow["id"] = (string) $addressItemArray['@attributes']['Id'];
      $addressRow["value"] = $postcode;
      $addressRow["label"] = $addressItemArray['@attributes']['StreetAddress'].', '.$addressItemArray['@attributes']['Place'];
      array_push($addressList, $addressRow);

      /*$addressItemArray = (array) $addressItem;
      $addressList['items'][] = array('id' => (string) $addressItemArray['@attributes']['Id'], 'label' => (string) $addressItemArray['@attributes']['StreetAddress'].', '.$addressItemArray['@attributes']['Place']);*/
    }

    if (empty($addressList)) {
      $addressRow["id"] = '';
      $addressRow["value"] = '';
      $addressRow["label"] = 'Error: Postcode Not Found';
      array_push($addressList, $addressRow);
    }

    return $addressList;
  }

  /*
   * Function to get address details based on the PostcodeAnywhere addressid/postkey
   */
  public static function getaddress() {
    $moniker = CRM_Utils_Request::retrieve('id', 'String');
    if (empty($moniker)) {
      exit;
    }

    $address = self::getAddressByMoniker($moniker);
    $response = [
      'address' => $address
    ];

    echo json_encode($response);
    exit;
  }

  private static function getAddressByMoniker($moniker) {

    // Get state/county
    $states = CRM_Core_PseudoConstant::stateProvince();

    $querystring = self::getPostcodeAnywhereCredentials(2);
    $querystring = $querystring . "&Id=" . urlencode($moniker);

    //Make the request to Postcode Anywhere and parse the XML returned
    $simpleXMLData = simplexml_load_file($querystring);

    $addressItemRow = (array) $simpleXMLData->Rows;
    $addressItem = (array) $addressItemRow['Row'];

    $providerAddressLineKeys = ['Company', 'Line1', 'Line2', 'Line3', 'Line4', 'Line5'];
    $civiAdressLinesKeys = ['street_address', 'supplemental_address_1', 'supplemental_address_2'];

    $address = ['id' => $moniker];
    foreach ($civiAdressLinesKeys as $civiAdressLinesKey) {
      $address[$civiAdressLinesKey] = '';
      foreach ($providerAddressLineKeys as $index => $providerKey) {
        unset($providerAddressLineKeys[$index]);
        if (!empty($addressItem['@attributes'][$providerKey])) {
          $address[$civiAdressLinesKey] = $addressItem['@attributes'][$providerKey];
          break;
        }
      }
    }

    $address['supplemental_address_3'] = '';
    foreach ($providerAddressLineKeys as $providerKey) {
      if (!empty($addressItem['@attributes'][$providerKey])) {
        $address['supplemental_address_3'] .= $addressItem['@attributes'][$providerKey] . ', ' ;
      }
    }
    $address['supplemental_address_3']= trim($address['supplemental_address_3'], ', ');

    $address['town'] = $addressItem['@attributes']['PostTown'];
    $address['postcode'] = $addressItem['@attributes']['Postcode'];

    $address['state_province_id'] = '';
    $address['state_province_abbreviation'] = '';
    if ($stateId = array_search($addressItem['@attributes']['County'], $states)) {
      $address['state_province_id'] = $stateId;

      $address['state_province_abbreviation'] = civicrm_api3('StateProvince', 'getvalue', [
        'return' => 'abbreviation',
        'id' => $stateId,
      ]);
    }

    return $address;
  }
}
