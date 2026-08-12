<?php

add_action('spa_hourly_notification_check', 'spa_run_notification_cron');
add_action('wp_ajax_spa_notify_event_volunteer', 'spa_notify_event_volunteer_ajax');

function spa_notification_should_run_now() {
    if ( intval(get_option('spa_notifications_enabled', 1)) !== 1 ) {
        return false;
    }

    $day = intval(get_option('spa_notification_day_of_week', 0));
    $time = get_option('spa_notification_time', '09:00');
    $timezone = wp_timezone();
    $now = current_datetime();
    $scheduled_time = DateTimeImmutable::createFromFormat('!H:i', $time, $timezone);
    if ( ! $scheduled_time ) {
        return false;
    }

    $scheduled_time = $scheduled_time->setDate(
        intval($now->format('Y')),
        intval($now->format('m')),
        intval($now->format('d'))
    );

    return (intval($now->format('w')) === $day && $now >= $scheduled_time);
}

function spa_get_next_notified_event() {
    global $wpdb;

    return $wpdb->get_row(
        "SELECT * FROM {$wpdb->prefix}spa_events
         WHERE active = 1
           AND notify_volunteers = 1
           AND event_date >= CURDATE()
         ORDER BY event_date ASC, start_time ASC
         LIMIT 1"
    );
}

function spa_get_event_volunteers_for_notification($event_id) {
    return spa_get_notification_recipients_for_event($event_id);
}

function spa_get_event_notification_templates() {
    global $wpdb;

    $email_template_id = intval(get_option('spa_active_email_template', 0));
    $sms_template_id = intval(get_option('spa_active_sms_template', 0));
    $push_enabled = intval(get_option('spa_enable_push', 0)) === 1;

    $email_tpl = intval(get_option('spa_enable_email', 0)) === 1 && $email_template_id
        ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}spa_notification_templates WHERE id = %d", $email_template_id))
        : null;
    $sms_tpl = intval(get_option('spa_enable_sms', 0)) === 1 && $sms_template_id
        ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}spa_notification_templates WHERE id = %d", $sms_template_id))
        : null;

    if ( ! $email_tpl && ! $sms_tpl && ! $push_enabled ) {
        return new WP_Error('no_templates', 'No enabled notification channel has an active template.');
    }

    return array(
        'email' => $email_tpl,
        'sms'   => $sms_tpl,
        'push'  => $push_enabled,
    );
}

function spa_send_event_notification_to_volunteer($event, $volunteer, $templates = null, $reminder_type = 'scheduled') {
    $is_24h_reminder = $reminder_type === '24h';
    if ( ! $is_24h_reminder && $templates === null ) {
        $templates = spa_get_event_notification_templates();
    }
    if ( ! $is_24h_reminder && is_wp_error($templates) ) {
        return $templates;
    }

    $sent = array('email' => 0, 'sms' => 0, 'push' => 0);
    $errors = array();
    if ( $templates['email'] && ! empty($volunteer->email) && intval($volunteer->email_enabled) === 1 ) {
        $email_provider = get_option('spa_email_provider', 'wp_mail');
        $message = spa_build_event_notification_message($event, $volunteer, $templates, 'email', $reminder_type);
        $result = spa_dispatch_notification('email', $event, $volunteer, $message, $email_provider);
        if ( is_wp_error($result) ) {
            $errors[] = 'Email: ' . $result->get_error_message();
        } else {
            $sent['email']++;
        }
    }

    if ( ( $is_24h_reminder || $templates['sms'] ) && ! empty($volunteer->phone) && intval($volunteer->phone_enabled) === 1 ) {
        $sms_provider = get_option('spa_sms_provider', 'twilio');
        $message = spa_build_event_notification_message($event, $volunteer, $templates, 'sms', $reminder_type);
        $result = spa_dispatch_notification('sms', $event, $volunteer, $message, $sms_provider);
        if ( is_wp_error($result) ) {
            $errors[] = 'SMS: ' . $result->get_error_message();
        } else {
            $sent['sms']++;
        }
    }

    if ( intval(get_option('spa_enable_push', 0)) === 1 && ! empty($volunteer->push_external_id) ) {
        $push_provider = get_option('spa_push_provider', 'onesignal');
        $message = spa_build_event_notification_message($event, $volunteer, $templates, 'push', $reminder_type);
        $result = spa_dispatch_notification('push', $event, $volunteer, $message, $push_provider);
        if ( is_wp_error($result) ) {
            $errors[] = 'Push: ' . $result->get_error_message();
        } else {
            $sent['push']++;
        }
    }

    if ( $sent['email'] === 0 && $sent['sms'] === 0 && $sent['push'] === 0 ) {
        $message = ! empty($errors)
            ? implode(' ', $errors)
            : 'This volunteer has no enabled notification method with contact information.';
        return new WP_Error('notification_not_sent', $message);
    }

    $sent['errors'] = $errors;
    return $sent;
}

