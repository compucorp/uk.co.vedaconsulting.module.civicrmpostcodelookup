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
      $('#CIVICRM_QFID_1_import_method').parent().append('<br /> <br /> Please provide PAF file in CSV format received from the Royal Mail');
      hideAllFields();
      showFields();
      $('#provider').change(function() {
        hideAllFields();
        showFields();
      });
      $('input[name="import_method"]').click(function() {
        importMethodToggle();
      });

      $('#paf_file').change(function() {
        var maxUploadFileSize = {/literal} {$maxFileSize} {literal}
        if (this.files[0].size > maxUploadFileSize){
          CRM.alert("This file exceeds the maximum upload size limit for this server", "Maximum Upload Size Limit", 'error');
          this.value = "";
        }
      });

      function hideAllFields() {
        $('#server').parent().parent().hide();
        $('#api_key').parent().parent().hide();
        $('#serial_number').parent().parent().hide();
        $('#username').parent().parent().hide();
        $('#password').parent().parent().hide();
        $('#CIVICRM_QFID_1_import_method').parent().parent().hide();
        $('#paf_file_url').parent().parent().hide();
        $('#paf_file').parent().parent().hide();
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
        else if (providerVal === 'serverupload') {
          $('#CIVICRM_QFID_1_import_method').parent().parent().show();
          var pafFileName = {/literal}"{$pafFileName}"{literal};
          if (pafFileName) {
            $('#paf_file').after('<div>' + pafFileName + ' was the last processed uploaded file! </div>');
          }
          importMethodToggle();
        }
      }

      function importMethodToggle() {
        var importMethod = $('input[name="import_method"]:checked').val();
        if (importMethod === '1') {
          $('#paf_file').parent().parent().show();
          $('#paf_file_url').parent().parent().hide();
        }
        else if(importMethod === '2') {
          $('#paf_file_url').parent().parent().show();
          $('#paf_file').parent().parent().hide()
        }
      }
    });
  </script>
{/literal}


