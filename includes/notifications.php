<?php

add_action('spa_hourly_notification_check', 'spa_run_notification_cron');
add_action('wp_ajax_spa_notify_event_volunteer', 'spa_notify_event_volunteer_ajax');

function spa_notification_should_run_now() {
    if ( intval(get_option('spa_notifications_enabled', 1)) !== 1 ) {
        return false;
    }

    $day = intval(get_option('spa_notification_day_of_week', 0));
    $time = get_option('spa_notification_time', '09:00');
    $current_day = intval(wp_date('w'));
    $current_hour_minute = wp_date('H:i');

    return ($current_day === $day && $current_hour_minute === $time);
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
    global $wpdb;

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DISTINCT v.id, v.first_name, v.last_name, v.email, v.phone, v.email_enabled, v.phone_enabled, t.name AS team_name
             FROM {$wpdb->prefix}spa_event_volunteers ev
             INNER JOIN {$wpdb->prefix}spa_teams t ON t.id = ev.team_id
             INNER JOIN {$wpdb->prefix}spa_volunteers v ON v.id = ev.volunteer_id
             WHERE ev.event_id = %d
               AND v.active = 1",
            $event_id
        )
    );
}

function spa_get_event_notification_templates() {
    global $wpdb;

    $email_template_id = intval(get_option('spa_active_email_template', 0));
    $sms_template_id = intval(get_option('spa_active_sms_template', 0));

    $email_tpl = intval(get_option('spa_enable_email', 0)) === 1 && $email_template_id
        ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}spa_notification_templates WHERE id = %d", $email_template_id))
        : null;
    $sms_tpl = intval(get_option('spa_enable_sms', 0)) === 1 && $sms_template_id
        ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}spa_notification_templates WHERE id = %d", $sms_template_id))
        : null;

    if ( ! $email_tpl && ! $sms_tpl ) {
        return new WP_Error('no_templates', 'No enabled notification channel has an active template.');
    }

    return array(
        'email' => $email_tpl,
        'sms'   => $sms_tpl,
    );
}

function spa_send_event_notification_to_volunteer($event, $volunteer, $templates = null) {
    if ( $templates === null ) {
        $templates = spa_get_event_notification_templates();
    }
    if ( is_wp_error($templates) ) {
        return $templates;
    }

    $sent = array('email' => 0, 'sms' => 0);
    $errors = array();
    $data = array(
        'first_name'     => $volunteer->first_name,
        'last_name'      => $volunteer->last_name,
        'full_name'      => trim($volunteer->first_name . ' ' . $volunteer->last_name),
        'event_name'     => $event->name,
        'event_date'     => $event->event_date,
        'event_time'     => $event->start_time,
        'event_location' => $event->location,
        'team_name'      => $volunteer->team_name,
    );

    if ( $templates['email'] && ! empty($volunteer->email) && intval($volunteer->email_enabled) === 1 ) {
        $email_data = $data;
        $email_data['readings'] = spa_get_readings_tag_value(
            $volunteer->team_name,
            $event->service_builder_url ?? '',
            true
        );
        $subject_data = $email_data;
        $subject_data['readings'] = '';
        $subject = spa_process_template($templates['email']->subject ?: 'Volunteer Reminder', $subject_data);
        $body = spa_process_template($templates['email']->body, $email_data);
        $result = spa_send_email($volunteer->email, $subject, $body);
        if ( is_wp_error($result) ) {
            $errors[] = 'Email: ' . $result->get_error_message();
        } else {
            $sent['email']++;
        }
    }

    if ( $templates['sms'] && ! empty($volunteer->phone) && intval($volunteer->phone_enabled) === 1 ) {
        $sms_data = $data;
        $sms_data['readings'] = spa_get_readings_tag_value(
            $volunteer->team_name,
            $event->service_builder_url ?? '',
            false
        );
        $body = spa_process_template($templates['sms']->body, $sms_data);
        $result = spa_send_sms($volunteer->phone, $body);
        if ( is_wp_error($result) ) {
            $errors[] = 'SMS: ' . $result->get_error_message();
        } else {
            $sent['sms']++;
        }
    }

    if ( $sent['email'] === 0 && $sent['sms'] === 0 ) {
        $message = ! empty($errors)
            ? implode(' ', $errors)
            : 'This volunteer has no enabled notification method with contact information.';
        return new WP_Error('notification_not_sent', $message);
    }

    $sent['errors'] = $errors;
    return $sent;
}

function spa_send_event_reminders($event, $reminder_type = 'scheduled') {
    $templates = spa_get_event_notification_templates();
    if ( is_wp_error($templates) ) {
        return $templates;
    }

    $volunteers = spa_get_event_volunteers_for_notification($event->id);
    $sent = array('email' => 0, 'sms' => 0);

    foreach ( $volunteers as $volunteer ) {
        $result = spa_send_event_notification_to_volunteer($event, $volunteer, $templates);
        if ( ! is_wp_error($result) ) {
            $sent['email'] += $result['email'];
            $sent['sms'] += $result['sms'];
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
    $message = 'Notification sent by ' . implode(' and ', $channels) . '.';
    if ( ! empty($sent['errors']) ) {
        $message .= ' ' . implode(' ', $sent['errors']);
    }

    wp_send_json_success(array('message' => $message));
}

function spa_run_notification_cron() {
    if ( ! spa_notification_should_run_now() ) {
        return;
    }

    $event = spa_get_next_notified_event();
    if ( ! $event ) {
        return;
    }

    spa_send_event_reminders($event, 'scheduled');

    if ( intval(get_option('spa_notification_reminder_24h', 0)) === 1 ) {
        $event_time = strtotime($event->event_date . ' ' . $event->start_time);
        if ( $event_time && abs(($event_time - DAY_IN_SECONDS) - current_time('timestamp')) < HOUR_IN_SECONDS ) {
            spa_send_event_reminders($event, '24h');
        }
    }
}

function spa_schedule_hourly_notifications() {
    if ( ! wp_next_scheduled('spa_hourly_notification_check') ) {
        wp_schedule_event(time(), 'hourly', 'spa_hourly_notification_check');
    }
}
add_action('init', 'spa_schedule_hourly_notifications');
