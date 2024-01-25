<?php

class CRM_Civicrmpostcodelookup_Page_GetAddressIo extends CRM_Civicrmpostcodelookup_Page_Postcode {

  public static function isValidPostcode($postcode) {
    if (in_array($postcode, ['XX200X', 'XX404X', 'XX400X', 'XX401X', 'XX429X', 'XX500X'])) {
      // A getAddressIo test postcode
      return TRUE;
    }
    return parent::isValidPostcode($postcode);
  }

  /*
   * Function to get address list based on a Post code
   */
  public static function search() {
    $postcode = self::getPostcode(FALSE);

    if (!self::isValidPostcode($postcode)) {
      exit;
    }

    $addressList = \Civi::cache('long')->get("ukpostcodes_{$postcode}") ?? NULL;
    if (!isset($addressList)) {
      // get address result from getAddress.io
      $apiUrl = self::getAddressIoApiUrl($postcode);
      $addressData = self::addressAPIResult($apiUrl);
      if ($addressData['is_error']) {
        $addressList[0]['value'] = '';
        $addressList[0]['label'] = CRM_Utils_Array::value('Message', $addressData, 'Error in fetching address');
      } else {
        $addressList = self::getAddressList($addressData, $postcode);
      }
      \Civi::cache('long')->set("ukpostcodes_{$postcode}", $addressList);
    }

    echo json_encode($addressList);
    exit;
  }

  /*
   * Function to get address details based on the selected address
   */
  public static function getaddress() {
    $selectedId = CRM_Utils_Request::retrieve('id', 'String');
    if (empty($selectedId)) {
      exit;
    }

    // get postcode & address key from selectedId
    $selectedResult = explode('_', $selectedId);
    $postcode = CRM_Civicrmpostcodelookup_Page_Civipostcode::format($selectedResult[0], FALSE);
    $addressKey = $selectedResult[1];

    $addressList = \Civi::cache('long')->get("ukpostcodes_{$postcode}") ?? NULL;

    // selected result from the addressItems
    $addressItem = $addressList[$addressKey];

    $address = $addressItem['lineArray'];
    // Fix me : postcode not returned in the API result, hence using the one from the selected ID
    $address['postcode'] = $postcode;

    $response = [
      'address' => $address
    ];

    echo json_encode($response);
    exit;
  }

  /*
   * Function to get the API URL
   */
  private static function getAddressIoApiUrl($postcode = NULL, $number = NULL) {
    #################
    #API settings
    #################
    $settingsStr = \Civi::settings()->get('api_details');
    $settingsArray = unserialize($settingsStr);

    $servertarget = $settingsArray['server'];

    // https://api.getAddress.io/find/{postcode}/{house}
    $servertarget = $servertarget . "/autocomplete";

    // search by postcode
    if ($postcode && !empty($postcode)) {
      $servertarget = $servertarget . "/" . $postcode;

      // search by house number
      if ($number && !empty($number)) {
        $servertarget = $servertarget . "/" . $number;
      }
    }

    $apiKey = $settingsArray['api_key'];

    $querystring = "api-key=$apiKey&all=true&template={line_1},{line_2},{line_3},{line_4},{town_or_city},{locality},{county}";
    
    \Civi::log('civicrmpostcodelookup')->info("calling url: ".$servertarget ."?" . $querystring);
    return $servertarget ."?" . $querystring;
  }

  /**
   * Function to get Address result from getAddress.io
   *
   * @param $apiUrl
   *
   * @return array
   */
  private static function addressAPIResult($apiUrl) {
    $addressData = [];

    if (empty($apiUrl)) {
      $addressData['is_error'] = 1;
      CRM_Core_Error::debug_var('apiURL empty in get addressAPIResult ', ' ');
      return $addressData;
    }

    // Get the Address Data
    $curlSession = curl_init();
    curl_setopt_array($curlSession, [
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => $apiUrl,
      CURLOPT_USERAGENT => 'CiviCRM'
    ]);

    $result = curl_exec($curlSession);
    $header = curl_getinfo($curlSession);

    $curlError['code'] = curl_errno($curlSession);
    $curlError['message'] = curl_error($curlSession);
    $curlError['httpCode'] = $header['http_code'];
    curl_close($curlSession);

    if ($header['http_code'] !== 200) {
      $addressData['is_error'] = 1;
      switch ($header['http_code']) {
        case 404:
          $addressData['Message'] = 'No addresses found';
          break;
        case 400:
          $addressData['Message'] = 'Invalid postcode';
          break;
        case 401:
          $addressData['Message'] = 'Invalid API Key';
          break;
        case 429:
          $addressData['Message'] = 'Too many requests';
          break;
        case 500:
          $addressData['Message'] = 'Server Error';
          break;
      }
    }
    elseif (!empty($curlError['code'])) {
      // Log & return error
      $addressData['is_error'] = 1;
      Civi::log()->debug('GetAddressIo cURL error: ' . print_r($curlError, TRUE));
      $addressData['Message'] = 'Unknown Error';
    }
    else {
      #\Civi::log('civicrmpostcodelookup')->info("api returned raw: ". $result);
      $resultObject = json_decode($result,TRUE);
      $addressData = $resultObject;
      $addressData['is_error'] = 0;
    }
    
    #\Civi::log('civicrmpostcodelookup')->info("api returned: ". print_r($addressData,TRUE));
    return $addressData;
  }

