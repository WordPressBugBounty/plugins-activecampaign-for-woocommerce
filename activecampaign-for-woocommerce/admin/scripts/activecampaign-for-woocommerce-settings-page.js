document.addEventListener("DOMContentLoaded", function () {
    const $ = window.jQuery;
    let cobraListOfValidProductIds = [2,3,4,5,6,7,8,9,10];
    let finalUrlList = [];
    // Lading Product Urls from settings. They get Put into first input array
    // must format that correctly
    let compiledProductInputField = document.getElementById('ba_product_url_patterns');
    let primaryProductInputField = document.getElementById('ba_product_url_patterns-1');
    if (primaryProductInputField) {
        finalUrlList = (primaryProductInputField.value != '') ? JSON.parse(primaryProductInputField.value) : [];
    }
    
    for(let i = 0; i < finalUrlList.length;i++) {
        if(i == 0) {
            compiledProductInputField.value = primaryProductInputField.value
            primaryProductInputField.value = finalUrlList[i];
        } else {
            let item_id = cobraListOfValidProductIds.shift();
            createProductUrlInputFields(item_id, true, finalUrlList[i]);
        }
    }
    if($('#ac_emailoption0').attr('checked')){
        $("#custom-email-option-set").hide();
    }
    $("#ac_emailoption0").click(function(e){
        $("#custom-email-option-set").hide();
        $("#custom_email_field").val('billing_email');
    });
    $("#ac_emailoption1").click(function(e){
        $("#custom-email-option-set").show();
    });

    $( "#activecampaign-update-api-button" ).click(function(e) {
        e.preventDefault();
        const form = $('#activecampaign-for-woocommerce-options-form');
        const nonceVal = $('#activecampaign_for_woocommerce_settings_nonce_field');

        let data = {};
        data.api_url = form.find('input[name="api_url"]').val();
        data.api_key = form.find('input[name="api_key"]').val();
        data.activecampaign_for_woocommerce_settings_nonce_field = nonceVal.attr('value');
        data.action = 'api_test';

        let url = $(this).attr("data-value");
        let type = 'POST';

        $.ajax({
            url: url,
            type: type,
            data:data
        }).done(response => {
            if (response.data.notices && response.data.notices.length > 0) {
                alert(response.data.notices[0].message);
            }
        }).fail(response => {
            if (response.responseJSON.data.errors && response.responseJSON.data.errors.length > 0) {
                alert(response.responseJSON.data.errors[0].message);
            }
        });
    });

    $("#activecampaign-run-fix-connection").click(function(e) {
        if (confirm("Please confirm that you would like to reset your connection ID.")) {
            const nonceVal = jQuery('#activecampaign_for_woocommerce_settings_nonce_field');
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: "activecampaign_for_woocommerce_reset_connection_id",
                    activecampaign_for_woocommerce_settings_nonce_field: nonceVal.attr('value')
                }
            }).done(response => {
                jQuery('#activecampaign-run-fix-connection-status').html( response.data );
            }).fail(response => {
                jQuery('#activecampaign-run-fix-connection-status').html( response.data );
            });
        }
    });

    $("#activecampaign-run-resync-plugin-features").click(function(e) {
        const nonceVal = jQuery('#activecampaign_for_woocommerce_settings_nonce_field');

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: "activecampaign_for_woocommerce_resync_features",
                activecampaign_for_woocommerce_settings_nonce_field: nonceVal.attr('value')
            }
        }).done(response => {
            jQuery('#activecampaign-run-clear-plugin-settings-status').html( response.data );
            document.location.reload();
        }).fail(response => {
            jQuery('#activecampaign-run-clear-plugin-settings-status').html( response.data );
        });
    });

    $("#activecampaign-run-clear-plugin-settings").click(function(e) {
        if (confirm("Are you sure you want to erase all settings? This plugin will not function until proper API settings have been set again.")) {
            const nonceVal = jQuery('#activecampaign_for_woocommerce_settings_nonce_field');
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: "activecampaign_for_woocommerce_clear_all_settings",
                    activecampaign_for_woocommerce_settings_nonce_field: nonceVal.attr('value')
                }
            }).done(response => {
                jQuery('#activecampaign-run-clear-plugin-settings-status').html( response.data );
            }).fail(response => {
                jQuery('#activecampaign-run-clear-plugin-settings-status').html( response.data );
            });
        }else{
            return false;
        }
    });

    $("#activecampaign-manual-mode").click(function(e){
        $("#manualsetup").show().addClass('scale-up-top');
    });

    $(".notice-dismiss").click(function(e){
        $("#update-notifications").hide();
    });

    const form = $('#activecampaign-for-woocommerce-options-form');
    
    // https://my-woocommerce-store.shop/**/products/{{sku}}
    function validProductUrlPattern(pattern) {
        let validUrlPatternVariables = ['sku', 'storePrimaryId', 'storeBaseProductId', 'upc'];
        const regexp = /\{\{(.*?)}}/g;

        if(pattern == '') {
            return true;
        }
        // Wildcard validation
        //https://my-woocommerce-store.shop/**/products/**/slug/collection/**/corner
        if(pattern.split('**').length > 3) {
            return false;
        }
        // Variable validations
        // https://my-woocommerce-store.shop/**/products/{{sku}}/{{id}}
        // https://my-woocommerce-store.shop/**/products/{{id}}
        const matches = [...pattern.matchAll(regexp)]
        if (matches.length !== 1) {
            return false;
        } else {
            let variable = matches[0][1]
            if (!validUrlPatternVariables.includes(variable)) {
                return false;
            }
        }
        // Wildcard next To Variable Validation
        // https://example.com/**{{sku}}
        if(pattern.includes('**{{') || pattern.includes('}}**')) {
            return false;
        }
        
        return true;
    }

    $("#ac-update-settings").click(function(e){
        form.submit();
    });

    function removeAdditionalProductInput(ev) {
        let button = ev.target;
        let div = button.parentElement;
        let oldValue = div.firstChild.value;
        let usable_id = div.firstChild.getAttribute('id').split('-')[1];
        let baProductField = document.getElementById('ba_product_url_patterns');
        let indexofRemoval = finalUrlList.indexOf(oldValue);

        // Remove pattern from final result and add that id to usable list
        finalUrlList.splice(indexofRemoval,1);
        if(finalUrlList.length == 0) {
            baProductField.value = '';
        } else {
            baProductField.value = JSON.stringify(finalUrlList);
        }
        cobraListOfValidProductIds.push(usable_id);

        div.remove();
    }
    function updateMainProductUrlFormField(ev) {
        let patternUrlInputFieldId = ev.currentTarget.getAttribute('id').split('-')[1];
        let patternUrlInputField = document.getElementById('ba_product_url_patterns-'+patternUrlInputFieldId);
        let removalButtonField = document.getElementById('ba_product_url_patterns_rmv-'+patternUrlInputFieldId);
        let pattern = patternUrlInputField.value;
        let baProductField = document.getElementById('ba_product_url_patterns');
        let showSaveToolTip = document.getElementById('ba_product_url_save_tooltip');
        
        if(!finalUrlList.includes(pattern)) {
            if (validProductUrlPattern(pattern) && pattern != '') {
                finalUrlList.push(pattern);
                baProductField.value = JSON.stringify(finalUrlList);
                removalButtonField.style.display = 'block'
                showSaveToolTip.style.display = 'none'
                patternUrlInputField.style.border="";
            } else {
                patternUrlInputField.style.border="2px solid red";
            }
        }
    }

    $('#validate_ba_product_url-1').click(function(e){
        updateMainProductUrlFormField(e);
    });
    $('#ba_product_url_patterns-1').on('change', function(e) {
        showSaveToolTip(e);
    });
    $('#ba_product_url_patterns_rmv-1').click(function(e){
        let baProductField = document.getElementById('ba_product_url_patterns');
        let productPatternInputs = [];
        let allInputs = document.getElementsByTagName("input");

        for(let i = 0; i < allInputs.length; i++) {
            if(allInputs[i].id.indexOf('ba_product_url_patterns-') == 0) {
                productPatternInputs.push(allInputs[i]);
            }
        }

        productPatternInputs.forEach((productPatternInput) => {
            let freeId = productPatternInput.getAttribute('id').split('-')[1]

            if (freeId == 1) {
                productPatternInput.value = ''
            } else {
                productPatternInput.parentElement.remove();   
            }
            cobraListOfValidProductIds.push(freeId);
        });
        finalUrlList = [];
        baProductField.value = '';
    });
    function showSaveToolTip(ev) {
        let showSaveToolTip = document.getElementById('ba_product_url_save_tooltip');
        let possiblePattern = ev.target.value
        if (!finalUrlList.includes(possiblePattern)) {
            showSaveToolTip.style.display = 'block'
        }
    }
    function createProductUrlInputFields(id, showRemove, inputValue = '') {
        let removeDisplayValue = showRemove ? 'block' : 'none';
        // Product Url Input
        let input = document.createElement('input');
        input.type = "text";
        input.setAttribute('id', 'ba_product_url_patterns-' + id);
        input.setAttribute('class', 'ba_product_url_inputs');
        input.setAttribute('size', 23);
        input.onchange = function(e) {
            showSaveToolTip(e);
        };
        if(inputValue) {
            input.value = inputValue;
        }
        // Product Url Validate Button
        let validator = document.createElement('button');
        validator.setAttribute('id', 'validate_ba_product_url-' + id);
        validator.setAttribute('class', 'activecampaign-for-woocommerce button validation');
        validator.setAttribute("type", "button");
        validator.innerHTML = "Validate Url";
        validator.onclick = function(e) {
            updateMainProductUrlFormField(e);
        };
        // Product Url Input Removal Button
        let remove = document.createElement('button');
        remove.setAttribute('id', 'ba_product_url_patterns_rmv-' + id);
        remove.setAttribute('class', 'activecampaign-for-woocommerce button removal');
        remove.setAttribute("type", "button");
        remove.style.display = removeDisplayValue;
        remove.innerHTML = "Remove Product Url";
        remove.onclick = function(e) {
            removeAdditionalProductInput(e);
        };
        // Product Url List
        var reqs = document.getElementById("additional_ba_product_url_patterns_list");
        var listItem = document.createElement('li');
        listItem.appendChild(input);
        listItem.appendChild(validator);
        listItem.appendChild(remove);
        reqs.appendChild(listItem);
    }
    $("#ac-add-ba_product_url").click(function(e){ 
        if (cobraListOfValidProductIds.length > 0) {
            let item_id = cobraListOfValidProductIds.shift();
            createProductUrlInputFields(item_id, false);
        }
    });

    form.submit(function(e) {
        let url = form.attr("action");
        let type = form.attr("method");
        let data = form.serialize();
        $('.update-notice').remove();
        e.preventDefault();
        $.ajax({
            url,
            type,
            data
        }).done(response => {
            $("#update-notifications").append('<div class="update-notice notice-success notice is-dismissible"><p>Settings saved</p><button id="my-dismiss-admin-message" class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
            window.location.search += '&manual_setup=1';
        }).fail(response => {
            if (response.responseJSON.data.errors && response.responseJSON.data.errors.length > 0) {
                let errors = response.responseJSON.data.errors;
                $.each(errors, function( key, error) {
                    $("#update-notifications").append('<div class="update-notice error notice is-dismissible"><p>' + error.message + '</p><button id="my-dismiss-admin-message" class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
                });
            }
        });
    });
});