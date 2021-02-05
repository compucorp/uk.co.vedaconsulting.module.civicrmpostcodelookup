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
    $servertarget = $servertarget . "/find";

    // search by postcode
    if ($postcode && !empty($postcode)) {
      $servertarget = $servertarget . "/" . $postcode;

      // search by house number
      if ($number && !empty($number)) {
        $servertarget = $servertarget . "/" . $number;
      }
    }

    $apiKey = $settingsArray['api_key'];

    $querystring = "api-key=$apiKey&expand=true";
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
      $resultObject = json_decode($result);
      $addressData = (array)$resultObject;
      $addressData['is_error'] = 0;
    }
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
    $AddressListItem = $addressData['addresses'];
    foreach ($AddressListItem as $key => $addressItem) {

      // FIX me : There is no address id found in th API, hence assigning combination of postcode & arrayresultID as rowId inorder to get the selected address later
      $addressId = $postcode . '_' . $key;

      $addressLineArray = self::formatAddressLines($addressId, $addressItem);
      // Don't display country_id in address list
      unset($addressLineArray['country_id']);
      $addressLineArray['postcode'] = $postcode;

      $addressRow['id'] = $addressId;
      $addressRow['value'] = $postcode;
      $addressRow['label'] = @implode(', ', $addressLineArray);
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

    if (!empty($addressItem->line_1)) {
      $address['street_address'] = $addressItem->line_1;
    }
    if (!empty($addressItem->line_2)) {
      $address['supplemental_address_1'] = $addressItem->line_2;
    };
    if (!empty($addressItem->line_3)) {
      $address['supplemental_address_2'] = $addressItem->line_2;
    }
    if (!empty($addressItem->town_or_city)) {
      $address['city'] = $addressItem->town_or_city;
    }

    // Get state/county
    if (!isset(\Civi::$statics[__FUNCTION__]['stateprovince'])) {
      \Civi::$statics[__FUNCTION__]['stateprovince'] = CRM_Core_PseudoConstant::stateProvince();
    }

    $address['state_province_id'] = '';
    if (!empty($addressItem->county)) {
      $stateProvinceID = array_search($addressItem->county, \Civi::$statics[__FUNCTION__]['stateprovince']);

      if ($stateProvinceID) {
        // Display actual state name in selection list
        $address['state_province_id'] = $stateProvinceID;
      }
      $address['state_province'] = $addressItem->county;
    }
    $address['country_id'] = 1226;

    return $address;
  }

}
