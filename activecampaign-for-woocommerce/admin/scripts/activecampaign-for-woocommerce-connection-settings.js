document.addEventListener("DOMContentLoaded", function () {
    const $ = window.jQuery;
    const modal = $('#activecampaign_connection_modal');
    const nonceVal = $('#activecampaign_for_woocommerce_settings_nonce_field');

    function loadConnectionBlock(){
        let data = {};
        data.activecampaign_for_woocommerce_settings_nonce_field = nonceVal.attr('value');
        data.action = 'activecampaign_for_woocommerce_load_connection_block';

        return new Promise((resolve, reject) => {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data
            }).done(response => {
                if(response) {
                    $("#the-connection-list").html(response);
                    registerTableButtons();
                }
                $('#activecampaign_connection_list .status .status-name').hide();
                resolve(response);
            }).fail(response => {
                reject(response);
            });
        });
    }

    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (err) {
            return false;
        }
    }

    function registerTableButtons() {
        $('.activecampaign-for-woocommerce-edit-connection-button').click(function (e) {
            e.preventDefault();
            var cData = $(e.target).closest('tr').attr('data-connection');
            var pcData = JSON.parse(cData);

            clearConnectionForm();

            $('#activecampaign_connection_modal #connection_id').val(pcData.id);
            $('#activecampaign_connection_modal #connection_external_id').val(pcData.externalid);
            $('#activecampaign_connection_modal #connection_integration_name').val(pcData.name);
            $('#activecampaign_connection_modal #connection_integration_link').val(pcData.linkUrl);
            $('#activecampaign_connection_modal').show();
            $('#activecampaign-send-create-connection-button').hide();
            $('#activecampaign-send-update-connection-button').show();
        });

        $(".activecampaign-for-woocommerce-select-connection-button").click(function (e) {
            e.preventDefault();

            var cData = $(e.target).closest('tr').attr('data-connection');
            var pcData = JSON.parse(cData);

            let data = {};
            data.activecampaign_for_woocommerce_settings_nonce_field = nonceVal.attr('value');
            data.action = 'activecampaign_for_woocommerce_select_connection';

            data.connection_id = pcData.id;
            data.connection_external_id = pcData.externalid;
            data.connection_integration_name = pcData.name;
            data.connection_integration_logo = pcData.logoUrl;
            data.connection_integration_link = pcData.linkUrl;

            rowStatusProcess(pcData.id, 0);
            ajaxCall(data).then((data) => {
                loadConnectionBlock().then(function(data){
                    rowStatusSuccess(pcData.id, 'Connection selected.');
                });

            })
                .catch((error) => {
                    rowStatusError(pcData.id, error);
                });
        });

        $(".activecampaign-for-woocommerce-delete-connection-button").click(function (e) {
            e.preventDefault();

            var cData = $(e.target).closest('tr').attr('data-connection');
            var pcData = JSON.parse(cData);

            if(confirm('Are you sure you want to delete connection '+pcData.id+' '+pcData.externalid+'?')) {
                let data = {};
                data.activecampaign_for_woocommerce_settings_nonce_field = nonceVal.attr('value');
                data.action = 'activecampaign_for_woocommerce_delete_connection';

                data.connection_id = pcData.id;
                data.connection_external_id = pcData.externalid;

                rowStatusProcess(pcData.id, 0);

                ajaxCall(data).then((data) => {
                    rowStatusSuccess(pcData.id,'Connection deleted');
                    loadConnectionBlock();
                })
                    .catch((error) => {
                        rowStatusError(pcData.id, error);
                    });
            }
        });
    }

    function rowStatusSuccess(id, tooltip){
        updateRowStatus(id, 'Success', 'success', tooltip);
        setTimeout(function(){document.location.reload()}, 1000);
    }

    function rowStatusError(id, tooltip){
        updateRowStatus(id, 'Error', 'error', tooltip);
    }

    function rowStatusProcess(id, tooltip){
        updateRowStatus(id, 'In progress', 'progress', tooltip);
    }

    function updateRowStatus(id, htmlText, className, tooltip){
        let target = $('#connection-list-' + id + ' .status');
        let tooltipText = '<span class="tooltiptext">'+tooltip+'</span>';
        let statusTarget = target.find('.status-name ');

        if(!tooltip){
            statusTarget.html(htmlText);
        }else{
            statusTarget.html(htmlText + tooltipText);
        }

        target.removeClass().addClass('status');
        target.addClass(className);
        statusTarget.show();
    }

    function closeModalWindow(){
        $('#activecampaign_connection_modal').hide();
    }

    function clearConnectionForm(){
        $('#activecampaign_connection_modal #connection_id').val('');
        $('#activecampaign_connection_modal #connection_external_id').val('');
        $('#activecampaign_connection_modal #connection_integration_name').val('');
        $('#activecampaign_connection_modal #connection_integration_link').val('');
    }

    function showStatus(type, message){
        if (type === 'error'){
            $('#activecampaign_connection_list .notice').removeClass('notice-success').addClass('notice-error').html(message).show();
            setTimeout(function(){$('#activecampaign_connection_list .notice').hide();}, 50000);
        }else if(type ==='success'){
            $('#activecampaign_connection_list .notice').removeClass('notice-error').addClass('notice-success').html(message).show();
            setTimeout(function(){$('#activecampaign_connection_list .notice').hide();}, 10000);
        }
    }

    function ajaxCall(data){
        return new Promise((resolve, reject) => {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function (result) {
                    if (result.success === true) {
                        var data = result.data;
                        resolve(result.data);
                        return true;
                    } else {
                        // error
                        showStatus('error', result.data.message);
                        reject(result.data);
                    }
                },
                error: function (responseText, textStatus, errorThrown) {

                    showStatus('error', responseText);
                    reject(responseText);
                    return false;
                }
            });
        });
    }


    $('#activecampaign_connection_modal').click(function(e){
        e.preventDefault();
        let t = $( e.target );
        if(t.is('#activecampaign_connection_modal')){
            closeModalWindow();
        }
    });

    $('#activecampaign-cancel-connection-button').click(function(e){
        e.preventDefault();
        closeModalWindow();
    });

    $('#activecampaign-new-connection-button').click(function(e){
        e.preventDefault();
        clearConnectionForm();
        $('#activecampaign_connection_modal').show();
        $('#activecampaign-send-create-connection-button').show();
        $('#activecampaign-send-update-connection-button').hide();
    });

    $("#activecampaign-send-create-connection-button").click(function(e){
        e.preventDefault();

        let data = {};
        data.activecampaign_for_woocommerce_settings_nonce_field = nonceVal.attr('value');
        data.action = 'activecampaign_for_woocommerce_create_connection';

        data.connection_external_id = modal.find('input[name="connection_external_id"]').val();
        data.connection_integration_name = modal.find('input[name="connection_integration_name"]').val();
        data.connection_integration_logo = modal.find('input[name="connection_integration_logo"]').val();
        data.connection_integration_link = modal.find('input[name="connection_integration_link"]').val();

        if (! isValidUrl(data.connection_external_id)){
            alert('The Site URL is not a valid URL. This field is required. Please correct the URL to create a connection.');
            return false;
        }

        if (data.connection_integration_link !== '' && ! isValidUrl(data.connection_integration_link)){
            alert('The store URL is not a valid URL. Please correct the URL to create a connection.');
            return false;
        }

        let type = 'POST';

        ajaxCall(data).then((data) => {
            showStatus('success', 'New connection created.');
            closeModalWindow();
            loadConnectionBlock();
            location.reload();
        })
        .catch((error) => {
            showStatus('error', error);
        });
    });


    $("#activecampaign-send-update-connection-button").click(function(e){
        e.preventDefault();

        let data = {};
        data.activecampaign_for_woocommerce_settings_nonce_field = nonceVal.attr('value');
        data.action = 'activecampaign_for_woocommerce_update_connection';

        var cID = modal.find('input[name="connection_id"]');
        cID.prop('disabled', false);
        data.connection_id = cID.val();
        let connectionId = data.connection_id;
        cID.prop('disabled', true);

        data.connection_external_id = modal.find('input[name="connection_external_id"]').val();
        data.connection_integration_name = modal.find('input[name="connection_integration_name"]').val();
        data.connection_integration_logo = modal.find('input[name="connection_integration_logo"]').val();
        data.connection_integration_link = modal.find('input[name="connection_integration_link"]').val();

        if (! isValidUrl(data.connection_external_id)){
            rowStatusError(data.connection_id, 'The Site URL is not a valid URL. Please correct the URL to create a connection.');
            return false;
        }

        if (data.connection_integration_link !== '' && ! isValidUrl(data.connection_integration_link)){
            rowStatusError(data.connection_id, 'The store URL is not a valid URL. Please correct the URL to create a connection.');
            return false;
        }

        let type = 'POST';
        rowStatusProcess(connectionId, 0);

        ajaxCall(data)
            .then((thendata) => {
                closeModalWindow();
                loadConnectionBlock().then(function(thendata){
                    rowStatusSuccess(connectionId, 'Connection updated.');
                });
            })
            .catch((error) => {
                rowStatusError(connectionId, error.message);
            });
        });

        loadConnectionBlock();
});