<?php

/**
 * Shared notification recipient, message, dispatch, and delivery-log helpers.
 */

function spa_get_notification_recipients_for_event($event_id) {
    global $wpdb;

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DISTINCT v.id, v.first_name, v.last_name, v.email, v.phone, v.push_external_id, v.email_enabled, v.phone_enabled, t.name AS team_name
             FROM {$wpdb->prefix}spa_event_volunteers ev
             INNER JOIN {$wpdb->prefix}spa_teams t ON t.id = ev.team_id
             INNER JOIN {$wpdb->prefix}spa_volunteers v ON v.id = ev.volunteer_id
             WHERE ev.event_id = %d
               AND v.active = 1",
            intval($event_id)
        )
    );
}

function spa_build_event_notification_data($event, $volunteer, $html_readings = true) {
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
    if ( $html_readings !== null ) {
        $data['readings'] = spa_get_readings_tag_value(
            $volunteer->team_name,
            $event->service_builder_url ?? '',
            $html_readings
        );
    }

    return $data;
}

function spa_build_event_notification_message($event, $volunteer, $templates, $channel, $reminder_type = 'scheduled') {
    $is_24h_reminder = $reminder_type === '24h';
    $data = spa_build_event_notification_data(
        $event,
        $volunteer,
        $channel === 'email' ? true : ( $channel === 'sms' ? false : null )
    );
    $team_name = trim((string) $volunteer->team_name);
    $article = $team_name !== '' && in_array(strtolower($team_name[0]), array('a', 'e', 'i', 'o', 'u'), true) ? 'an' : 'a';
    $fixed_reminder = sprintf('Reminder: you are serving as %s %s on Sunday. See you there!', $article, $team_name);

    if ( $channel === 'email' ) {
        if ( $is_24h_reminder ) {
            return array('subject' => 'Volunteer Reminder', 'body' => '<p>' . esc_html($fixed_reminder) . '</p>');
        }

        $subject_data = $data;
        $subject_data['readings'] = '';
        return array(
            'subject' => spa_process_template($templates['email']->subject ?: 'Volunteer Reminder', $subject_data),
            'body'    => spa_process_template($templates['email']->body, $data),
        );
    }

    if ( $channel === 'sms' ) {
        return array(
            'subject' => '',
            'body'    => $is_24h_reminder ? $fixed_reminder : spa_process_template($templates['sms']->body, $data),
        );
    }

    return array(
        'subject' => $is_24h_reminder
            ? 'Volunteer Reminder'
            : ( $templates['email'] ? spa_process_template($templates['email']->subject ?: 'Volunteer Reminder', $data) : 'Volunteer Reminder' ),
        'body' => $is_24h_reminder
            ? $fixed_reminder
            : ( $templates['email'] ? spa_process_template($templates['email']->body ?: 'You are scheduled to serve.', $data) : 'You are scheduled to serve.' ),
    );
}

function spa_dispatch_notification($channel, $event, $volunteer, $message, $provider = null) {
    if ( $provider === null ) {
        $provider = get_option('spa_' . $channel . '_provider', $channel === 'email' ? 'wp_mail' : ( $channel === 'sms' ? 'twilio' : 'onesignal' ));
    }

    $log_id = spa_create_delivery_log($event, $volunteer, $channel, $provider);
    if ( $channel === 'email' ) {
        $result = spa_send_email($volunteer->email, $message['subject'], $message['body'], array(), array(), array('delivery_log_id' => $log_id));
    } elseif ( $channel === 'sms' ) {
        $result = spa_send_sms($volunteer->phone, $message['body'], $provider, array('delivery_log_id' => $log_id));
    } else {
        $result = spa_send_push_notification(array($volunteer->push_external_id), $message['subject'], wp_strip_all_tags($message['body']), $provider);
    }

    if ( is_wp_error($result) ) {
        spa_mark_delivery_failed($log_id, $result->get_error_message());
        return $result;
    }

    spa_mark_delivery_sent(
        $log_id,
        $result,
        ( $channel === 'email' && $provider === 'sendgrid' ) || ( $channel === 'sms' && $provider === 'twilio' )
    );
    return $result;
}
