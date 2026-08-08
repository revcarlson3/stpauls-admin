<?php

function spa_send_push_notification($user_ids, $title, $message, $provider = null) {
    if (empty($provider)) {
        $provider = get_option('spa_push_provider', 'onesignal');
    }

    switch ($provider) {
        case 'onesignal':
            return spa_send_push_onesignal($user_ids, $title, $message);
        case 'firebase':
            return spa_send_push_firebase($user_ids, $title, $message);
        default:
            return new WP_Error('invalid_provider', 'Invalid push notification provider');
    }
}

function spa_send_push_onesignal($user_ids, $title, $message) {
    $app_id = get_option('spa_onesignal_app_id', '');
    $api_key = get_option('spa_onesignal_api_key', '');

    if (empty($app_id) || empty($api_key)) {
        return new WP_Error('missing_config', 'OneSignal App ID or API Key not configured');
    }

    $url = 'https://onesignal.com/api/v1/notifications';
    
    $body = array(
        'app_id' => $app_id,
        'include_external_user_ids' => $user_ids,
        'headings' => array('en' => $title),
        'contents' => array('en' => $message),
    );

    $response = wp_remote_post($url, array(
        'headers' => array(
            'Authorization' => 'Basic ' . $api_key,
            'Content-Type' => 'application/json',
        ),
        'body' => wp_json_encode($body),
        'timeout' => 30,
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code($response);
    if ($status !== 200) {
        $body = wp_remote_retrieve_body($response);
        return new WP_Error('onesignal_error', 'OneSignal API error: ' . $body);
    }

    return true;
}

function spa_send_push_firebase($user_ids, $title, $message) {
    $project_id = get_option('spa_firebase_project_id', '');
    $server_key = get_option('spa_firebase_server_key', '');

    if (empty($project_id) || empty($server_key)) {
        return new WP_Error('missing_config', 'Firebase Project ID or Server Key not configured');
    }

    $url = 'https://fcm.googleapis.com/fcm/send';
    
    foreach ((array)$user_ids as $user_id) {
        $body = array(
            'to' => $user_id,
            'notification' => array(
                'title' => $title,
                'body' => $message,
                'click_action' => home_url(),
            ),
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'key=' . $server_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($body),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code($response);
        if ($status !== 200) {
            $body = wp_remote_retrieve_body($response);
            return new WP_Error('firebase_error', 'Firebase API error: ' . $body);
        }
    }

    return true;
}
