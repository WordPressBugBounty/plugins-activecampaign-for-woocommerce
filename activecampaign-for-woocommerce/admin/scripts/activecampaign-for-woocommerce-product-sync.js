jQuery(document).ready(function($) {

    $('#activecampaign-run-product-sync').click(function (e) {
        if ( ! $(e.target).hasClass('disabled')) {
            cancelUpdateCheck();
            $('.sync-run-status').hide();
            $('#activecampaign-run-product-wait').show();
            // $('#activecampaign-run-product-sync').addClass('disabled');
            $('#activecampaign-product-sync-run-shortly').show();

            var batchLimit = 20;

            if($('#activecampaign-product-sync-limit').find(":selected").text()) {
                batchLimit = $('#activecampaign-product-sync-limit').find(":selected").text();
            }

            var action = 'activecampaign_for_woocommerce_schedule_product_sync';

            runAjax({
                'action': action,
                'batchLimit': batchLimit,
                'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_nonce_field').val()
            });
            enableStopButtons();
            $('#activecampaign-cancel-product-sync').show();
            startUpdateCheck();
        }
    });

    $('#activecampaign-cancel-product-sync').click(function (e) {
        cancelUpdateCheck();
        $('.sync-run-status').hide();
        $('#activecampaign-run-product-wait').show();
        runAjax({
            'action': 'activecampaign_for_woocommerce_cancel_product_sync',
            'type': 1,
            'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_nonce_field').val()
        });
        $('#activecampaign-product-sync-stop-requested').show();
        startUpdateCheck(4000);
        disableStopButtons();
    });

    $('#activecampaign-reset-product-sync').click(function (e) {
        cancelUpdateCheck();
        $('.sync-run-status').hide();
        $('#activecampaign-run-product-wait').show();
        runAjax({
            'action': 'activecampaign_for_woocommerce_reset_product_sync',
            'type': 2,
            'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_nonce_field').val()
        });
        enableStopButtons();
        startUpdateCheck(10000);
    });

    updateStatus();
    // Check sync status
    var statInt = setInterval(updateStatus, 2000);

    function startUpdateCheck( degrade = 0 ) {
        var intv = 2000;

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
                resolve(response.data);
            }).fail(response => {
                startUpdateCheck(4000);
            });
        });
    }

    function disableStopButtons(){
        $('#activecampaign-cancel-product-sync').addClass('disabled');
        $('#activecampaign-pause-product-sync').addClass('disabled');
    }

    function enableStopButtons(){
        $('#activecampaign-cancel-product-sync').removeClass('disabled');
        $('#activecampaign-pause-product-sync').removeClass('disabled');
    }

    function updateStatus() {
        var batchLimit = 20;

        if($('#activecampaign-product-sync-limit').find(":selected").text()) {
            batchLimit = $('#activecampaign-product-sync-limit').find(":selected").text();
        }

        var data = {
            'action': 'activecampaign_for_woocommerce_check_product_sync_status',
            'batchLimit': batchLimit
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data
        }).done(response => {
            $('.sync-run-status').hide();

            if(response.data.is_scheduled ){
                if(response.data.job_count && response.data.job_count > 0){
                    $('#activecampaign-product-sync-run-shortly span').html(response.data.job_count);
                    $('#activecampaign-product-sync-run-shortly').show();
                    enableStopButtons();
                }
                $('#activecampaign-cancel-product-sync').show();
            }

            if(response.data.status){
                var s = response.data.status;
                $('#activecampaign-cancel-product-sync').show();

                $('#activecampaign-run-product-sync-running').show();
                $('#activecampaign-run-product-sync-current-record').show();
                $('#activecampaign-run-product-sync-last-update').show();
                $('#activecampaign-run-product-sync-start-record').show();

                $('#activecampaign-run-product-sync-current-record span').html(s.current_record);
                $('#activecampaign-run-product-sync-start-record span').html(s.start_record);
                $('#activecampaign-run-product-sync-fails span').html(JSON.stringify(s.failed_id_array));
                $('#activecampaign-run-product-sync-sync-started span').html(s.start_time);
                $('#activecampaign-run-product-sync-sync-last-update span').html(s.last_update);

                $('#activecampaign-run-product-sync-running-status').html(JSON.stringify(s));
                $('#activecampaign-run-product-sync-debug').html(JSON.stringify(s));
            }

            if(response.data.last_sync){
                $('#activecampaign-cancel-product-sync').show();
                var s = response.data.last_sync;
                if('reset' === s.status_name) {
                    $('#activecampaign-run-product-sync-reset').show();
                    $('#activecampaign-cancel-product-sync').hide();
                }else {
                    $('.last-sync-status').show();
                    $('#activecampaign-run-product-sync-finished').show();
                    $('#activecampaign-run-product-sync-fails span').html(JSON.stringify(s.failed_id_array));
                    $('#activecampaign-run-product-sync-finished span').html(s.end_time);
                    $('#activecampaign-run-product-sync-sync-started span').html(s.start_time);
                }

                if(s.is_finished && !response.data.is_scheduled){
                    disableStopButtons();
                    $('#activecampaign-cancel-product-sync').hide();
                }

                $('#activecampaign-run-product-sync-debug').html(JSON.stringify(response.data.last_sync));
            }

            if(response.data.is_cancelled){
                $('.sync-run-status').hide();
                $('#activecampaign-run-product-sync-cancelled').show();
                if(response.data.job_count && response.data.job_count > 0){
                    $('#activecampaign-product-sync-run-shortly').show();
                    $('#activecampaign-product-sync-run-shortly span').html(response.data.job_count);
                }
            }
        }).fail(response => {
            console.log('Product response failed', response);
            startUpdateCheck(6000);
            $('#activecampaign-run-product-sync-status').html('Response failed from ' + ajaxurl);

        });
    }
});
