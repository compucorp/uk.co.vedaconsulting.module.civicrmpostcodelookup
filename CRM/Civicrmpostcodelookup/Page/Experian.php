<?php

require_once 'CRM/Core/Page.php';

// Access the QAS library via the  dependency 'llr_qas_library' module
require_once '/lib/QASCapture.php';

class CRM_PostcodeLookup_Page_Ajax extends CRM_Civicrmpostcodelookup_Page_Postcode {
  static private $qacampture;

  public static function getQasCredentials($account_type) {
    $credentials = [];

    $settingsStr = \Civi::settings()->get('api_details');
    $settingsArray = unserialize($settingsStr);

    $credentials['username'] = $settingsArray['username'];
    $credentials['password'] = $settingsArray['password'];

    /*// @todo Decide what format the value is coming in as int/hash etc
    $map = array(
      '0' => 'internal',
      '1' => 'external'
    );

    switch ($map[$account_type]) {
      case 'internal':
        $credentials['username'] = '7aab7efc-f66';
        $credentials['password'] = 'biscuitbase1';
        break;

      case 'external':
        $credentials['username'] = 'e220d980-d4d';
        $credentials['password'] = 'biscuitbase1';
        break;

      default:
        $credentials['username'] = '';
        $credentials['password'] = '';
    }*/

    return $credentials;
  }

  public static function search() {
    $postcode = self::getPostcode(TRUE); // FIXME: Check whether API requires space or not
    $number = CRM_Utils_Request::retrieve('number', 'String');
    if (!$number) {
      exit;
    }

    $qaCapture = self::getQACapture();
    $ret = $qaCapture->Search("$number, $postcode", 'GBR', 'Singleline', true);//, $intensity, $promptset, $threshold, $timeout, $layout, $formattedAddressInPicklist, $requestTag, $localisation)

    $response = [];
    $response['items'] = [];
    foreach($ret->Picklist->Items as $item) {
      $response['items'][] = [
        'id' => $item->Moniker,
        'label' => $item->PartialAddress,
      ];
    }

    //mzeman: get the address details if it's the precise one
    if($ret->Picklist->IsAutoformatSafe && $ret->Picklist->Total == 1) {
      $listItem = $ret->Picklist->Items[0];

      $address = self::getAddressByMoniker($listItem->Moniker);
      $response['address'] = $address;
    }

    echo json_encode($response);
    exit;
  }

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

  private static function getQACapture() {
    if(self::$qacampture === null) {
      // @todo retrieved value should be encoded somehow, MD5 or whatever
      $mode = CRM_Utils_Request::retrieve('mode', 'String');
      if (!$mode) {
        $mode = '1';
      }
      $params = self::getQasCredentials($mode);
      self::$qacampture = new QASCapture($params);
    }

    return self::$qacampture;
  }

  private static function getAddressByMoniker($moniker) {
    $addressRet = self::getQACapture()->GetAddress($moniker);

    $address = ['id' => $moniker];
    $lineCounter = 0;
    foreach($addressRet->AddressLines as $line) {
      switch($line->Label) {
        case '':
          $lineCounter++;
          $address["line{$lineCounter}"] = $line->Line;
          break;
        case 'Town':
          $address["town"] = $line->Line;
          break;
        case 'County':
          $address["county"] = $line->Line;
          break;
        case 'Postcode':
          $address["postcode"] = $line->Line;
          break;
      }
    }

    return $address;
  }
}
