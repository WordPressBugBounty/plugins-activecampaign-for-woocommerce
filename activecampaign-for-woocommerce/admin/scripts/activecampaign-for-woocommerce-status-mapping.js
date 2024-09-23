document.addEventListener("DOMContentLoaded", function () {
    const $ = window.jQuery;
    const nonceVal = $('#activecampaign_for_woocommerce_settings_nonce_field');

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

    $("#activecampaign-create-mapping-button").click(function(e){
        e.preventDefault();
        console.log('create mapping');

        let data = {};
        data.activecampaign_for_woocommerce_settings_nonce_field = nonceVal.attr('value');
        data.action = 'activecampaign_for_woocommerce_create_status_mapping';

        data.wc_status_key = $('#wc_status_key').find(":selected").val();
        data.ac_status_key = $('#ac_status_key').find(":selected").val();
        data.perform = 'create';

        ajaxCall(data).then((data) => {
            console.log('success', data);
            location.reload();
        })
            .catch((error) => {
                console.log(error);
            });
    });

    $(".activecampaign-delete-mapping-button").click(function(e){
        e.preventDefault();
        console.log('delete mapping');

        let data = {};
        data.activecampaign_for_woocommerce_settings_nonce_field = nonceVal.attr('value');
        data.action = 'activecampaign_for_woocommerce_delete_status_mapping';
        data.wc_status_key = $(this).attr('key');
        data.perform = 'delete';

        ajaxCall(data).then((data) => {
            console.log('success you deleted the mapping', data);
            location.reload();
        })
            .catch((error) => {
                console.log(error);
            });
    });
});