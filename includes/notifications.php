<?php

add_action('spa_hourly_notification_check', 'spa_run_notification_cron');
add_action('spa_weekly_assignment_report', 'spa_send_weekly_assignment_report');
add_action('wp_ajax_spa_notify_event_volunteer', 'spa_notify_event_volunteer_ajax');

function spa_get_upcoming_assignment_events($limit = 2) {
    global $wpdb;

    $limit = max(1, intval($limit));
    $today = current_time('Y-m-d');
    $now = current_time('H:i:s');
    $events = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT e.id, e.name, e.event_date, e.start_time
             FROM {$wpdb->prefix}spa_events e
             WHERE e.active = 1
             AND (e.event_date > %s OR (e.event_date = %s AND e.start_time >= %s))
             AND EXISTS (
                 SELECT 1
                 FROM {$wpdb->prefix}spa_event_volunteers ev_check
                 WHERE ev_check.event_id = e.id
             )
             ORDER BY e.event_date ASC, e.start_time ASC, e.id ASC
             LIMIT %d",
            $today,
            $today,
            $now,
            $limit
        ),
        ARRAY_A
    );

    foreach ( $events as &$event ) {
        $assignments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    t.name AS team_name,
                    CONCAT(v.first_name, ' ', v.last_name) AS volunteer_name
                 FROM {$wpdb->prefix}spa_event_volunteers ev
                 INNER JOIN {$wpdb->prefix}spa_teams t ON t.id = ev.team_id AND t.active = 1
                 INNER JOIN {$wpdb->prefix}spa_volunteers v ON v.id = ev.volunteer_id AND v.active = 1
                 WHERE ev.event_id = %d
                 ORDER BY t.name, v.last_name, v.first_name",
                $event['id']
            ),
            ARRAY_A
        );

        $event['assignments'] = array();
        foreach ( $assignments as $assignment ) {
            if ( ! isset($event['assignments'][$assignment['team_name']]) ) {
                $event['assignments'][$assignment['team_name']] = array();
            }
            $event['assignments'][$assignment['team_name']][] = $assignment['volunteer_name'];
        }
    }
    unset($event);

    return $events;
}

function spa_build_weekly_assignment_report_email($events) {
    $body = '<div style="font-family:Arial,sans-serif;color:#222;line-height:1.5;">';
    $body .= '<h1 style="font-size:22px;margin:0 0 16px;">Weekly Assignments</h1>';

    if ( empty($events) ) {
        $body .= '<p>There are no upcoming events with volunteer assignments.</p></div>';
        return $body;
    }

    foreach ( $events as $index => $event ) {
        $event_date = DateTimeImmutable::createFromFormat('!Y-m-d', $event['event_date'], wp_timezone());
        $start_time = DateTimeImmutable::createFromFormat('!H:i:s', $event['start_time'], wp_timezone());
        $label = $index === 0 ? 'Current Event' : 'Next Event';
        $date = $event_date ? $event_date->format('F j, Y') : $event['event_date'];
        $time = $start_time ? $start_time->format(get_option('time_format')) : $event['start_time'];

        $body .= '<section style="margin:0 0 24px;">';
        $body .= '<h2 style="font-size:18px;margin:0 0 4px;">' . esc_html($label . ': ' . $event['name']) . '</h2>';
        $body .= '<p style="margin:0 0 10px;color:#555;">' . esc_html($date . ' at ' . $time) . '</p>';
        $body .= '<table role="presentation" style="width:100%;border-collapse:collapse;">';
        $body .= '<thead><tr><th style="border:1px solid #ccc;padding:8px;text-align:left;background:#f3f3f3;">Team</th>';
        $body .= '<th style="border:1px solid #ccc;padding:8px;text-align:left;background:#f3f3f3;">Volunteers</th></tr></thead><tbody>';

        foreach ( $event['assignments'] as $team_name => $volunteers ) {
            $body .= '<tr><td style="border:1px solid #ccc;padding:8px;vertical-align:top;">' . esc_html($team_name) . '</td>';
            $body .= '<td style="border:1px solid #ccc;padding:8px;vertical-align:top;">' . implode('<br>', array_map('esc_html', $volunteers)) . '</td></tr>';
        }

        $body .= '</tbody></table></section>';
    }

    if ( count($events) === 1 ) {
        $body .= '<section style="margin:0 0 24px;"><h2 style="font-size:18px;margin:0 0 4px;">Next Event</h2>';
        $body .= '<p style="margin:0;">There is no additional upcoming event with volunteer assignments.</p></section>';
    }

    return $body . '</div>';
}