function spa_send_event_reminders($event, $reminder_type = 'scheduled') {
    $templates = $reminder_type === '24h' ? array('email' => null, 'sms' => null) : spa_get_event_notification_templates();
    if ( is_wp_error($templates) ) {
        return $templates;
    }

    $volunteers = spa_get_event_volunteers_for_notification($event->id);
    $sent = array(
        'email'      => 0,
        'sms'        => 0,
        'push'       => 0,
        'recipients' => count($volunteers),
        'errors'     => array(),
    );

    foreach ( $volunteers as $volunteer ) {
        $result = spa_send_event_notification_to_volunteer($event, $volunteer, $templates, $reminder_type);
        if ( is_wp_error($result) ) {
            $sent['errors'][] = trim($volunteer->first_name . ' ' . $volunteer->last_name) . ': ' . $result->get_error_message();
            continue;
        }
        $sent['email'] += $result['email'];
        $sent['sms'] += $result['sms'];
        $sent['push'] += $result['push'];
        if ( ! empty($result['errors']) ) {
            $sent['errors'] = array_merge($sent['errors'], $result['errors']);
        }
    }

    return $sent;
}

function spa_notify_event_volunteer_ajax() {
    global $wpdb;

    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
    $volunteer_id = isset($_POST['volunteer_id']) ? intval($_POST['volunteer_id']) : 0;

    $event = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT *
             FROM {$wpdb->prefix}spa_events
             WHERE id = %d
             AND active = 1",
            $event_id
        )
    );
    $volunteer = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT
               v.id,
               v.first_name,
               v.last_name,
               v.email,
               v.phone,
               v.push_external_id,
               v.email_enabled,
               v.phone_enabled,
               t.name AS team_name
             FROM {$wpdb->prefix}spa_event_volunteers ev
             INNER JOIN {$wpdb->prefix}spa_volunteers v
               ON v.id = ev.volunteer_id
               AND v.active = 1
             INNER JOIN {$wpdb->prefix}spa_teams t
               ON t.id = ev.team_id
               AND t.active = 1
             WHERE ev.event_id = %d
             AND ev.team_id = %d
             AND ev.volunteer_id = %d",
            $event_id,
            $team_id,
            $volunteer_id
        )
    );

    if ( ! $event || ! $volunteer ) {
        wp_send_json_error(array('message' => 'The saved volunteer assignment could not be found.'));
    }

    $sent = spa_send_event_notification_to_volunteer($event, $volunteer);
    if ( is_wp_error($sent) ) {
        wp_send_json_error(array('message' => $sent->get_error_message()));
    }

    $channels = array();
    if ( $sent['email'] > 0 ) {
        $channels[] = 'email';
    }
    if ( $sent['sms'] > 0 ) {
        $channels[] = 'SMS';
    }
    if ( $sent['push'] > 0 ) {
        $channels[] = 'push';
    }
    $message = 'Notification sent by ' . implode(' and ', $channels) . '.';
    if ( ! empty($sent['errors']) ) {
        $message .= ' ' . implode(' ', $sent['errors']);
    }

    wp_send_json_success(array('message' => $message));
}

function spa_run_notification_cron($force = false) {
    $event = spa_get_next_notified_event();
    if ( ! $event ) {
        return new WP_Error('no_notifiable_event', 'No upcoming event is configured for volunteer notifications.');
    }

    $result = array(
        'event'          => $event,
        'scheduled'      => array('email' => 0, 'sms' => 0, 'push' => 0),
        'reminder_24h'   => array('email' => 0, 'sms' => 0, 'push' => 0),
    );

    if ( $force || spa_notification_should_run_now() ) {
        $run_marker = wp_date('Y-m-d', time(), wp_timezone()) . ':' . intval($event->id);
        if ( $force || get_option('spa_notification_last_run', '') !== $run_marker ) {
            $scheduled_result = spa_send_event_reminders($event, 'scheduled');
            if (
                ! is_wp_error($scheduled_result)
                && (
                    $scheduled_result['email'] > 0
                    || $scheduled_result['sms'] > 0
                    || $scheduled_result['push'] > 0
                )
            ) {
                update_option('spa_notification_last_run', $run_marker, false);
            }
            if ( ! is_wp_error($scheduled_result) ) {
                $result['scheduled'] = $scheduled_result;
            }
        }
    }

    if ( intval(get_option('spa_notification_reminder_24h', 0)) === 1 ) {
        $event_datetime = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $event->event_date . ' ' . $event->start_time, wp_timezone());
        $now = current_datetime();
        $hours_until_event = $event_datetime ? ($event_datetime->getTimestamp() - $now->getTimestamp()) / HOUR_IN_SECONDS : 0;
        $sent_marker = 'spa_24h_reminder_sent_' . intval($event->id);
        $event_marker = $event_datetime ? $event_datetime->format('Y-m-d H:i:s') : '';
        if ( $event_datetime && $hours_until_event > 23 && $hours_until_event <= 24 && get_option($sent_marker, '') !== $event_marker ) {
            $reminder_result = spa_send_event_reminders($event, '24h');
            if ( ! is_wp_error($reminder_result) && ( $reminder_result['email'] > 0 || $reminder_result['sms'] > 0 ) ) {
                update_option($sent_marker, $event_marker, false);
            }
            if ( ! is_wp_error($reminder_result) ) {
                $result['reminder_24h'] = $reminder_result;
            }
        }
    }

    return $result;
}

function spa_schedule_hourly_notifications() {
    if ( ! wp_next_scheduled('spa_hourly_notification_check') ) {
        wp_schedule_event(time(), 'hourly', 'spa_hourly_notification_check');
    }
}
add_action('init', 'spa_schedule_hourly_notifications');
