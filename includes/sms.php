<?php

function spa_send_sms($to, $message, $provider = null) {
    if ( empty($to) ) {
        return new WP_Error('missing_recipient', 'Recipient phone number is required');
    }

    if ( $provider === null ) {
        $provider = get_option('spa_sms_provider', 'twilio');
    }

    switch ($provider) {
        case 'twilio':
            $sid = get_option('spa_twilio_sid', '');
            $token = get_option('spa_twilio_token', '');
            $from = get_option('spa_twilio_from', '');
            if (empty($sid) || empty($token) || empty($from)) {
                $missing = array();
                if ( empty($sid) ) {
                    $missing[] = 'Account SID';
                }
                if ( empty($token) ) {
                    $missing[] = 'Auth Token';
                }
                if ( empty($from) ) {
                    $missing[] = 'From Number';
                }
                return new WP_Error('twilio_not_configured', 'Twilio is missing: ' . implode(', ', $missing) . '.');
            }
            $url = "https://api.twilio.com/2010-04-01/Accounts/" . rawurlencode($sid) . "/Messages.json";
            $args = array(
                'body' => array(
                    'From' => $from,
                    'To' => $to,
                    'Body' => $message
                ),
                'timeout' => 20,
                'headers' => array(),
                'httpversion' => '1.1',
                'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
            );
            $args['headers']['Authorization'] = 'Basic ' . base64_encode($sid . ':' . $token);
            $resp = wp_remote_post($url, $args);
            if ( is_wp_error($resp) ) return $resp;
            $code = wp_remote_retrieve_response_code($resp);
            $body = wp_remote_retrieve_body($resp);
            if ( $code < 200 || $code >= 300 ) {
                return new WP_Error('twilio_error', "Twilio error: " . $body);
            }
            return true;

        case 'vonage':
            $key = get_option('spa_vonage_key', '');
            $secret = get_option('spa_vonage_secret', '');
            $from = get_option('spa_vonage_from', '');
            if (empty($key) || empty($secret) || empty($from)) {
                return new WP_Error('vonage_not_configured', 'Vonage not configured');
            }
            $url = 'https://rest.nexmo.com/sms/json';
            $args = array(
                'body' => array(
                    'api_key' => $key,
                    'api_secret' => $secret,
                    'to' => $to,
                    'from' => $from,
                    'text' => $message
                ),
                'timeout' => 20
            );
            $resp = wp_remote_post($url, $args);
            if ( is_wp_error($resp) ) return $resp;
            $code = wp_remote_retrieve_response_code($resp);
            $body = wp_remote_retrieve_body($resp);
            if ( $code < 200 || $code >= 300 ) {
                return new WP_Error('vonage_error', "Vonage error: " . $body);
            }
            $json = json_decode($body, true);
            if ( isset($json['messages']) && isset($json['messages'][0]) && $json['messages'][0]['status'] === '0' ) {
                return true;
            }
            return new WP_Error('vonage_error', isset($body) ? $body : 'Unknown Vonage error');

        case 'plivo':
            $auth_id = get_option('spa_plivo_auth_id', '');
            $auth_token = get_option('spa_plivo_auth_token', '');
            $from = get_option('spa_plivo_from', '');
            if (empty($auth_id) || empty($auth_token) || empty($from)) {
                return new WP_Error('plivo_not_configured', 'Plivo not configured');
            }
            $url = "https://api.plivo.com/v1/Account/" . rawurlencode($auth_id) . "/Message/";
            $args = array(
                'body' => json_encode(array('src' => $from, 'dst' => $to, 'text' => $message)),
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode($auth_id . ':' . $auth_token)
                ),
                'timeout' => 20
            );
            $resp = wp_remote_post($url, $args);
            if ( is_wp_error($resp) ) return $resp;
            $code = wp_remote_retrieve_response_code($resp);
            $body = wp_remote_retrieve_body($resp);
            if ( $code < 200 || $code >= 300 ) {
                return new WP_Error('plivo_error', "Plivo error: " . $body);
            }
            return true;

        case 'messagebird':
            $key = get_option('spa_messagebird_key', '');
            $from = get_option('spa_messagebird_from', '');
            if (empty($key) || empty($from)) {
                return new WP_Error('messagebird_not_configured', 'MessageBird not configured');
            }
            $url = 'https://rest.messagebird.com/messages';
            $args = array(
                'headers' => array('Authorization' => 'AccessKey ' . $key, 'Content-Type' => 'application/json'),
                'body' => json_encode(array('recipients' => $to, 'originator' => $from, 'body' => $message)),
                'timeout' => 20
            );
            $resp = wp_remote_post($url, $args);
            if ( is_wp_error($resp) ) return $resp;
            $code = wp_remote_retrieve_response_code($resp);
            $body = wp_remote_retrieve_body($resp);
            if ( $code < 200 || $code >= 300 ) {
                return new WP_Error('messagebird_error', "MessageBird error: " . $body);
            }
            return true;

        case 'textmagic':
            $username = get_option('spa_textmagic_username', '');
            $api_key = get_option('spa_textmagic_api_key', '');
            $from = get_option('spa_textmagic_from', '');
            if (empty($username) || empty($api_key)) {
                return new WP_Error('textmagic_not_configured', 'TextMagic not configured');
            }
            // TextMagic v2 uses token in X-TM-Key and X-TM-Username headers
            $url = 'https://rest.textmagic.com/api/v2/messages';
            $args = array(
                'headers' => array('X-TM-Username' => $username, 'X-TM-Key' => $api_key, 'Content-Type' => 'application/json'),
                'body' => json_encode(array('phones' => $to, 'text' => $message, 'from' => $from)),
                'timeout' => 20
            );
            $resp = wp_remote_post($url, $args);
            if ( is_wp_error($resp) ) return $resp;
            $code = wp_remote_retrieve_response_code($resp);
            $body = wp_remote_retrieve_body($resp);
            if ( $code < 200 || $code >= 300 ) {
                return new WP_Error('textmagic_error', "TextMagic error: " . $body);
            }
            return true;

        default:
            return new WP_Error('unsupported_provider', 'Unsupported SMS provider');
    }
}