function spa_send_weekly_assignment_report($force = false) {
    if ( ! $force && intval(get_option('spa_weekly_report_enabled', 0)) !== 1 ) {
        return false;
    }

    if ( intval(get_option('spa_weekly_report_enabled', 0)) === 1 ) {
        // Queue the following run before sending so a transient mail failure cannot stop future reports.
        spa_reschedule_weekly_assignment_report();
    }

    $recipient = sanitize_email(get_option('spa_weekly_report_recipient', ''));
    if ( ! is_email($recipient) ) {
        error_log('St. Paul\'s Admin weekly assignment report has no valid recipient.');
        return new WP_Error('invalid_weekly_report_recipient', 'The weekly assignment report recipient is not a valid email address.');
    }

    $events = spa_get_upcoming_assignment_events(2);
    $subject = get_bloginfo('name') . ' - Weekly Assignments';
    $result = spa_send_email($recipient, $subject, spa_build_weekly_assignment_report_email($events));
    if ( is_wp_error($result) ) {
        error_log('St. Paul\'s Admin weekly assignment report failed: ' . $result->get_error_message());
    }

    return $result;
}

function spa_get_next_weekly_assignment_report_timestamp() {
    $day = max(0, min(6, intval(get_option('spa_weekly_report_day_of_week', 0))));
    $time = get_option('spa_weekly_report_time', '09:00');
    if ( ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) ) {
        $time = '09:00';
    }

    list($hour, $minute) = array_map('intval', explode(':', $time));
    $now = current_datetime();
    $days_ahead = ($day - intval($now->format('w')) + 7) % 7;
    $next = $now->setTime($hour, $minute, 0)->modify('+' . $days_ahead . ' days');
    if ( $next <= $now ) {
        $next = $next->modify('+7 days');
    }

    return $next->getTimestamp();
}

function spa_reschedule_weekly_assignment_report() {
    wp_clear_scheduled_hook('spa_weekly_assignment_report');

    if ( intval(get_option('spa_weekly_report_enabled', 0)) === 1 ) {
        $scheduled = wp_schedule_single_event(
            spa_get_next_weekly_assignment_report_timestamp(),
            'spa_weekly_assignment_report',
            array(),
            true
        );
        if ( is_wp_error($scheduled) ) {
            error_log('St. Paul\'s Admin could not schedule the weekly assignment report: ' . $scheduled->get_error_message());
        }
    }
}

function spa_ensure_weekly_assignment_report_schedule() {
    if ( intval(get_option('spa_weekly_report_enabled', 0)) === 1 ) {
        if ( ! wp_next_scheduled('spa_weekly_assignment_report') ) {
            spa_reschedule_weekly_assignment_report();
        }
    } elseif ( wp_next_scheduled('spa_weekly_assignment_report') ) {
        wp_clear_scheduled_hook('spa_weekly_assignment_report');
    }
}

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
    $sent = array('email' => 0, 'sms' => 0, 'push' => 0);

    foreach ( $volunteers as $volunteer ) {
        $result = spa_send_event_notification_to_volunteer($event, $volunteer, $templates, $reminder_type);
        if ( ! is_wp_error($result) ) {
            $sent['email'] += $result['email'];
            $sent['sms'] += $result['sms'];
            $sent['push'] += $result['push'];
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

function spa_run_notification_cron() {
    if ( ! spa_notification_should_run_now() ) {
        return;
    }

    $event = spa_get_next_notified_event();
    if ( ! $event ) {
        return;
    }

    if ( spa_notification_should_run_now() ) {
        spa_send_event_reminders($event, 'scheduled');
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
        }
    }
}

function spa_schedule_hourly_notifications() {
    if ( ! wp_next_scheduled('spa_hourly_notification_check') ) {
        wp_schedule_event(time(), 'hourly', 'spa_hourly_notification_check');
    }
}
add_action('init', 'spa_schedule_hourly_notifications');
add_action('init', 'spa_ensure_weekly_assignment_report_schedule');
