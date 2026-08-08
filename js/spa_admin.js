jQuery(function($) {
    $(document).on(
        'click',
        '.spa-tab-button',
        function() {

            let button = $(this);

            let tabId =
                button.data('tab');

            button
                .closest('.spa-volunteer-tabs')
                .find('.spa-tab-button')
                .removeClass('active');

            button.addClass('active');

            button
                .closest('.spa-volunteer-tabs')
                .find('.spa-tab-panel')
                .removeClass('active');

            $('#' + tabId)
                .addClass('active');

        }
    );
    $('#spa-dashboard-widgets').sortable({

        items: '.spa-dashboard-card',

        stop: function() {

            let order = [];

            $('#spa-dashboard-widgets .spa-dashboard-card')
                .each(function() {

                    order.push(
                        $(this).data('card')
                    );

                });
            $.post(
                spaAdmin.ajaxUrl,
                {
                    action: 'spa_save_dashboard_order',
                    order: order,
                    nonce: spaAdmin.nonce
                },
                function(response) {
                    console.log(response);
                }
            );

        }

    });
    $(document).on(
        'click',
        '.spa-event-link',
        function(e) {

            e.preventDefault();

            let eventId =
                $(this).data('event-id');

            $('.spa-event-selected')
                .removeClass(
                    'spa-event-selected'
                );

            $(this)
                .closest('tr')
                .addClass(
                    'spa-event-selected'
                );

            $.post(
                spaAdmin.ajaxUrl,
                {
                    action: 'spa_load_event',
                    event_id: eventId,
                    nonce: spaAdmin.nonce
                },
                function(response) {

                    if (response.success) {

                        $('#spa-event-details-container')
                            .html(
                                response.data.details
                            );

                        $('#spa-event-volunteers-container')
                            .html(
                                response.data.volunteers
                            );

                    }

                }
            );

        }
    );

    $(document).on(
        'click',
        '.spa-page-button',
        function() {

            let page =
                $(this).data('page');

            $.post(
                spaAdmin.ajaxUrl,
                {
                    action: 'spa_load_events_page',
                    page: page,
                    nonce: spaAdmin.nonce
                },
                function(response) {

                    if (response.success) {

                        $('#spa-events-list-container')
                            .html(
                                response.data.html
                            );

                    }

                }
            );

        }
    );

    $(document).on(
        'change',
        '.spa-volunteer-checkbox',
        function() {

            let checkbox = $(this);

            $.post(
                spaAdmin.ajaxUrl,
                {
                    action: 'spa_toggle_volunteer',
                    event_id: checkbox.data('event-id'),
                    team_id: checkbox.data('team-id'),
                    volunteer_id: checkbox.data('volunteer-id'),
                    assigned: checkbox.is(':checked') ? 1 : 0,
                    nonce: spaAdmin.nonce
                },
                function(response) {

                if (response.success) {

                    let teamCard = checkbox.closest('.spa-team-card');

                    let countElement =
                        teamCard.find('.spa-assigned-count');

                    let assignedCount =
                        teamCard.find(
                            '.spa-volunteer-checkbox:checked'
                        ).length;

                    countElement.text(assignedCount);

                    let countContainer =
                        teamCard.find('.spa-team-count');

                    let needed = parseInt(
                        countContainer.data('needed')
                    );

                    let checkmark =
                        teamCard.find('.spa-team-complete');

                    countContainer.removeClass(
                        'spa-team-full spa-team-short'
                    );

                    if (assignedCount >= needed) {

                        countContainer.addClass(
                            'spa-team-full'
                        );

                        checkmark.html('&#10003;');

                    } else {

                        countContainer.addClass(
                            'spa-team-short'
                        );

                        checkmark.empty();

                    }

                    teamCard.find(
                        '.spa-volunteer-checkbox'
                    ).each(function() {

                        let currentCheckbox = $(this);

                        if (
                            assignedCount >= needed &&
                            !currentCheckbox.is(':checked')
                        ) {

                            currentCheckbox.prop(
                                'disabled',
                                true
                            );

                        } else {

                            currentCheckbox.prop(
                                'disabled',
                                false
                            );

                        }

                    });

                }

                }
            );

        }
    );

    $(document).on('input', '#spa-uninstall-confirm', function() {
        var val = $(this).val();
        var $btn = $('.spa-danger-zone').find('.spa-uninstall-btn');
        var slug = $btn.data('slug') || 'stpauls-admin';
        if (val === slug) {
            $btn.removeClass('disabled').prop('disabled', false).attr('aria-disabled', 'false').css({'pointer-events':'auto','opacity':'1'});
        } else {
            $btn.addClass('disabled').prop('disabled', true).attr('aria-disabled', 'true').css({'pointer-events':'none','opacity':'.6'});
        }
    });

    $(document).on('click', '.spa-uninstall-btn', function(e) {
        var $btn = $(this);
        if ($btn.hasClass('disabled') || $btn.prop('disabled')) {
            e.preventDefault();
            alert('Please type ' + ($btn.data('slug') || 'stpauls-admin') + ' to enable uninstall.');
            return;
        }
        // proceed to plugins page (default anchor behavior)
    });

    // Email provider fields show/hide
    function spa_toggle_email_provider() {
        var selected = $('#spa_email_provider').val();
        $('.spa-provider').hide();
        $('.spa-provider[data-provider="' + selected + '"]').show();
    }

    $(document).ready(function() {
        spa_toggle_email_provider();
    });

    $(document).on('change', '#spa_email_provider', function() {
        spa_toggle_email_provider();
    });

    // AJAX Send Test Email (shows result inline under the button)
    $(document).on('click', '#spa-send-test-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $form = $btn.closest('form');
        var dataArray = $form.serializeArray();
        // add action and nonce for admin-ajax
        dataArray.push({ name: 'action', value: 'spa_send_test_email' });
        dataArray.push({ name: 'nonce', value: spaAdmin.nonce });

        $btn.prop('disabled', true).text('Sending...');
        var $result = $('#spa-test-result');
        $result.removeClass().text('');

        $.post(spaAdmin.ajaxUrl, dataArray, function(response) {
            if ( response && response.success ) {
                $result.addClass('spa-test-success').text('Test email sent successfully.');
            } else {
                var msg = response && response.data ? response.data : 'Unknown error';
                if ( msg === 'missing_recipient' ) msg = 'Recipient email missing.';
                $result.addClass('spa-test-error').text('Error: ' + msg);
            }
        }).fail(function(jqXHR) {
            $result.addClass('spa-test-error').text('AJAX error: ' + (jqXHR.statusText || 'request failed'));
        }).always(function() {
            $btn.prop('disabled', false).text('Send Test Email');
        });
    });

    // SMS provider toggle and AJAX test send
    function spa_toggle_sms_provider() {
var selected = $('#spa_sms_provider').val();
$('.spa-sms-provider').hide();
$('.spa-sms-provider[data-provider="' + selected + '"]').show();
    }

    $(document).ready(function() {
spa_toggle_sms_provider();
    });

    $(document).on('change', '#spa_sms_provider', function() {
spa_toggle_sms_provider();
    });

    $(document).on('click', '#spa-send-test-sms-btn', function(e) {
e.preventDefault();
var $btn = $(this);
var $form = $btn.closest('form');
var dataArray = $form.serializeArray();
dataArray.push({ name: 'action', value: 'spa_send_test_sms' });
dataArray.push({ name: 'nonce', value: spaAdmin.nonce });

$btn.prop('disabled', true).text('Sending...');
var $result = $('#spa-test-sms-result');
$result.removeClass().text('');

$.post(spaAdmin.ajaxUrl, dataArray, function(response) {
    if ( response && response.success ) {
        $result.addClass('spa-test-success').text('Test SMS sent successfully.');
    } else {
        var msg = response && response.data ? response.data : 'Unknown error';
        if ( msg === 'missing_recipient' ) msg = 'Recipient phone missing.';
        $result.addClass('spa-test-error').text('Error: ' + msg);
    }
}).fail(function(jqXHR) {
    $result.addClass('spa-test-error').text('AJAX error: ' + (jqXHR.statusText || 'request failed'));
}).always(function() {
    $btn.prop('disabled', false).text('Send Test SMS');
});
    });

});
