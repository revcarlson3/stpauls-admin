<?php

add_action('spa_hourly_notification_check', 'spa_run_notification_cron');

function spa_notification_should_run_now() {
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
             FROM {$wpdb->prefix}spa_events_teams et
             INNER JOIN {$wpdb->prefix}spa_teams t ON t.id = et.team_id
             INNER JOIN {$wpdb->prefix}spa_volunteer_teams vt ON vt.team_id = t.id
             INNER JOIN {$wpdb->prefix}spa_volunteers v ON v.id = vt.volunteer_id
             WHERE et.event_id = %d
               AND v.active = 1",
            $event_id
        )
    );
}

function spa_send_event_reminders($event, $reminder_type = 'scheduled') {
    global $wpdb;

    $email_template_id = intval(get_option('spa_active_email_template', 0));
    $sms_template_id = intval(get_option('spa_active_sms_template', 0));

    $email_tpl = $email_template_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}spa_notification_templates WHERE id = %d", $email_template_id)) : null;
    $sms_tpl   = $sms_template_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}spa_notification_templates WHERE id = %d", $sms_template_id)) : null;

    if ( ! $email_tpl && ! $sms_tpl ) {
        return new WP_Error('no_templates', 'No active notification templates selected.');
    }

    $volunteers = spa_get_event_volunteers_for_notification($event->id);
    $sent = array('email' => 0, 'sms' => 0);

    foreach ( $volunteers as $volunteer ) {
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

        if ( $email_tpl && ! empty($volunteer->email) && intval($volunteer->email_enabled) === 1 ) {
            $subject = spa_process_template($email_tpl->subject ?: 'Volunteer Reminder', $data);
            $body = spa_process_template($email_tpl->body, $data);
            $result = spa_send_email($volunteer->email, $subject, $body);
            if ( ! is_wp_error($result) ) {
                $sent['email']++;
            }
        }

        if ( $sms_tpl && ! empty($volunteer->phone) && intval($volunteer->phone_enabled) === 1 ) {
            $body = spa_process_template($sms_tpl->body, $data);
            $result = spa_send_sms($volunteer->phone, $body);
            if ( ! is_wp_error($result) ) {
                $sent['sms']++;
            }
        }
    }

    return $sent;
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
