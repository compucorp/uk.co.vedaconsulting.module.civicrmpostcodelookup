{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var addressSelectors = {/literal}{$ukpostcodesAddressSelectors}{literal};
    var blockId = '';
    var blockNo = '';
    var postCodeHtml = '';

    // Location Types from settings
    if (addressSelectors) {
      $.each(addressSelectors, function(id, address) {
        if ($(address.selector).length > 0) {
          blockId = address.prefix + address.id;
          var postcodeElement = 'inputPostCode_' + blockId;
          postCodeHtml = '' +
                  '<div class="crm-section addressLookup form-item">' +
                    '<div class="label">' +
                      '<label for="addressLookup">Search for an address</label>' +
                    '</div>' +
                    '<div class="edit-value content">' +
                      '<div class="postcodelookup-textbox-wrapper">' +
                        '<input placeholder="Start typing a postcode" name="' + postcodeElement + '" id="' + postcodeElement + '" class="crm-postcodelookup ui-autocomplete-input" style="width: 25em;">' +
                      '</div>' +
                    '</div>' +
                    '<div class="clear" />' +
                  '</div>';
          if ($('#' + postcodeElement).length === 0) {
            $(address.beforeSelector).before(postCodeHtml);

            var minCharacters = 4;

            var postcodeProvider = '{/literal}{$civiPostCodeLookupProvider}{literal}';
            if (postcodeProvider !== 'civipostcode') {
              $(postcodeElement).attr("placeholder", "Type full postcode to find addresses");
              minCharacters = 5;
            }

            var sourceUrl = CRM.url('civicrm/{/literal}{$civiPostCodeLookupProvider}{literal}/ajax/search', {"json": 1});

            $('#' + postcodeElement).autocomplete({
              source: sourceUrl,
              minLength: minCharacters,
              data: {postcode: $('#' + postcodeElement).val(), mode: '0'},
              select: function(event, ui) {
                if (ui.item.id !== '') {
                  findAddressValues(ui.item.id, address.id, address.prefix);
                }
                return false;
              },
              html: true, // optional (jquery.ui.autocomplete.html.js required)

              //optional (if other layers overlap autocomplete list)
              open: function(event, ui) {
                $(".ui-autocomplete").css("z-index", 1000);
              }
            });
          }
        }
      });
    }

    function findAddressValues(id , blockNo, blockPrefix) {
      setAddressFields(false, blockNo, blockPrefix);
      var sourceUrl = CRM.url('civicrm/{/literal}{$civiPostCodeLookupProvider}{literal}/ajax/get', {"json": 1});
      $.ajax({
        dataType: 'json',
        data: {id: id, mode: '0'},
        url: sourceUrl,
        success: function (data) {
          setAddressFields(data.address, blockNo, blockPrefix);
          setAddressFields(true, blockNo, blockPrefix);
        }
      });
    }

    function setAddressFields(address, blockNo, blockPrefix) {
      var postcodeElement = '#' + blockPrefix + 'postal_code-'+ blockNo;
      var streetAddressElement = '#' + blockPrefix + 'street_address-'+ blockNo;
      var AddstreetAddressElement = '#' + blockPrefix + 'supplemental_address_1-'+ blockNo;
      var AddstreetAddressElement1 = '#' + blockPrefix + 'supplemental_address_2-'+ blockNo;
      var cityElement = '#' + blockPrefix + 'city-'+ blockNo;
      var countyElement = '#address_'+ blockNo +'_state_province_id';
      var countryElement = '#' + blockPrefix + 'country_id-' + blockNo;
      if ($(countryElement).length === 0) {
        countryElement = '#' + blockPrefix + 'country-' + blockNo;
      }

      var allFields = {
        postcode: postcodeElement,
        line1: streetAddressElement,
        line2: AddstreetAddressElement,
        line3: AddstreetAddressElement1,
        city: cityElement
      };

      if (address === true) {
        for (var field in allFields) {
          $(allFields[field]).removeAttr('disabled');
        }
      }
      else if(address === false) {
        for (var field in allFields) {
          $(allFields[field]).attr('disabled', 'disabled');
        }
      }
      else {
        $(streetAddressElement).val('');
        $(AddstreetAddressElement).val('');
        $(AddstreetAddressElement1).val('');
        $(cityElement).val('');
        $(postcodeElement).val('');
        $(countyElement).val('');

        if (($(AddstreetAddressElement1).length === 0) && (typeof address.supplemental_address_2 !== 'undefined')) {
          if (typeof address.supplemental_address_1 !== 'undefined') {
            address.supplemental_address_1 = address.supplemental_address_1 + ', ';
          }
          address.supplemental_address_1 = address.supplemental_address_1 + address.supplemental_address_2;
        }
        if (($(AddstreetAddressElement).length === 0) && (typeof address.supplemental_address_1 !== 'undefined')) {
          address.street_address = address.street_address + ', ' + address.supplemental_address_1;
        }

        $(streetAddressElement).val(address.street_address);
        $(AddstreetAddressElement).val(address.supplemental_address_1);
        $(AddstreetAddressElement1).val(address.supplemental_address_2);
        $(cityElement).val(address.town);
        $(postcodeElement).val(address.postcode);
        $(countryElement).val('1226'); // United Kingdom
        if (typeof(address.state_province_id) !== 'undefined' && address.state_province_id !== null) {
          $(countyElement).val(address.state_province_id);
        }

        // Trigger change on all the elements we touch so that other functions can react
        //   eg. a checkbox for "My billing address is the same".
        $(streetAddressElement).trigger('change');
        $(AddstreetAddressElement).trigger('change');
        $(AddstreetAddressElement1).trigger('change');
        $(cityElement).trigger('change');
        $(postcodeElement).trigger('change');
        $(countryElement).trigger('change')
        $(countyElement).trigger('change');
      }
    }
  });
</script>
{/literal}
