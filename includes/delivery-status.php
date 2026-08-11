<?php

add_action('rest_api_init', 'spa_register_delivery_status_routes');
add_action('spa_hourly_notification_check', 'spa_cleanup_delivery_logs', 20);

function spa_register_delivery_status_routes() {
    register_rest_route(
        'spa/v1',
        '/twilio-status',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'spa_handle_twilio_status_webhook',
            'permission_callback' => '__return_true',
        )
    );
    register_rest_route(
        'spa/v1',
        '/sendgrid-events',
        array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'spa_handle_sendgrid_event_webhook',
            'permission_callback' => '__return_true',
        )
    );
}

function spa_create_delivery_log($event, $volunteer, $channel, $provider) {
    global $wpdb;

    $inserted = $wpdb->insert(
        $wpdb->prefix . 'spa_notification_delivery_logs',
        array(
            'event_id'      => ! empty($event->id) ? intval($event->id) : null,
            'volunteer_id'  => ! empty($volunteer->id) ? intval($volunteer->id) : null,
            'volunteer_name' => trim((string) $volunteer->first_name . ' ' . (string) $volunteer->last_name),
            'channel'       => sanitize_key($channel),
            'provider'      => sanitize_key($provider),
            'status'        => 'pending',
            'created_at'    => current_time('mysql'),
            'updated_at'    => current_time('mysql'),
        ),
        array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if ( $inserted !== 1 ) {
        error_log('St. Paul\'s Admin could not create a notification delivery log: ' . $wpdb->last_error);
        return 0;
    }

    return intval($wpdb->insert_id);
}

function spa_normalize_delivery_failure_reason($reason) {
    $reason = trim(wp_strip_all_tags((string) $reason));
    if ( $reason === '' ) {
        $reason = 'The provider did not supply a failure reason.';
    }

    return function_exists('mb_substr') ? mb_substr($reason, 0, 1000) : substr($reason, 0, 1000);
}

function spa_update_delivery_log($log_id, $data, $formats) {
    global $wpdb;

    if ( intval($log_id) < 1 ) {
        return false;
    }

    $data['updated_at'] = current_time('mysql');
    $formats[] = '%s';
    $updated = $wpdb->update(
        $wpdb->prefix . 'spa_notification_delivery_logs',
        $data,
        array('id' => intval($log_id)),
        $formats,
        array('%d')
    );
    if ( $updated === false ) {
        error_log('St. Paul\'s Admin could not update notification delivery log ' . intval($log_id) . ': ' . $wpdb->last_error);
        return false;
    }

    return true;
}

function spa_mark_delivery_failed($log_id, $reason, $failed_at = '') {
    global $wpdb;

    if ( intval($log_id) < 1 ) {
        return false;
    }

    return $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->prefix}spa_notification_delivery_logs
             SET status = %s,
                 failure_reason = %s,
                 failed_at = %s,
                 updated_at = %s
             WHERE id = %d
             AND status <> %s",
            'failed',
            spa_normalize_delivery_failure_reason($reason),
            $failed_at !== '' ? $failed_at : current_time('mysql'),
            current_time('mysql'),
            intval($log_id),
            'delivered'
        )
    );
}

function spa_mark_delivery_sent($log_id, $result, $retain_for_webhook) {
    global $wpdb;

    if ( intval($log_id) < 1 ) {
        return;
    }

    $message_id = is_array($result) && ! empty($result['message_id'])
        ? sanitize_text_field($result['message_id'])
        : null;
    $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->prefix}spa_notification_delivery_logs
             SET status = %s,
                 provider_message_id = %s,
                 updated_at = %s
             WHERE id = %d
             AND status = %s",
            'sent',
            $message_id,
            current_time('mysql'),
            intval($log_id),
            'pending'
        )
    );
}

function spa_mark_delivery_delivered($log_id, $message_id) {
    global $wpdb;

    if ( intval($log_id) < 1 ) {
        return false;
    }

    return $wpdb->query(
        $wpdb->prepare(
            "UPDATE {$wpdb->prefix}spa_notification_delivery_logs
             SET status = %s,
                 provider_message_id = %s,
                 failure_reason = NULL,
                 failed_at = NULL,
                 updated_at = %s
             WHERE id = %d
             AND status <> %s",
            'delivered',
            sanitize_text_field($message_id),
            current_time('mysql'),
            intval($log_id),
            'failed'
        )
    );
}

function spa_cleanup_delivery_logs() {
    global $wpdb;

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}spa_notification_delivery_logs
             WHERE status <> %s
             AND created_at < %s",
            'failed',
            wp_date('Y-m-d H:i:s', time() - (30 * DAY_IN_SECONDS), wp_timezone())
        )
    );
}

function spa_verify_twilio_webhook_signature(WP_REST_Request $request, $log_id) {
    $signature = $request->get_header('x-twilio-signature');
    $token = get_option('spa_twilio_token', '');
    if ( $signature === '' || $token === '' ) {
        return false;
    }

    $signed_data = add_query_arg(
        'log_id',
        intval($log_id),
        rest_url('spa/v1/twilio-status')
    );
    $params = $request->get_body_params();
    ksort($params, SORT_STRING);
    foreach ( $params as $key => $value ) {
        foreach ( (array) $value as $item ) {
            $signed_data .= $key . $item;
        }
    }

    $expected = base64_encode(hash_hmac('sha1', $signed_data, $token, true));
    return hash_equals($expected, $signature);
}

