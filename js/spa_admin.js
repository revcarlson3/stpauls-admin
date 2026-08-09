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
        handle: '.spa-card-drag-handle',
        placeholder: 'spa-dashboard-card ui-sortable-placeholder',
        stop: function() {
            let order = [];
            $('#spa-dashboard-widgets .spa-dashboard-card').each(function() {
                order.push($(this).data('card'));
            });
            $.post(spaAdmin.ajaxUrl, {
                action: 'spa_save_dashboard_order',
                order: order,
                nonce: spaAdmin.nonce
            });
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
                        $('#spa-event-notify-volunteers').prop('checked', false);

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

    // Event Modal - Open Add Event modal
    $(document).on('click', '#spa-add-event-button', function() {
       $('#spa-event-modal-title').text('Add Event');
       $('#spa-event-modal-id').val('');
       $('#spa-event-modal-name').val('');
       $('#spa-event-modal-location').val('');
       $('#spa-event-modal-description').val('');
       $('#spa-event-modal-date').val('');
       $('#spa-event-modal-start-time').val('');
       $('#spa-event-modal-end-time').val('');
       $('#spa-event-modal-recurring').prop('checked', false);
       $('#spa-event-modal-notify-volunteers').prop('checked', false);
       $('#spa-event-modal-recurrence-type').val('');
       $('#spa-event-modal-recurrence-end').val('');
       $('#spa-event-modal-status').empty();
       $('#spa-event-modal').show();
    });

    // Close modal - X button
    $(document).on('click', '#spa-event-modal-close, #spa-event-modal-cancel', function() {
       $('#spa-event-modal').hide();
    });

    // Close modal - overlay click
    $(document).on('click', '.spa-modal-overlay', function(e) {
       if (e.target === this) {
           $('#spa-event-modal').hide();
       }
    });

    // Save event - modal submit
    $(document).on('click', '#spa-event-modal-save', function(e) {
       e.preventDefault();
       var $btn = $(this);
       var $status = $('#spa-event-modal-status');
        
       var eventData = {
           action: 'spa_save_event_modal',
           nonce: spaAdmin.nonce,
           event_id: $('#spa-event-modal-id').val(),
           name: $('#spa-event-modal-name').val(),
           location: $('#spa-event-modal-location').val(),
           description: $('#spa-event-modal-description').val(),
           event_date: $('#spa-event-modal-date').val(),
           start_time: $('#spa-event-modal-start-time').val(),
           end_time: $('#spa-event-modal-end-time').val(),
           is_recurring: $('#spa-event-modal-recurring').is(':checked') ? 1 : 0,
           notify_volunteers: $('#spa-event-modal-notify-volunteers').is(':checked') ? 1 : 0,
           recurrence_type: $('#spa-event-modal-recurrence-type').val(),
           recurrence_end_date: $('#spa-event-modal-recurrence-end').val()
       };

       $btn.prop('disabled', true).text('Saving...');
       $status.empty();

       $.post(spaAdmin.ajaxUrl, eventData, function(response) {
           if (response && response.success) {
               $status.html('<div class="notice notice-success"><p>Event saved successfully!</p></div>');
               setTimeout(function() {
                   $('#spa-event-modal').hide();
                   // Reload events list
                   var page = 1;
                   $.post(spaAdmin.ajaxUrl, {
                       action: 'spa_load_events_page',
                       nonce: spaAdmin.nonce,
                       page: page
                   }, function(data) {
                       if (data.success) {
                           $('#spa-events-list-container').html(data.data.html);
                       }
                   });
               }, 1000);
           } else {
               var msg = response && response.data ? response.data : 'Unknown error';
               $status.html('<div class="notice notice-error"><p>Error: ' + msg + '</p></div>');
           }
       }).fail(function() {
           $status.html('<div class="notice notice-error"><p>AJAX error</p></div>');
       }).always(function() {
           $btn.prop('disabled', false).text('Save Event');
       });
    });

    // Event details panel - toggle volunteers-needed opacity when team checkbox changes
    $(document).on('change', '.spa-event-team-check', function() {
       var $row = $(this).closest('.spa-event-team-row');
       var $needed = $row.find('.spa-event-team-needed');
       $needed.css('opacity', this.checked ? '1' : '0.4');
    });

    // Event details panel - Save Event button
    $(document).on('click', '#spa-save-event-details-btn', function() {
       var $btn = $(this);
       var $status = $('#spa-save-status');

       // Collect team assignments: only checked teams
       var teams = {};
       $('.spa-event-team-check:checked').each(function() {
           var teamId = $(this).data('team-id');
           var needed = $(this).closest('.spa-event-team-row').find('.spa-event-team-needed').val() || 1;
           teams[teamId] = needed;
       });

       var data = {
           action: 'spa_save_event_details',
           nonce: spaAdmin.nonce,
           event_id: $('#spa-event-id').val(),
           name: $('#spa-event-name').val(),
           location: $('#spa-event-location').val(),
           description: $('#spa-event-description').val(),
           event_date: $('#spa-event-date').val(),
           start_time: $('#spa-event-start-time').val(),
           end_time: $('#spa-event-end-time').val(),
           is_recurring: $('#spa-event-recurring').is(':checked') ? 1 : 0,
           notify_volunteers: $('#spa-event-notify-volunteers').is(':checked') ? 1 : 0,
           recurrence_type: $('#spa-event-recurrence-type').val(),
           recurrence_end_date: $('#spa-event-recurrence-end').val(),
           teams: teams
       };

       $btn.prop('disabled', true).text('Saving...');
       $status.empty();

       $.post(spaAdmin.ajaxUrl, data, function(response) {
           if (response && response.success) {
               $status.html('<div class="notice notice-success inline"><p>Saved successfully.</p></div>');
               setTimeout(function() { $status.empty(); }, 3000);
           } else {
               var msg = response && response.data ? response.data.message : 'Unknown error';
               $status.html('<div class="notice notice-error inline"><p>Error: ' + msg + '</p></div>');
           }
       }).fail(function() {
           $status.html('<div class="notice notice-error inline"><p>AJAX error. Please try again.</p></div>');
       }).always(function() {
           $btn.prop('disabled', false).text('Save Event');
       });
    });

    /* ── Notification Templates ── */

    function spaTemplateGetBody(type) {
       if (type === 'email') {
           if (typeof tinymce !== 'undefined' && tinymce.get('spa_template_body_email')) {
               return tinymce.get('spa_template_body_email').getContent();
           }
           return $('#spa_template_body_email').val();
       }
       return $('#spa-template-body-sms').val();
    }

    function spaTemplateSetBody(type, content) {
       if (type === 'email') {
           if (typeof tinymce !== 'undefined' && tinymce.get('spa_template_body_email')) {
               tinymce.get('spa_template_body_email').setContent(content);
           } else {
               $('#spa_template_body_email').val(content);
           }
       } else {
           $('#spa-template-body-sms').val(content);
           spaUpdateSmsWordCount();
       }
    }

    function spaTemplateSetType(type) {
       $('#spa-template-type').val(type);
       if (type === 'email') {
           $('#spa-email-editor-wrap').show();
           $('#spa-sms-editor-wrap').hide();
           $('#spa-template-subject-row').show();
       } else {
           $('#spa-email-editor-wrap').hide();
           $('#spa-sms-editor-wrap').show();
           $('#spa-template-subject-row').hide();
       }
    }

    function spaTemplateShowForm(type, id, name, subject, body) {
       $('#spa-template-placeholder').hide();
       $('#spa-template-form').show();
       $('#spa-template-id').val(id || '');
       $('#spa-template-name').val(name || '');
       $('#spa-template-subject').val(subject || '');
       $('#spa-delete-template-btn').toggle(!!id);
       $('#spa-template-status').text('');
       spaTemplateSetType(type);
       spaTemplateSetBody(type, body || '');
    }

    function spaUpdateSmsWordCount() {
       var words = $('#spa-template-body-sms').val().trim().split(/\s+/).filter(Boolean).length;
       $('#spa-sms-word-count').text(words);
       $('#spa-sms-word-count').css('color', words > 75 ? '#dc3545' : '#666');
    }

    $(document).on('input', '#spa-template-body-sms', spaUpdateSmsWordCount);

    // New template buttons
    $(document).on('click', '#spa-new-email-template', function() {
       spaTemplateShowForm('email', '', '', '', '');
    });
    $(document).on('click', '#spa-new-sms-template', function() {
       spaTemplateShowForm('sms', '', '', '', '');
    });

    // Load existing template
    $(document).on('click', '.spa-load-template', function(e) {
       e.preventDefault();
       var id = $(this).data('id');
       $.post(spaAdmin.ajaxUrl, {
           action: 'spa_load_template',
           nonce: spaAdmin.nonce,
           template_id: id
       }, function(response) {
           if (response.success) {
               var t = response.data;
               spaTemplateShowForm(t.type, t.id, t.name, t.subject, t.body);
           }
       });
    });

    // Insert smart tag
    $(document).on('click', '#spa-insert-tag', function() {
       var tag = $('#spa-tag-picker').val();
       if (!tag) return;
       var type = $('#spa-template-type').val();
       if (type === 'email') {
           if (typeof tinymce !== 'undefined' && tinymce.get('spa_template_body_email')) {
               tinymce.get('spa_template_body_email').insertContent(tag);
           } else {
               var $ta = $('#spa_template_body_email');
               var pos = $ta[0].selectionStart;
               var val = $ta.val();
               $ta.val(val.slice(0, pos) + tag + val.slice(pos));
           }
       } else {
           var $sms = $('#spa-template-body-sms');
           var pos = $sms[0].selectionStart;
           var val = $sms.val();
           $sms.val(val.slice(0, pos) + tag + val.slice(pos));
           spaUpdateSmsWordCount();
       }
       $('#spa-tag-picker').val('');
    });

    // Save template
    $(document).on('click', '#spa-save-template-btn', function() {
       var $btn = $(this);
       var type = $('#spa-template-type').val();
       var body = spaTemplateGetBody(type);
       var words = body.replace(/<[^>]+>/g, '').trim().split(/\s+/).filter(Boolean).length;

       if (type === 'sms' && words > 75) {
           $('#spa-template-status').text('SMS is over 75 words. Please shorten it.').css('color','#dc3545');
           return;
       }

       $btn.prop('disabled', true).text('Saving...');
       $('#spa-template-status').text('');

       $.post(spaAdmin.ajaxUrl, {
           action: 'spa_save_template',
           nonce: spaAdmin.nonce,
           template_id: $('#spa-template-id').val(),
           template_name: $('#spa-template-name').val(),
           template_type: type,
           template_subject: $('#spa-template-subject').val(),
           template_body: body
       }, function(response) {
           if (response.success) {
               $('#spa-template-id').val(response.data.id);
               $('#spa-delete-template-btn').show();
               $('#spa-template-status').text('Saved!').css('color','green');
               // Refresh the sidebar list
               spaRefreshTemplateLists();
               setTimeout(function() { $('#spa-template-status').text(''); }, 3000);
           } else {
               $('#spa-template-status').text('Error: ' + (response.data ? response.data.message : 'Unknown')).css('color','#dc3545');
           }
       }).always(function() {
           $btn.prop('disabled', false).text('Save Template');
       });
    });

    // Delete template
    $(document).on('click', '#spa-delete-template-btn', function() {
       if (!confirm('Delete this template? This cannot be undone.')) return;
       var id = $('#spa-template-id').val();
       $.post(spaAdmin.ajaxUrl, {
           action: 'spa_delete_template',
           nonce: spaAdmin.nonce,
           template_id: id
       }, function(response) {
           if (response.success) {
               $('#spa-template-form').hide();
               $('#spa-template-placeholder').show();
               spaRefreshTemplateLists();
           }
       });
    });

    function spaRefreshTemplateLists() {
       $.post(spaAdmin.ajaxUrl, {
           action: 'spa_get_template_list',
           nonce: spaAdmin.nonce
       }, function(response) {
           if (!response.success) return;
           var email = '', sms = '';
           $.each(response.data.email, function(i, t) {
               email += '<li style="margin-bottom:4px;"><a href="#" class="spa-load-template" data-id="' + t.id + '" style="text-decoration:none;">📧 ' + $('<span>').text(t.name).html() + '</a></li>';
           });
           $.each(response.data.sms, function(i, t) {
               sms += '<li style="margin-bottom:4px;"><a href="#" class="spa-load-template" data-id="' + t.id + '" style="text-decoration:none;">💬 ' + $('<span>').text(t.name).html() + '</a></li>';
           });
           $('#spa-email-template-list').html(email || '');
           $('#spa-sms-template-list').html(sms || '');
       });
    }

});
