jQuery(document).ready(function($) {
console.log('subscription js loaded');
    function run_sync(e, type){
        var btn = $(e.target).closest('button');

        if (!btn.hasClass('disabled')) {
            var wcOrderId = btn.attr('ref');
            btn.addClass('lds-dual-ring');

            return new Promise((resolve, reject) => {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        'action': 'activecampaign_for_woocommerce_run_single_subscription_sync',
                        'sync_type': type,
                        'wc_order_id': wcOrderId,
                        'nonce': $('#activecampaign_for_woocommerce_settings_nonce_field').val(),
                        'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_settings_nonce_field').val()
                    }
                }).done(response => {
                    btn.removeClass('lds-dual-ring');
                    location.reload();
                    resolve(response.data);
                }).fail(response => {
                    btn.removeClass('lds-dual-ring');
                });
            });
        }
    }

    $('#activecampaign-sync-new-subscription').click(function (e) {
        e.preventDefault();
        run_sync(e, 'new');
    });

    $('#activecampaign-sync-historical-subscription').click(function (e) {
        e.preventDefault();
        run_sync(e, 'historical');
    });

    $('.sync-button').removeClass('disabled');
});