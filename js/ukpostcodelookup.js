(function($) {

  var ukpostcodes = {
    load: function() {
      var addressSelectors = CRM.vars.ukpostcodes.addressSelectors;
      var blockId = '';
      var postCodeHtml = '';

      // Location Types from settings
      if (addressSelectors) {
        $.each(addressSelectors, function (id, address) {
          if ($(address.selector).length > 0) {
            blockId = address.prefix + address.suffix;
            var postcodeElement = 'inputPostCode_' + blockId;
            postCodeHtml = '' +
              '<div class="crm-section addressLookup form-item">' +
              '<div class="label">' +
              '<label for="addressLookup">Search for an address</label>' +
              '</div>' +
              '<div class="edit-value content">' +
              '<div class="crm-postcodelookup-textbox-wrapper">' +
              '<input placeholder="Start typing a postcode" name="' + postcodeElement + '" id="' + postcodeElement + '" class="crm-postcodelookup ui-autocomplete-input">' +
              '</div>' +
              '</div>' +
              '<div class="clear" />' +
              '</div>';
            if ($('#' + postcodeElement).length === 0) {
              $(address.beforeSelector).before(postCodeHtml);

              var minCharacters = 4;

              var postcodeProvider = CRM.vars.ukpostcodes.lookupProvider;
              if (postcodeProvider !== 'civipostcode') {
                $(postcodeElement)
                  .attr("placeholder", "Type full postcode to find addresses");
                minCharacters = 5;
              }

              var sourceUrl = CRM.url('civicrm/' + CRM.vars.ukpostcodes.lookupProvider + '/ajax/search', {"json": 1});

              $('#' + postcodeElement).autocomplete({
                source: sourceUrl,
                minLength: minCharacters,
                data: {postcode: $('#' + postcodeElement).val(), mode: '0'},
                select: function (event, ui) {
                  if (ui.item.id !== '') {
                    findAddressValues(ui.item.id, address.suffix, address.prefix);
                  }
                  return false;
                },
                html: true, // optional (jquery.ui.autocomplete.html.js required)

                //optional (if other layers overlap autocomplete list)
                open: function (event, ui) {
                  $(".ui-autocomplete").css("z-index", 1000);
                }
              });
            }
          }
        });
      }

      function findAddressValues(id, blockSuffix, blockPrefix) {
        setAddressFields(false, blockSuffix, blockPrefix);
        var sourceUrl = CRM.url('civicrm/' + CRM.vars.ukpostcodes.lookupProvider + '/ajax/get', {"json": 1});
        $.ajax({
          dataType: 'json',
          data: {id: id, mode: '0'},
          url: sourceUrl,
          success: function (data) {
            setAddressFields(data.address, blockSuffix, blockPrefix);
            setAddressFields(true, blockSuffix, blockPrefix);
          }
        });
      }

      function setAddressFields(address, blockSuffix, blockPrefix) {
        var postcodeElement = '#' + blockPrefix + 'postal_code' + blockSuffix;
        var streetAddressElement = '#' + blockPrefix + 'street_address' + blockSuffix;
        var AddstreetAddressElement = '#' + blockPrefix + 'supplemental_address_1' + blockSuffix;
        var AddstreetAddressElement1 = '#' + blockPrefix + 'supplemental_address_2' + blockSuffix;
        var AddstreetAddressElement2 = '#' + blockPrefix + 'supplemental_address_3' + blockSuffix;
        var cityElement = '#' + blockPrefix + 'city' + blockSuffix;
        var countyElement = '#' + blockPrefix + 'state_province_id' + blockSuffix;
        if ($(countyElement).length === 0) {
          countyElement = '#' + blockPrefix + 'state_province' + blockSuffix;
        }
        var countryElement = '#' + blockPrefix + 'country_id' + blockSuffix;
        if ($(countryElement).length === 0) {
          countryElement = '#' + blockPrefix + 'country' + blockSuffix;
        }

        var allFields = {
          postcode: postcodeElement,
          line1: streetAddressElement,
          line2: AddstreetAddressElement,
          line3: AddstreetAddressElement1,
          line4: AddstreetAddressElement2,
          city: cityElement
        };

        if (address === true) {
          for (var field in allFields) {
            $(allFields[field]).removeAttr('disabled');
          }
        }
        else
          if (address === false) {
            for (var field2 in allFields) {
              $(allFields[field2]).attr('disabled', 'disabled');
            }
          }
          else {
            $(streetAddressElement).val('');
            $(AddstreetAddressElement).val('');
            $(AddstreetAddressElement1).val('');
            $(AddstreetAddressElement2).val('');
            $(cityElement).val('');
            $(postcodeElement).val('');
            if (address.country_id) {
              $(countryElement).val(address.country_id).trigger('change');
            }

            // Helper to join address line parts, skipping any that are undefined/null/empty.
            // Avoids stray ", " separators and prevents in-place mutation of source values.
            var joinAddressParts = function () {
              var parts = [];
              for (var i = 0; i < arguments.length; i++) {
                var v = arguments[i];
                if (typeof v !== 'undefined' && v !== null && v !== '') {
                  parts.push(v);
                }
              }
              return parts.join(', ');
            };

            // If the Address Line 4 (supplemental_address_3) field is not on the form,
            // fold its value up into supplemental_address_2.
            if (($(AddstreetAddressElement2).length === 0) && (typeof address.supplemental_address_3 !== 'undefined') && address.supplemental_address_3 !== '') {
              address.supplemental_address_2 = joinAddressParts(address.supplemental_address_2, address.supplemental_address_3);
              address.supplemental_address_3 = '';
            }
            // If the Address Line 3 (supplemental_address_2) field is not on the form,
            // fold its value up into supplemental_address_1.
            if (($(AddstreetAddressElement1).length === 0) && (typeof address.supplemental_address_2 !== 'undefined') && address.supplemental_address_2 !== '') {
              address.supplemental_address_1 = joinAddressParts(address.supplemental_address_1, address.supplemental_address_2);
              address.supplemental_address_2 = '';
            }
            // If the Address Line 2 (supplemental_address_1) field is not on the form,
            // fold its value up into street_address.
            if (($(AddstreetAddressElement).length === 0) && (typeof address.supplemental_address_1 !== 'undefined') && address.supplemental_address_1 !== '') {
              address.street_address = joinAddressParts(address.street_address, address.supplemental_address_1);
              address.supplemental_address_1 = '';
            }

            $(streetAddressElement).val(address.street_address);
            $(AddstreetAddressElement).val(address.supplemental_address_1);
            $(AddstreetAddressElement1).val(address.supplemental_address_2);
            $(AddstreetAddressElement2).val(address.supplemental_address_3);
            $(cityElement).val(address.city);
            $(postcodeElement).val(address.postcode);

            if (typeof (address.state_province_id) !== 'undefined' && address.state_province_id !== null) {
              $(countyElement).val(address.state_province_id);
            }

            // Trigger change on all the elements we touch so that other functions can react
            //   eg. a checkbox for "My billing address is the same".
            $(streetAddressElement).trigger('change');
            $(AddstreetAddressElement).trigger('change');
            $(AddstreetAddressElement1).trigger('change');
            $(AddstreetAddressElement2).trigger('change');
            $(cityElement).trigger('change');
            $(postcodeElement).trigger('change');
            $(countyElement).trigger('change');
          }
      }
    }
  };

  if (typeof CRM.ukpostcodes === 'undefined') {
    CRM.ukpostcodes = ukpostcodes;
  }

  document.addEventListener('DOMContentLoaded', function() {
    CRM.ukpostcodes.load();
  });

  // Re-prep form when we've loaded a new payproc via ajax or via webform
  $(document).ajaxComplete(function(event, xhr, settings) {
    // Load postcode lookup on inline address edit (contact summary)
    // On wordpress these are urlencoded
    if (settings.url.match("civicrm(\/|%2F)ajax(\/|%2F)inline(.*)class_name=CRM_Contact_Form_Inline_Address") !== null) {
      CRM.ukpostcodes.load();
    }
  });

}(CRM.$));
