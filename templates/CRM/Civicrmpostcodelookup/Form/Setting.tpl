{* HEADER *}

<div class="crm-block crm-form-block crm-export-form-block">
  {foreach from=$elementNames item=elementName}
    <div class="crm-section">
      <div class="label">{$form.$elementName.label}</div>
      <div class="content">{$form.$elementName.html}</div>
      <div class="clear"></div>
    </div>
  {/foreach}

  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>

{literal}
  <script>
    CRM.$(function($) {
      $('#server').parent().append('<br />Without trailing slash. Example: http://pce.afd.co.uk , http://civipostcode.com');
      hideAllFields();
      showFields();
      $('#provider').change(function() {
        hideAllFields();
        showFields();
      });

      function hideAllFields() {
        $('#server').parent().parent().hide();
        $('#api_key').parent().parent().hide();
        $('#serial_number').parent().parent().hide();
        $('#username').parent().parent().hide();
        $('#password').parent().parent().hide();
      }

      function showFields() {
        var providerVal = $("#provider").val();

        if (providerVal === 'experian') {
          $('#username').parent().parent().show();
          $('#password').parent().parent().show();
        }
        else if (providerVal === 'afd') {
          $('#server').parent().parent().show();
          $('#serial_number').parent().parent().show();
          $('#password').parent().parent().show();
          $('#server').val('http://pce.afd.co.uk');
        }
        else if (providerVal === 'civipostcode') {
          $('#server').parent().parent().show();
          $('#api_key').parent().parent().show();
          $('#server').val('http://civipostcode.com');
        }
        else if (providerVal === 'postcodeanywhere') {
          $('#server').parent().parent().show();
          $('#api_key').parent().parent().show();
          $('#username').parent().parent().show();
          $('#server').val('http://services.postcodeanywhere.co.uk');
        }
        else if (providerVal === 'getaddressio') {
          $('#server').parent().parent().show();
          $('#api_key').parent().parent().show();
          $('#server').val('https://api.getAddress.io');
        }
      }
    });
  </script>
{/literal}