  /**
   * Format the list of found addresses
   *
   * @param array $addressData
   * @param string Full postcode, without space
   *
   * @return array
   */
  private static function getAddressList($addressData, $postcode) {
    $addressList = [];
    $addressRow = [];

    // return, if adddressData/postcode is empty
    if (empty($addressData) || empty($postcode)) {
      $addressRow["id"] = '';
      $addressRow["value"] = '';
      $addressRow["label"] = 'Postcode Not Found';
      array_push($addressList, $addressRow);
      return $addressList;
    }
    $postcode = self::format($postcode, TRUE);
    $AddressListItem = $addressData['suggestions'];
    foreach ($AddressListItem as $key => $addressItem) {

      \Civi::log('civicrmpostcodelookup')->info("processing api line: ".$key." - ". print_r($addressItem,TRUE));

      // FIX me : There is no address id found in th API, hence assigning combination of postcode & arrayresultID as rowId inorder to get the selected address later
      $addressId = $postcode . '_' . $key;

      $addressLineArray = self::formatAddressLines($addressId, $addressItem);
      
      $addressLineLabelArray = $addressLineArray;
      unset($addressLineLabelArray['country_id']);
      unset($addressLineLabelArray['state_province_id']);
      unset($addressLineLabelArray['country']);

      // Don't display country_id in address list
      
      $addressLineArray['postcode'] = $postcode;

      $addressRow['id'] = $addressId;
      $addressRow['value'] = $postcode;
      
      
      $addressRow['label'] = @implode(', ', $addressLineLabelArray);
      
      $addressRow['lineArray'] = $addressLineArray;
      array_push($addressList, $addressRow);
    }

    if (empty($addressList)) {
      $addressRow['id'] = '';
      $addressRow['value'] = '';
      $addressRow['label'] = 'Postcode Not Found';
      array_push($addressList, $addressRow);
    }


    return $addressList;
  }

  /**
   * @param string $addressId
   * @param array $addressItem
   *
   * @return array|void
   */
  private static function formatAddressLines($addressId, $addressItem) {
    
    if (empty($addressItem)) {
      return;
    }
    
    $address=array();
    
    $address_str=$addressItem["address"];
    $address_bits=explode(",",$address_str);

    #\Civi::log('civicrmpostcodelookup')->info("address bits: ". print_r($address_bits,TRUE));

    if (!empty($address_bits[0])) {
      $address['street_address'] = $address_bits[0];
    }

    if (!empty($address_bits[1])) {
      $address['supplemental_address_1'] = $address_bits[1];
    }
      
    if (!empty($address_bits[2])) {
      $address['supplemental_address_2']=$address_bits[2];
    }

    if (!empty($address_bits[3])) {
      $address['supplemental_address_3']=$address_bits[3];
    }


    
    if (!empty($address_bits[4])) {
      $address['city'] = $address_bits[4];
    }
 
    // Get state/county
    if (!isset(\Civi::$statics[__FUNCTION__]['stateprovince'])) {
      \Civi::$statics[__FUNCTION__]['stateprovince'] = CRM_Core_PseudoConstant::stateProvince();
    }

    $address['state_province_id'] = '';
    if (!empty($address_bits[6])) {
    
      #\Civi::log('civicrmpostcodelookup')->info("county= ". $address_bits[6]);
      $stateProvinceID = array_search($address_bits[6], \Civi::$statics[__FUNCTION__]['stateprovince']);
      #\Civi::log('civicrmpostcodelookup')->info("countycode = ". $stateProvinceID);
      if ($stateProvinceID) {
        // Display actual state name in selection list
        $address['state_province_id'] = $stateProvinceID;
      }
      $address['state_province'] = $address_bits[6];
    }
    $address['country_id'] = 1226;
    $address['country'] = "United Kingdom";

    return $address;
  }

}
