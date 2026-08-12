<?php
add_action('wp_ajax_spa_save_template',   'spa_save_template_ajax');
add_action('wp_ajax_spa_delete_template', 'spa_delete_template_ajax');
add_action('wp_ajax_spa_load_template',   'spa_load_template_ajax');
add_action('wp_ajax_spa_get_template_list', 'spa_get_template_list_ajax');

function spa_get_template_list_ajax() {
    global $wpdb;

    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }

    $rows = $wpdb->get_results(
        "SELECT id, name, type FROM {$wpdb->prefix}spa_notification_templates ORDER BY type, name"
    );
    $email = array_values(array_filter($rows, fn($r) => $r->type === 'email'));
    $sms   = array_values(array_filter($rows, fn($r) => $r->type === 'sms'));

    wp_send_json_success(array('email' => $email, 'sms' => $sms));
}

function spa_save_template_ajax() {
    global $wpdb;

    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $id      = intval($_POST['template_id'] ?? 0);
    $name    = sanitize_text_field($_POST['template_name'] ?? '');
    $type    = sanitize_text_field($_POST['template_type'] ?? '');
    $subject = sanitize_text_field($_POST['template_subject'] ?? '');
    $body    = wp_kses_post(wp_unslash($_POST['template_body'] ?? ''));

    if ( empty($name) || empty($type) || empty($body) ) {
        wp_send_json_error(array('message' => 'Name, type, and body are required.'));
    }

    if ( ! in_array($type, array('email', 'sms')) ) {
        wp_send_json_error(array('message' => 'Invalid template type.'));
    }

    $table = $wpdb->prefix . 'spa_notification_templates';

    if ( $id > 0 ) {
        $wpdb->update(
            $table,
            array('name' => $name, 'type' => $type, 'subject' => $subject, 'body' => $body),
            array('id' => $id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
    } else {
        $wpdb->insert(
            $table,
            array('name' => $name, 'type' => $type, 'subject' => $subject, 'body' => $body),
            array('%s', '%s', '%s', '%s')
        );
        $id = $wpdb->insert_id;
    }

    wp_send_json_success(array('id' => $id, 'message' => 'Template saved.'));
}

function spa_delete_template_ajax() {
    global $wpdb;

    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $id = intval($_POST['template_id'] ?? 0);
    if ( ! $id ) {
        wp_send_json_error(array('message' => 'Invalid ID'));
    }

    $wpdb->delete($wpdb->prefix . 'spa_notification_templates', array('id' => $id), array('%d'));
    wp_send_json_success(array('message' => 'Template deleted.'));
}

function spa_load_template_ajax() {
    global $wpdb;

    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $id = intval($_POST['template_id'] ?? 0);
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}spa_notification_templates WHERE id = %d", $id
    ));

    if ( ! $row ) {
        wp_send_json_error(array('message' => 'Template not found'));
    }

    wp_send_json_success($row);
}
