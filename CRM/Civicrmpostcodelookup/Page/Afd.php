<?php

require_once 'CRM/Core/Page.php';

class CRM_Civicrmpostcodelookup_Page_Afd extends CRM_Civicrmpostcodelookup_Page_Postcode {

  /*
   * Function to get the Server URL and login credentials
   */
  public static function getAFDCredentials($action = 1) {
    #################
    #Server settings
    #################
    $settingsStr = \Civi::settings()->get('api_details');
    $settingsArray = unserialize($settingsStr);

    $servertarget = $settingsArray['server'];

    // Action : '1' - Address List, '2' - Address Lookup
    switch ($action) {
      case 1:
        $servertarget = $servertarget . "/addresslist.pce";
        break;

      case 2:
        $servertarget = $servertarget . "/addresslookup.pce";
        break;

      default:
        $servertarget = $servertarget . "/addresslist.pce";
    }

    $strSerial = $settingsArray['serial_number'];
    $strPassword = $settingsArray['password'];

    $querystring = "serial=$strSerial&password=$strPassword";
    return $servertarget ."?" . $querystring;
  }

  /*
   * Function to get address list based on a Post code
   */
  public static function search() {
    $postcode = self::getPostcode(TRUE); // FIXME: Check whether API requires space or not
    $number = CRM_Utils_Request::retrieve('number', 'String');

    $querystring = self::getAFDCredentials(1);
    $querystring = $querystring . "&postcode=" . $postcode . "&property=" . $number;

    ###############
    #File Handling
    ###############

    ##Open the XML Document##
    $filetoparse = fopen("$querystring","r") or die("Error reading XML data.");
    $data = stream_get_contents($filetoparse);
    $simpleXMLData = simplexml_load_string($data);

    if (!empty($simpleXMLData)) {
      $addresslist = self::getAddressList($simpleXMLData, $postcode);
    }

    ##Close the XML source##
    fclose($filetoparse);

    echo json_encode($addresslist);
    exit;
  }

  private static function getAddressList($simpleXMLData, $postcode) {
    $addressList = [];
    $addressRow = [];
    $AddressListItem = $simpleXMLData->AddressListItem;
    foreach ($AddressListItem as $key => $addressItem) {
      $addressRow["id"] = (string) $addressItem->PostKey;
      $addressRow["value"] = $postcode;
      $addressRow["label"] = (string) $addressItem->Address;
      array_push($addressList, $addressRow);
      //$addressList['items'][] = array('id' => (string) $addressItem->PostKey, 'label' => (string) $addressItem->Address);
      //$addressList[(string) $addressItem->PostKey] = (string) $addressItem->Address;
    }

    return $addressList;
  }

  /*
   * Function to get address details based on the AFD addressid/postkey
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
    $querystring = self::getAFDCredentials(2);
    $querystring = $querystring . "&postkey=" . urlencode($moniker);

    ###############
    #File Handling
    ###############

    ##Open the XML Document##
    $filetoparse = fopen("$querystring","r") or die("Error reading XML data.");
    $data = stream_get_contents($filetoparse);
    $simpleXMLData = simplexml_load_string($data);

    $address = ['id' => $moniker];
    $addressItem = (array) $simpleXMLData->Address;

    $address["street"] = empty($addressItem['Street']) ? '':$addressItem['Street'];
    $address["locality"] = empty($addressItem['Locality']) ? '':$addressItem['Locality'];
    $address["town"] = empty($addressItem['Town']) ? '':$addressItem['Town'];
    $address["postcode"] = empty($addressItem['Postcode']) ? '':$addressItem['Postcode'];

    ##Close the XML source##
    fclose($filetoparse);

    return $address;
  }
}
