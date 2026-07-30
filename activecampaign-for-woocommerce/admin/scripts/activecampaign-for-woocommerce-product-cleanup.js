jQuery(document).ready(function ($) {

    $('#activecampaign-run-product-cleanup').click(function (e) {
        if (!$(e.target).hasClass('disabled')) {
            cancelUpdateCheck();
            $('.cleanup-run-status').hide();
            $('#activecampaign-run-product-cleanup-wait').show();
            $('#activecampaign-product-cleanup-run-shortly').show();

            runAjax({
                'action': 'activecampaign_for_woocommerce_schedule_product_cleanup',
                'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_nonce_field').val()
            });

            enableStopButtons();
            $('#activecampaign-cancel-product-cleanup').show();
            startUpdateCheck();
        }
    });

    $('#activecampaign-cancel-product-cleanup').click(function (e) {
        cancelUpdateCheck();
        $('.cleanup-run-status').hide();
        $('#activecampaign-run-product-cleanup-wait').show();
        runAjax({
            'action': 'activecampaign_for_woocommerce_cancel_product_cleanup',
            'type': 1,
            'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_nonce_field').val()
        });
        $('#activecampaign-product-cleanup-stop-requested').show();
        startUpdateCheck(4000);
        disableStopButtons();
    });

    $('#activecampaign-reset-product-cleanup').click(function (e) {
        cancelUpdateCheck();
        $('.cleanup-run-status').hide();
        $('#activecampaign-run-product-cleanup-wait').show();
        runAjax({
            'action': 'activecampaign_for_woocommerce_reset_product_cleanup',
            'type': 2,
            'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_nonce_field').val()
        });
        enableStopButtons();
        startUpdateCheck(10000);
    });

    updateStatus();
    var statInt = setInterval(updateStatus, 2000);

    function startUpdateCheck(degrade = 0) {
        var intv = 2000;

        if (degrade > 0) {
            intv = degrade + intv;
        }

        if (statInt) {
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
                resolve(response.data);
            }).fail(response => {
                startUpdateCheck(4000);
            });
        });
    }

    function disableStopButtons() {
        $('#activecampaign-cancel-product-cleanup').addClass('disabled');
    }

    function enableStopButtons() {
        $('#activecampaign-cancel-product-cleanup').removeClass('disabled');
    }

    function updateStatus() {
        var data = {
            'action': 'activecampaign_for_woocommerce_check_product_cleanup_status'
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data
        }).done(response => {
            $('.cleanup-run-status').hide();

            if (!response || !response.data) {
                return;
            }

            if (response.data.is_scheduled) {
                if (response.data.job_count && response.data.job_count > 0) {
                    $('#activecampaign-product-cleanup-run-shortly span').text(response.data.job_count);
                    $('#activecampaign-product-cleanup-run-shortly').show();
                    enableStopButtons();
                }
                $('#activecampaign-cancel-product-cleanup').show();
            }

            if (response.data.status) {
                var s = response.data.status;
                $('#activecampaign-cancel-product-cleanup').show();

                $('#activecampaign-run-product-cleanup-running').show();
                $('#activecampaign-run-product-cleanup-current-record').show();
                $('#activecampaign-run-product-cleanup-last-update').show();
                $('#activecampaign-run-product-cleanup-cofe-total').show();
                $('#activecampaign-run-product-cleanup-cofe-eligible').show();
                $('#activecampaign-run-product-cleanup-deleted').show();

                $('#activecampaign-run-product-cleanup-current-record span').text(s.current_record);
                $('#activecampaign-run-product-cleanup-cofe-total span').text(s.cofe_total);
                $('#activecampaign-run-product-cleanup-cofe-eligible span').text(s.cofe_eligible);
                $('#activecampaign-run-product-cleanup-deleted span').text(s.deleted_count);
                $('#activecampaign-run-product-cleanup-fails span').text(JSON.stringify(s.failed_id_array));
                $('#activecampaign-run-product-cleanup-started span').text(s.start_time);
                $('#activecampaign-run-product-cleanup-last-update span').text(s.last_update);

                $('#activecampaign-run-product-cleanup-debug').text(JSON.stringify(s));
            }

            if (response.data.last_run) {
                $('#activecampaign-cancel-product-cleanup').show();
                var s = response.data.last_run;
                if ('reset' === s.status_name) {
                    $('#activecampaign-run-product-cleanup-reset').show();
                    $('#activecampaign-cancel-product-cleanup').hide();
                } else {
                    $('.last-cleanup-status').show();
                    $('#activecampaign-run-product-cleanup-finished').show();
                    $('#activecampaign-run-product-cleanup-fails span').text(JSON.stringify(s.failed_id_array));
                    $('#activecampaign-run-product-cleanup-finished span').text(s.end_time);
                    $('#activecampaign-run-product-cleanup-started span').text(s.start_time);
                    $('#activecampaign-run-product-cleanup-deleted span').text(s.deleted_count);
                }

                if (s.is_finished && !response.data.is_scheduled) {
                    disableStopButtons();
                    $('#activecampaign-cancel-product-cleanup').hide();
                }

                $('#activecampaign-run-product-cleanup-debug').text(JSON.stringify(response.data.last_run));
            }

            if (response.data.is_cancelled) {
                $('.cleanup-run-status').hide();
                $('#activecampaign-run-product-cleanup-cancelled').show();
                if (response.data.job_count && response.data.job_count > 0) {
                    $('#activecampaign-product-cleanup-run-shortly').show();
                    $('#activecampaign-product-cleanup-run-shortly span').text(response.data.job_count);
                }
            }
        }).fail(response => {
            console.log('Product cleanup response failed', response);
            startUpdateCheck(6000);
        });
    }
});