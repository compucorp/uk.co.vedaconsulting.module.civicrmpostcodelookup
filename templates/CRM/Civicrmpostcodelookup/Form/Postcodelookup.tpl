{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var locationTypes = {/literal}{$civiPostCodeLookupLocationTypeJson}{literal};
    var blockId = '';
    var blockNo = '';
    var addressSelector = '';
    var postCodeHtml = '';

    // Location Types from settings
    if (locationTypes) {
      $.each(locationTypes, function (id, index) {
        addressSelector = '#editrow-street_address-' + id;
        if ($(addressSelector).length > 0) {
          blockId = id;
          blockNo = id;
          postCodeHtml = '<div class="crm-section addressLookup form-item"><div class="label"><label for="addressLookup">Search for an address</label></div><div class="edit-value content"><div class="postcodelookup-textbox-wrapper"><input placeholder="Start typing a postcode" name="inputPostCode_' + blockId + '" id ="inputPostCode_' + blockId + '" style="width: 25em;"></div></div><div class="clear"></div></div>';
          if ($('#inputPostCode_' + blockId).length === 0) {
            $(addressSelector).before(postCodeHtml);
          }
        }
      });
    }

    // Include lookup in billing section as well
    if ($('#billing_street_address-5').length > 0) {
      var billingblockId = '5';
      var billingblockNo = '5';
      var billingpostCodeHtml = '<div class="crm-section addressLookup form-item"><div class="label"><label for="addressLookup">Search for an address</label></div><div class="edit-value content"><div class="postcodelookup-textbox-wrapper"><input placeholder="Start typing a postcode" name="inputPostCodeBillingSection_' + billingblockId + '" id ="inputPostCodeBillingSection_' + billingblockId + '" style="width: 25em;"></div></div><div class="clear"></div></div>';
      $('.billing_street_address-5-section').before(billingpostCodeHtml);
      var billingPostcodeElement = '#inputPostCodeBillingSection_'+billingblockNo;
    }

    var houseElement = '#inputNumber_'+blockNo;
    var postcodeElement = '#inputPostCode_'+blockNo;
    var minCharacters = 4;

    var postcodeProvider = '{/literal}{$civiPostCodeLookupProvider}{literal}';
    if (postcodeProvider !== 'civipostcode') {
      $(postcodeElement).attr("placeholder", "Type full postcode to find addresses");
      minCharacters = 5;
    }

    var sourceUrl = CRM.url('civicrm/{/literal}{$civiPostCodeLookupProvider}{literal}/ajax/search', {"json": 1});

    $(postcodeElement).autocomplete({
      source: sourceUrl,
      minLength: minCharacters,
      data: {postcode: $( postcodeElement ).val(), number: $(houseElement).val(), mode: '0'},
      search: function( event, ui ) {
        $('#loaderimage_'+blockNo).show();
      },
      response: function( event, ui ) {
        $('#loaderimage_'+blockNo).hide();
      },
      select: function(event, ui) {
        if (ui.item.id !== '') {
          findAddressValues(ui.item.id, blockNo, blockPrefix = '');
          $('#loaderimage_'+blockNo).show();
        }
        return false;
      },
      html: true, // optional (jquery.ui.autocomplete.html.js required)

      //optional (if other layers overlap autocomplete list)
      open: function(event, ui) {
        $(".ui-autocomplete").css("z-index", 1000);
      }
    });

    // Postcode lookup in billing section
    if ($('#billing_street_address-5').length > 0 ) {
      $(billingPostcodeElement).autocomplete({
        source: sourceUrl,
        minLength: minCharacters,
        data: {postcode: $( billingPostcodeElement ).val(), number: $(houseElement).val(), mode: '0'},
        search: function( event, ui ) {
          $('#loaderimage_'+blockNo).show();
        },
        response: function( event, ui ) {
          $('#loaderimage_'+blockNo).hide();
        },
        select: function(event, ui) {
          if (ui.item.id !== '') {
            findAddressValues(ui.item.id, '5', blockPrefix = 'billing_');
            $('#loaderimage_'+blockNo).show();
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

    function findAddressValues(id , blockNo, blockPrefix) {
      $('#loaderimage_'+blockNo).show();
      setAddressFields(false, blockNo, blockPrefix);
      var sourceUrl = CRM.url('civicrm/{/literal}{$civiPostCodeLookupProvider}{literal}/ajax/get', {"json": 1});
      $.ajax({
        dataType: 'json',
        data: {id: id, mode: '0'},
        url: sourceUrl,
        success: function (data) {
          setAddressFields(data.address, blockNo, blockPrefix);
          setAddressFields(true, blockNo, blockPrefix);
        },
        complete: function (data) {
          $('#loaderimage_'+blockNo).hide();
        }
      });
    }

    function setAddressFields(address, blockNo, blockPrefix) {
      var postcodeElement = '#' + blockPrefix + 'postal_code-'+ blockNo;
      var streetAddressElement = '#' + blockPrefix + 'street_address-'+ blockNo;
      var AddstreetAddressElement = '#' + blockPrefix + 'supplemental_address_1-'+ blockNo;
      var AddstreetAddressElement1 = '#' + blockPrefix + 'supplemental_address_2-'+ blockNo;
      var AddstreetAddressElement2 = '#' + blockPrefix + 'supplemental_address_3-'+ blockNo;
      var cityElement = '#' + blockPrefix + 'city-'+ blockNo;
      var countyElement = '#' + blockPrefix +'state_province-'+ blockNo;
      if(cj('#' + blockPrefix +'state_province_id-'+ blockNo).length) {
        countyElement =  '#' + blockPrefix +'state_province_id-'+ blockNo;
      }

      var allFields = {
        postcode: postcodeElement,
        line1: streetAddressElement,
        line2: AddstreetAddressElement,
        line3: AddstreetAddressElement1,
        city: cityElement
      };

      if(address === true) {
        for(var field in allFields) {
          $(allFields[field]).removeAttr('disabled');
        }
      }
      else if(address === false) {
        for (var field in allFields) {
          $(allFields[field]).attr('disabled', 'disabled');
        }
      }
      else {
        if(typeof address.supplemental_address_3 == 'undefined')
          address.supplemental_address_3 = '';
        var addr = [];
        if(address.supplemental_address_1.length == 0 &&
                address.supplemental_address_2.length == 0 &&
                address.supplemental_address_3.length == 0) {
          addr = address.street_address.split(",");
          if(addr.length) {
            address.street_address = addr.shift();
          }
          if(addr.length) {
            address.supplemental_address_1 = addr.shift();
          }
          if(addr.length) {
            address.supplemental_address_2 = addr.shift();
          }
          if(addr.length) {
            address.supplemental_address_3 = addr.join(', ');
          }
        }
        else if (address.supplemental_address_2.length == 0 &&
                address.supplemental_address_3.length == 0) {
          addr = address.street_address.split(",");
          if(addr.length) {
            address.street_address = addr.shift();
          }
          if(addr.length) {
            address.supplemental_address_2 = address.supplemental_address_1;
            address.supplemental_address_1 = addr.shift();
          }
          if(addr.length) {
            address.supplemental_address_3 = address.supplemental_address_2;
            address.supplemental_address_2 = addr.join(', ');
          }
        }
        else if (address.supplemental_address_3.length == 0) {
          addr = address.street_address.split(",");
          if(addr.length) {
            address.street_address = addr.shift();
          }
          if(addr.length) {
            address.supplemental_address_3 = address.supplemental_address_2;
            address.supplemental_address_2 = address.supplemental_address_1;
            address.supplemental_address_1 = addr.join(', ');
          }
        }

        $(streetAddressElement).val('');
        $(AddstreetAddressElement).val('');
        $(AddstreetAddressElement1).val('');
        $(cityElement).val('');
        $(postcodeElement).val('');
        $(countyElement).val('');

        $(streetAddressElement).val(address.street_address);
        $(AddstreetAddressElement).val(address.supplemental_address_1);
        $(AddstreetAddressElement1).val(address.supplemental_address_2);
        $(AddstreetAddressElement2).val(address.supplemental_address_3);
        $(cityElement).val(address.town);
        $(postcodeElement).val(address.postcode);
        if (typeof(address.state_province_id) !== 'undefined' && address.state_province_id !== null) {
          $(countyElement).val(address.state_province_id);
        }

        // Trigger change on all the elements we touch so that other functions can react
        //   eg. a checkbox for "My billing address is the same".
        $(streetAddressElement).trigger("change");
        $(AddstreetAddressElement).trigger("change");
        $(AddstreetAddressElement1).trigger("change");
        $(cityElement).trigger("change");
        $(postcodeElement).trigger("change");
        $(countyElement).trigger("change");
      }
    }
  });
</script>
{/literal}
