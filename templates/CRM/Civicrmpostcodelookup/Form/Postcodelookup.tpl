{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var locationTypes = {/literal}{if $civiPostCodeLookupLocationTypeJson}{$civiPostCodeLookupLocationTypeJson}{else}''{/if}{literal};
    var blockId = '';
    var blockNo = '';

    if ($('#editrow-street_address-Primary').length > 0) {
      var blockId = 'Primary';
      var blockNo = 'Primary';
      var targetHtml = '';
      var postCodeHtml = '<div class="crm-section addressLookup form-item"><div class="label"><label for="addressLookup">Search for an address</label></div><div class="edit-value content"><div class="postcodelookup-textbox-wrapper"><input placeholder="Start typing a postcode" name="inputPostCode_' + blockId + '" id ="inputPostCode_' + blockId + '" style="width: 25em;"></div><div class="loader-image"><img id="loaderimage_' + blockId + '" src="{/literal}{$config->resourceBase}{literal}i/loading.gif" style="width:15px;height:15px; display: none" /></div></div><div class="clear"></div></div>';
      $('#editrow-street_address-Primary').before(postCodeHtml);
    }
    else if ($('#editrow-street_address-5').length > 0) {
      var blockId = '5';
      var blockNo = '5';
      var targetHtml = '';
      var divHtml = $('#editrow-street_address-5').html();
      var postCodeHtml = '<div class="crm-section addressLookup form-item"><div class="label"><label for="addressLookup">Search for an address</label></div><div class="edit-value content"><div class="postcodelookup-textbox-wrapper"><input placeholder="Start typing a postcode" name="inputPostCode_' + blockId + '" id ="inputPostCode_' + blockId + '" style="width: 25em;"></div><div class="loader-image"><img id="loaderimage_' + blockId + '" src="{/literal}{$config->resourceBase}{literal}i/loading.gif" style="width:15px;height:15px; display: none" /></div></div><div class="clear"></div></div>';
      $('#editrow-street_address-5').before(postCodeHtml);
    }

    // Include lookup in billing section as well
    if ($('#billing_street_address-5').length > 0) {
      var billingblockId = '5';
      var billingblockNo = '5';
      var billingtargetHtml = '';
      var billingdivHtml = $('#billing_street_address-5').html();
      var billingpostCodeHtml = '<div class="crm-section addressLookup form-item"><div class="label"><label for="addressLookup">Search for an address</label></div><div class="edit-value content"><div class="postcodelookup-textbox-wrapper"><input placeholder="Start typing a postcode" name="inputPostCodeBillingSection_' + billingblockId + '" id ="inputPostCodeBillingSection_' + billingblockId + '" style="width: 25em;"></div><div class="loader-image"><img id="loaderimage_' + billingblockId + '" src="{/literal}{$config->resourceBase}{literal}i/loading.gif" style="width:15px;height:15px; display: none" /></div></div><div class="clear"></div></div>';
      $('.billing_street_address-5-section').before(billingpostCodeHtml);

      var billingPostcodeElement = '#inputPostCodeBillingSection_'+billingblockNo;
    }
    //Location Types from settings
    if (locationTypes) {
      $.each(locationTypes, function (id, index) {
        if ($('#editrow-street_address-' + id).length > 0) {
          blockId = id;
          blockNo = id;
          var targetHtml = '';
          // var divHtml = $('#editrow-street_address-'+ id).html();
          var postCodeHtml = '<div class="crm-section addressLookup form-item"><div class="label"><label for="addressLookup">Search for an address</label></div><div class="edit-value content"><div class="postcodelookup-textbox-wrapper"><input placeholder="Start typing a postcode" name="inputPostCode_' + blockId + '" id ="inputPostCode_' + blockId + '" style="width: 25em;"></div><div class="loader-image"><img id="loaderimage_' + blockId + '" src="{/literal}{$config->resourceBase}{literal}i/loading.gif" style="width:15px;height:15px; display: none" /></div></div><div class="clear"></div></div>';
          $('#editrow-street_address-'+ id).before(postCodeHtml);
        }
      });
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
      var cityElement = '#' + blockPrefix + 'city-'+ blockNo;
      var countyElement = '#address_'+ blockNo +'_state_province_id';

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
        $(streetAddressElement).val('');
        $(AddstreetAddressElement).val('');
        $(AddstreetAddressElement1).val('');
        $(cityElement).val('');
        $(postcodeElement).val('');
        $(countyElement).val('');

        $(streetAddressElement).val(address.street_address);
        $(AddstreetAddressElement).val(address.supplemental_address_1);
        $(AddstreetAddressElement1).val(address.supplemental_address_2);
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