function spa_handle_twilio_status_webhook(WP_REST_Request $request) {
    global $wpdb;

    $log_id = intval($request->get_param('log_id'));
    if ( $log_id < 1 || ! spa_verify_twilio_webhook_signature($request, $log_id) ) {
        return new WP_Error('invalid_twilio_signature', 'Invalid Twilio signature.', array('status' => 403));
    }

    $log = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, provider_message_id
             FROM {$wpdb->prefix}spa_notification_delivery_logs
             WHERE id = %d
             AND provider = %s",
            $log_id,
            'twilio'
        )
    );
    if ( ! $log ) {
        return new WP_Error('delivery_log_not_found', 'Delivery log not found.', array('status' => 404));
    }

    $message_sid = sanitize_text_field((string) $request->get_param('MessageSid'));
    if ( $log->provider_message_id && ! hash_equals((string) $log->provider_message_id, $message_sid) ) {
        return new WP_Error('twilio_message_mismatch', 'Twilio message does not match this delivery log.', array('status' => 409));
    }

    $status = sanitize_key((string) $request->get_param('MessageStatus'));
    if ( in_array($status, array('failed', 'undelivered', 'canceled'), true) ) {
        $error_code = sanitize_text_field((string) $request->get_param('ErrorCode'));
        $reason = 'Twilio reported the message as ' . $status . '.';
        if ( $error_code !== '' ) {
            $known_reasons = array(
                '21211' => 'The destination phone number is invalid.',
                '21610' => 'The recipient has opted out of SMS messages.',
                '21614' => 'The destination number cannot receive SMS messages.',
                '30003' => 'The destination handset is unavailable or unreachable.',
                '30004' => 'The message was blocked.',
                '30005' => 'The destination handset is unknown.',
                '30006' => 'The destination is a landline or its carrier is unreachable.',
                '30007' => 'The message was filtered by the carrier.',
                '30017' => 'The carrier network was congested.',
                '30034' => 'US A2P 10DLC registration is required.',
            );
            $reason .= ' ' . ($known_reasons[$error_code] ?? ('Error code: ' . $error_code . '.'));
        }
        spa_update_delivery_log(
            $log_id,
            array('provider_message_id' => $message_sid),
            array('%s')
        );
        spa_mark_delivery_failed($log_id, $reason);
    } elseif ( $status === 'delivered' ) {
        spa_mark_delivery_delivered($log_id, $message_sid);
    } elseif ( in_array($status, array('queued', 'sending', 'sent'), true) ) {
        spa_mark_delivery_sent(
            $log_id,
            array('message_id' => $message_sid),
            true
        );
    }

    return new WP_REST_Response(null, 204);
}

function spa_get_sendgrid_verification_key() {
    $key = trim((string) get_option('spa_sendgrid_webhook_public_key', ''));
    if ( $key === '' || strpos($key, 'BEGIN PUBLIC KEY') !== false ) {
        return $key;
    }

    $key = preg_replace('/\s+/', '', $key);
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split($key, 64, "\n") . "-----END PUBLIC KEY-----";
}

function spa_verify_sendgrid_webhook_signature(WP_REST_Request $request) {
    if ( ! function_exists('openssl_verify') ) {
        return false;
    }

    $timestamp = $request->get_header('x-twilio-email-event-webhook-timestamp');
    $signature = base64_decode(
        $request->get_header('x-twilio-email-event-webhook-signature'),
        true
    );
    $public_key = spa_get_sendgrid_verification_key();
    if (
        ! ctype_digit((string) $timestamp)
        || $signature === false
        || $public_key === ''
    ) {
        return false;
    }

    $key_resource = openssl_pkey_get_public($public_key);
    if ( $key_resource === false ) {
        return false;
    }

    return openssl_verify(
        $timestamp . $request->get_body(),
        $signature,
        $key_resource,
        OPENSSL_ALGO_SHA256
    ) === 1;
}

function spa_handle_sendgrid_event_webhook(WP_REST_Request $request) {
    global $wpdb;

    if ( ! spa_verify_sendgrid_webhook_signature($request) ) {
        return new WP_Error('invalid_sendgrid_signature', 'Invalid SendGrid signature.', array('status' => 403));
    }

    $events = json_decode($request->get_body(), true);
    if ( ! is_array($events) ) {
        return new WP_Error('invalid_sendgrid_payload', 'Invalid SendGrid event payload.', array('status' => 400));
    }

    foreach ( $events as $event ) {
        if ( ! is_array($event) ) {
            continue;
        }
        $log_id = intval($event['spa_log_id'] ?? ($event['custom_args']['spa_log_id'] ?? 0));
        if ( $log_id < 1 ) {
            continue;
        }
        $is_sendgrid_log = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$wpdb->prefix}spa_notification_delivery_logs
                 WHERE id = %d
                 AND provider = %s",
                $log_id,
                'sendgrid'
            )
        );
        if ( ! $is_sendgrid_log ) {
            continue;
        }

        $event_type = sanitize_key($event['event'] ?? '');
        $message_id = sanitize_text_field($event['sg_message_id'] ?? '');
        if ( in_array($event_type, array('bounce', 'dropped'), true) ) {
            $reason = $event['reason'] ?? ($event['response'] ?? ('SendGrid reported ' . $event_type . '.'));
            spa_update_delivery_log(
                $log_id,
                array('provider_message_id' => $message_id),
                array('%s')
            );
            $failed_at = ! empty($event['timestamp'])
                ? wp_date('Y-m-d H:i:s', intval($event['timestamp']), wp_timezone())
                : current_time('mysql');
            spa_mark_delivery_failed($log_id, $reason, $failed_at);
        } elseif ( $event_type === 'delivered' ) {
            spa_mark_delivery_delivered($log_id, $message_id);
        }
    }

    return new WP_REST_Response(null, 204);
}
