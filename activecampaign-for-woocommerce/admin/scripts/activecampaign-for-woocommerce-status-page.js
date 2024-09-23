document.getElementById('copyButton').addEventListener('click', function() {
    copyToClipboard('activecampaign_status');
} );

document.getElementById('activecampaign-for-woocommerce-clear-error-log').addEventListener('click', function(e) {
    if (!jQuery(e.target).hasClass('button-disabled')) {
        jQuery.ajax({
            url: ajaxurl,
            type:'POST',
            data: {
                'activecampaign_for_woocommerce_settings_nonce_field': jQuery('#activecampaign_for_woocommerce_settings_nonce_field').val(),
                action: "activecampaign_for_woocommerce_clear_error_log"
            }
        }).done(response => {
            if(!response.success){
                jQuery('#activecampaign-for-woocommerce-clear-error-log-result').html('There was an error attempting to remove the log entries.');
            }else {
                jQuery(e.target).addClass('button-disabled');
                jQuery('.wc_status_table.status_activecampaign_errors tbody').html('<tr><td>' + response.data + '</td></tr>');
            }
        }).fail(response => {
            jQuery(e.target).parent().html('There was an error attempting to remove the log entries.');
        });
    }
});

function copyToClipboard(element_id) {
    let aux = document.createElement("div");
    aux.setAttribute("contentEditable", true);
    aux.innerHTML = document.getElementById(element_id).innerHTML;
    aux.setAttribute("onfocus", "document.execCommand('selectAll',false,null)");
    document.body.prepend(aux);
    aux.focus();
    document.execCommand("copy");
    document.body.removeChild(aux);
    document.getElementById('copyStatus').innerHTML = 'Copied!';
    setTimeout(() => {
        document.getElementById('copyStatus').innerHTML = '';
    }, 5000);
}
