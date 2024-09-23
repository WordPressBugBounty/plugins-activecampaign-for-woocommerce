jQuery(document).ready(function($) {
    var scheduled = false;
    var idle = 0;

    // Check sync status
    var statInt = setInterval(updateStatus, 4000);

    function startUpdateCheck( degrade = 0 ) {
        var intv = 4000;

        if(degrade > 0){
            intv = degrade + intv;
        }

        if(statInt) {
            cancelUpdateCheck();
        }

        statInt = setInterval(updateStatus, intv);
    }

    function cancelUpdateCheck() {
        clearInterval(statInt);
    }

    function runAjax(data) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data
            }).done(response => {
                console.log(response);
                resolve(response.data);
            }).fail(response => {
                console.log(response);
                startUpdateCheck(8000);
            });
        });
    }

    function resetCounts() {
        $('#activecampaign-run-historical-sync-contact-record-num').html('-');
        $('#activecampaign-historical-sync-contacts-count').html('-');

        $('#activecampaign-run-historical-sync-prepared-count').html('-');
        $('#activecampaign-run-historical-sync-pending-count').html('-');
        $('#activecampaign-run-historical-sync-current-record-status').html('-');
        $('#activecampaign-run-historical-sync-synced-count').html('-');
        $('#activecampaign-run-historical-sync-total-count').html('-');
        $('#activecampaign-run-historical-sync-incompatible-count').html('-');
        $('#activecampaign-run-historical-sync-error-count').html('-');

        $('#activecampaign-run-historical-sync-sub-total-count').html('-');
        $('#activecampaign-run-historical-sync-sub-prepared-count').html('-');
        $('#activecampaign-run-historical-sync-sub-pending-count').html('-');
        $('#activecampaign-run-historical-sync-sub-synced-count').html('-');
        $('#activecampaign-run-historical-sync-sub-incompatible-count').html('-');
        $('#activecampaign-run-historical-sync-sub-error-count').html('-');
    }

    function updateStatus() {
        var data = {
            'action': 'activecampaign_for_woocommerce_check_historical_sync_status'
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data
        }).done(response => {
            var run_status = false;

            if(response.data.status !== false) {
                $('#activecampaign-sync-run-section').show();
                run_status = response.data.status;

                if (run_status.stop_status === true){
                    $('#activecampaign-historical-sync-stop-requested').show();
                    $('#activecampaign-continue-historical-sync').removeClass('disabled');
                }else{
                    $('#activecampaign-continue-historical-sync').addClass('disabled');
                }

                // Contact status
                $('#activecampaign-historical-sync-contact-status').show();
                $('#activecampaign-historical-sync-contact-status span').html(run_status.run_sync.contacts);
                $('#activecampaign-historical-sync-contacts-count').html(run_status.contact_total);
                $('#activecampaign-historical-sync-contacts-queue').html(run_status.contact_queue + ' (apx.)');
                $('#activecampaign-run-historical-sync-contact-record-num').html(run_status.contact_count);
                $('#activecampaign-run-historical-sync-contact-failed-num').html(run_status.contact_failed_count);

                // Subscription status
                $('#activecampaign-run-historical-sync-sub-total-count').html('-');
                $('#activecampaign-run-historical-sync-sub-prepared-count').html(run_status.subprepared);
                $('#activecampaign-run-historical-sync-sub-pending-count').html(run_status.subpending);
                $('#activecampaign-run-historical-sync-sub-synced-count').html(run_status.subsynced);
                $('#activecampaign-run-historical-sync-sub-incompatible-count').html(run_status.subincomp);
                $('#activecampaign-run-historical-sync-sub-error-count').html(run_status.suberror);

                // Order status
                $('#activecampaign-historical-sync-order-status').show();
                $('#activecampaign-historical-sync-order-status span').html(run_status.run_sync.orders);
                $('#activecampaign-run-historical-sync-total-count').html(run_status.total_orders);
                $('#activecampaign-run-historical-sync-prepared-count').html(run_status.prepared);
                $('#activecampaign-run-historical-sync-pending-count').html(run_status.pending);
                $('#activecampaign-run-historical-sync-synced-count').html(run_status.synced);
                $('#activecampaign-run-historical-sync-incompatible-count').html(run_status.incompatible);
                $('#activecampaign-run-historical-sync-error-count').html(run_status.error);

                // Show fields
                $('#activecampaign-run-historical-sync-contact-block').show();
                $('#activecampaign-data-contacts').show();
                $('#sync-run-contact-line').show();
                $('#activecampaign-historical-sync-run-data-prep').show();
                $('#activecampaign-historical-sync-data-prep-finished').show();

                // Debug section
                $('#activecampaign-run-historical-sync-current-record-status').html(JSON.stringify(response.data));

                startUpdateCheck();

                if (run_status.stuck) {
                    $('#activecampaign-historical-sync-stuck').show();
                }else{
                    $('#activecampaign-historical-sync-stuck').hide();
                }

                if (!run_status.is_running) {
                    idle++;
                    if(idle === 11){
                        startUpdateCheck(8000);
                    }
                    if(idle === 30){
                        startUpdateCheck(16000);
                    }
                    if(idle > 60) {
                        $('#activecampaign-sync-idle-header').show();

                        startUpdateCheck(30000);
                    }
                }else{
                    idle = 0;
                    startUpdateCheck(4000);
                    $('#activecampaign-historical-sync-order-data').show();
                    $('#activecampaign-data-orders').show();
                }
            }else{
                $('#activecampaign-sync-finished-header').show();
                resetCounts();
            }
        }).fail(response => {
            startUpdateCheck(8000);
            $('#activecampaign-run-historical-sync-status').html('Response failed from ' + ajaxurl);

        });
    }

    updateStatus();

    $('#activecampaign-run-historical-sync').click(function (e) {
        if ( ! $(e.target).hasClass('disabled')) {
            $('#activecampaign-historical-sync-run-shortly').show();
            resetCounts();
            var syncContacts = 0;

            if($('#activecampaign-historical-sync-contacts:checked').val() == 1) {
                syncContacts = 1;
            }

            var action = 'activecampaign_for_woocommerce_schedule_bulk_historical_sync';

            runAjax({
                'action': action,
                'syncContacts': syncContacts,
                'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_settings_nonce_field').val()
            });
            scheduled = true;
            startUpdateCheck();
        }
    });

    $('#activecampaign-cancel-historical-sync').click(function (e) {
        console.log('cancel');
        runAjax({
            'action': 'activecampaign_for_woocommerce_cancel_historical_sync',
            'type': 1,
            'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_settings_nonce_field').val()
        });
        $('#activecampaign-historical-sync-stop-requested').show();
    });

    $('#activecampaign-continue-historical-sync').click(function (e) {
        console.log('continue');
        runAjax({
            'action': 'activecampaign_for_woocommerce_cancel_historical_sync',
            'type': 2,
            'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_settings_nonce_field').val()
        });
        $('#activecampaign-historical-sync-stop-requested').hide();
    });

    $('#activecampaign-reset-historical-sync').click(function (e) {
        console.log('reset');
        runAjax({
            'action': 'activecampaign_for_woocommerce_reset_historical_sync',
            'type': 2,
            'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_settings_nonce_field').val()
        });
        setTimeout(function(){
            updateStatus();
        }, 500);
    });
});
