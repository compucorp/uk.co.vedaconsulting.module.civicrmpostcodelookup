# Postcode lookup for CiviCRM

## Overview

For having postcode lookup feature in CiviCRM backend and Front end profiles.

### Supported Providers

* [AFD](http://www.afd.co.uk)
* [Civipostcode](http://civipostcode.com/)
* [Experian](http://www.qas.co.uk)
* [PostcodeAnywhere](http://www.postcodeanywhere.co.uk/)
* [getAddress()](https://getaddress.io/)

### Installation

1. See: https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/#installing-a-new-extension.
1. Configure postcode lookup provider details in *Administer->Postcode Lookup*.

### Permissions

**From version 1.6 you need to give the `Access CiviCRM Postcode lookups` permission to anyone who can do postcode lookups (eg. anonymous user).**  Previously the permission required was `Access CiviEvent`.

### Integration with Drupal Webform
This drupal module provides integration with Drupal Webform: https://github.com/compucorp/webform_civicrm_postcode

### Usage

* For backend, postcode lookup features is automatically enabled for address fields when adding/editing
contacts and configuring event location.
* For front end profiles, postcode lookup feature is enabled for payment billing address, primary address
and all location types set in *Administer->Postcode lookup*.

Include 'Supplemental Address 1' and 'Supplemental Address 2' fields in the profile for address lines based on the rules in the Royal Mail programmers guide.

## Changelog

### 1.9
* Cleanup the postcode selector that was being added multiple times in some cases.
* Only show a single loading "spinner" when searching for postcodes.

### 1.8
* Update jQuery and trigger change on all billing fields that we touch to trigger other functions (eg. billing address is the same). This fixes issues when "My Billing address is the same" is checked.
* Switch to \Civi::settings to get/set settings (removes a deprecated function warning).

### 1.7
* Fixed parsing of address.

