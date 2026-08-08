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
        spa_toggle_push_provider();
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

    // Push provider toggle
    function spa_toggle_push_provider() {
        var selected = $('#spa_push_provider').val();
        $('.spa-push-provider').hide();
        $('.spa-push-provider[data-provider="' + selected + '"]').show();
    }

    $(document).on('change', '#spa_push_provider', function() {
        spa_toggle_push_provider();
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

    // update inline example when default country changes
    $(document).on('change', '#spa_sms_default_country', function() {
var ex = $(this).find('option:selected').data('example') || '';
$('#spa-sms-example').text(ex);
    });
    // set initial example on load
    $(document).ready(function() {
var ex0 = $('#spa_sms_default_country').find('option:selected').data('example') || '';
$('#spa-sms-example').text(ex0);
    });

    $(document).on('click', '#spa-send-test-sms-btn', function(e) {
e.preventDefault();
var $btn = $(this);
var $form = $btn.closest('form');
var $to = $('#spa_test_sms_recipient').val().trim();
if ( $to === '' ) {
    $('#spa-test-sms-result').removeClass().addClass('spa-test-error').text('Please enter a recipient phone number.');
    return;
}
// Strip most special characters
var cleaned = $to.replace(/[^\d+]/g, '');
// If no leading +, try to prepend default country dial code
if ( cleaned.charAt(0) !== '+' ) {
    var selectedOpt = $('#spa_sms_default_country option:selected');
    var dial = selectedOpt.data('dial') || null;
    if ( dial ) {
        // remove leading zeros from local number
        cleaned = cleaned.replace(/^0+/, '');
        cleaned = '+' + dial + cleaned;
    } else {
        // if no dial code available, ensure digits only
        cleaned = cleaned;
    }
}
// Validate E.164 when provider requires it
var provider = $('#spa_sms_provider').val();
var requireE164 = ['twilio','vonage','plivo','messagebird','textmagic'];
var e164re = /^\+[1-9]\d{7,14}$/;
if ( requireE164.indexOf(provider) !== -1 && ! e164re.test(cleaned) ) {
    $('#spa-test-sms-result').removeClass().addClass('spa-test-error').text('Phone must be E.164 format (e.g. +15551234567) for the selected provider.');
    return;
}

// Set normalized value back into the input so server receives the same
$('#spa_test_sms_recipient').val(cleaned);

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
        // handle prefixed invalid_phone_format message
        if ( typeof msg === 'string' && msg.indexOf('invalid_phone_format:') === 0 ) {
            msg = msg.replace('invalid_phone_format:', '');
        }
        $result.addClass('spa-test-error').text('Error: ' + msg);
    }
}).fail(function(jqXHR) {
    $result.addClass('spa-test-error').text('AJAX error: ' + (jqXHR.statusText || 'request failed'));
}).always(function() {
    $btn.prop('disabled', false).text('Send Test SMS');
});
    });

    // Delete secret button handler
    $(document).on('click', '.spa-delete-secret-btn', function(e) {
       e.preventDefault();
       var $btn = $(this);
       var field = $btn.data('field');
       var option = $btn.data('option');
        
       if (!confirm('Are you sure? This will delete the saved credential.')) {
           return;
       }
        
       $btn.prop('disabled', true).text('Deleting...');
        
       $.post(spaAdmin.ajaxUrl, {
           action: 'spa_delete_secret',
           option: option,
           nonce: spaAdmin.nonce
       }, function(response) {
           if (response && response.success) {
               // Reload the settings page to show the input field again
               location.reload();
           } else {
               alert('Error deleting credential: ' + (response && response.data ? response.data : 'unknown error'));
               $btn.prop('disabled', false).text('Delete & Re-enter');
           }
       }).fail(function() {
           alert('AJAX error');
           $btn.prop('disabled', false).text('Delete & Re-enter');
       });
    });

});
