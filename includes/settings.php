<?php

function spa_handle_settings_post() {
    // Handle saves via admin-post.php
    if ( ! isset($_POST['spa_settings_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['spa_settings_nonce']), 'spa_save_settings') ) {
        wp_die('Invalid nonce', 'Error', array('response' => 403));
    }
    if ( ! current_user_can('manage_options') ) {
        wp_die('Unauthorized', 'Error', array('response' => 403));
    }

    $posted_tab = isset($_POST['active_tab']) ? sanitize_text_field(wp_unslash($_POST['active_tab'])) : 'general';


    if ( $posted_tab === 'general' ) {
        $org_name = isset($_POST['spa_org_name']) ? sanitize_text_field(wp_unslash($_POST['spa_org_name'])) : '';
        update_option('spa_org_name', $org_name);
    }

    if ( $posted_tab === 'email' ) {
        $notification_email = isset($_POST['spa_notification_email']) ? sanitize_email(wp_unslash($_POST['spa_notification_email'])) : '';
        $enable_email = isset($_POST['spa_enable_email']) ? 1 : 0;
        $email_provider = isset($_POST['spa_email_provider']) ? sanitize_text_field(wp_unslash($_POST['spa_email_provider'])) : 'wp_mail';

        update_option('spa_notification_email', $notification_email);
        update_option('spa_enable_email', $enable_email);
        update_option('spa_email_provider', $email_provider);

        // Provider-specific fields
        switch ( $email_provider ) {
            case 'smtp':
                $smtp_host = isset($_POST['spa_smtp_host']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_host'])) : '';
                $smtp_port = isset($_POST['spa_smtp_port']) ? intval($_POST['spa_smtp_port']) : 587;
                $smtp_user = isset($_POST['spa_smtp_user']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_user'])) : '';
                $smtp_pass = isset($_POST['spa_smtp_pass']) ? wp_unslash($_POST['spa_smtp_pass']) : '';
                $smtp_enc  = isset($_POST['spa_smtp_encryption']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_encryption'])) : 'tls';
                $smtp_from_address = isset($_POST['spa_smtp_from_address']) ? sanitize_email(wp_unslash($_POST['spa_smtp_from_address'])) : '';
                $smtp_from_name = isset($_POST['spa_smtp_from_name']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_from_name'])) : '';

                update_option('spa_smtp_host', $smtp_host);
                update_option('spa_smtp_port', $smtp_port);
                update_option('spa_smtp_user', $smtp_user);
                if ($smtp_pass !== '') update_option('spa_smtp_pass', $smtp_pass);
                update_option('spa_smtp_encryption', $smtp_enc);
                update_option('spa_smtp_from_address', $smtp_from_address);
                update_option('spa_smtp_from_name', $smtp_from_name);
                break;

            case 'sendgrid':
                $sendgrid_key = isset($_POST['spa_sendgrid_api_key']) ? sanitize_text_field(wp_unslash($_POST['spa_sendgrid_api_key'])) : '';
                $sendgrid_from_address = isset($_POST['spa_sendgrid_from']) ? sanitize_email(wp_unslash($_POST['spa_sendgrid_from'])) : '';
                $sendgrid_from_name = isset($_POST['spa_sendgrid_from_name']) ? sanitize_text_field(wp_unslash($_POST['spa_sendgrid_from_name'])) : '';
                if ($sendgrid_key !== '') update_option('spa_sendgrid_api_key', $sendgrid_key);
                update_option('spa_sendgrid_from', $sendgrid_from_address);
                update_option('spa_sendgrid_from_name', $sendgrid_from_name);
                break;

            case 'mailgun':
                $mailgun_key = isset($_POST['spa_mailgun_api_key']) ? sanitize_text_field(wp_unslash($_POST['spa_mailgun_api_key'])) : '';
                $mailgun_domain = isset($_POST['spa_mailgun_domain']) ? sanitize_text_field(wp_unslash($_POST['spa_mailgun_domain'])) : '';
                if ($mailgun_key !== '') update_option('spa_mailgun_api_key', $mailgun_key);
                update_option('spa_mailgun_domain', $mailgun_domain);
                break;

            case 'mailpoet':
                $mailpoet_list = isset($_POST['spa_mailpoet_list']) ? sanitize_text_field(wp_unslash($_POST['spa_mailpoet_list'])) : '';
                update_option('spa_mailpoet_list', $mailpoet_list);
                break;

            case 'ses':
                $ses_key = isset($_POST['spa_ses_key']) ? sanitize_text_field(wp_unslash($_POST['spa_ses_key'])) : '';
                $ses_secret = isset($_POST['spa_ses_secret']) ? wp_unslash($_POST['spa_ses_secret']) : '';
                $ses_region = isset($_POST['spa_ses_region']) ? sanitize_text_field(wp_unslash($_POST['spa_ses_region'])) : '';
                if ($ses_key !== '') update_option('spa_ses_key', $ses_key);
                if ($ses_secret !== '') update_option('spa_ses_secret', $ses_secret);
                update_option('spa_ses_region', $ses_region);
                break;

            case 'postmark':
                $postmark_token = isset($_POST['spa_postmark_token']) ? sanitize_text_field(wp_unslash($_POST['spa_postmark_token'])) : '';
                $postmark_from = isset($_POST['spa_postmark_from']) ? sanitize_email(wp_unslash($_POST['spa_postmark_from'])) : '';
                if ($postmark_token !== '') update_option('spa_postmark_token', $postmark_token);
                update_option('spa_postmark_from', $postmark_from);
                break;

            case 'mailersend':
                $mailersend_token = isset($_POST['spa_mailersend_token']) ? sanitize_text_field(wp_unslash($_POST['spa_mailersend_token'])) : '';
                $mailersend_from = isset($_POST['spa_mailersend_from']) ? sanitize_email(wp_unslash($_POST['spa_mailersend_from'])) : '';
                if ($mailersend_token !== '') update_option('spa_mailersend_token', $mailersend_token);
                update_option('spa_mailersend_from', $mailersend_from);
                break;

            default:
                break;
        }
    }

    if ( $posted_tab === 'sms' ) {
        $sms_provider = isset($_POST['spa_sms_provider']) ? sanitize_text_field(wp_unslash($_POST['spa_sms_provider'])) : '';
        $enable_sms = isset($_POST['spa_enable_sms']) ? 1 : 0;
        update_option('spa_sms_provider', $sms_provider);
        update_option('spa_enable_sms', $enable_sms);
    }

    if ( $posted_tab === 'push' ) {
        $enable_push = isset($_POST['spa_enable_push']) ? 1 : 0;
        update_option('spa_enable_push', $enable_push);
    }

    if ( $posted_tab === 'templates' ) {
        $example_template = isset($_POST['spa_example_template']) ? wp_kses_post(wp_unslash($_POST['spa_example_template'])) : '';
        update_option('spa_example_template', $example_template);
    }

    // If a test send was requested, send a test email and include result in the redirect
    $test_result = '';
    if ( isset($_POST['spa_send_test']) ) {
        $test_to = isset($_POST['spa_test_recipient']) ? sanitize_email(wp_unslash($_POST['spa_test_recipient'])) : '';
        if ( empty($test_to) ) {
            $test_result = 'missing_recipient';
        } else {
            $sent = spa_send_email($test_to, 'St. Paul\'s Admin - Test Email', '<p>This is a test email sent from the plugin to verify provider settings.</p>');
            if ( is_wp_error($sent) ) {
                $test_result = 'error:' . rawurlencode($sent->get_error_message());
            } else {
                $test_result = 'sent';
            }
        }
    }

    // Redirect back to avoid re-post on refresh
    $redirect_args = array('page' => 'spa-settings', 'tab' => $posted_tab, 'saved' => '1');
    if ( $test_result !== '' ) {
        $redirect_args['test'] = $test_result;
    }
    $redirect_url = add_query_arg($redirect_args, admin_url('admin.php'));
    wp_safe_redirect($redirect_url);
    exit;

}
add_action('admin_post_spa_save_settings', 'spa_handle_settings_post');


function spa_settings_admin_notices() {
    // Only show on the plugin settings page
    if ( ! isset($_GET['page']) || $_GET['page'] !== 'spa-settings' ) {
        return;
    }

    if ( isset($_GET['test']) ) {
        $test = wp_unslash($_GET['test']);
        if ( strpos($test, 'error:') === 0 ) {
            $msg = rawurldecode(substr($test, 6));
            echo '<div class="notice notice-error"><p>Test email failed: ' . esc_html($msg) . '</p></div>';
        } elseif ( $test === 'missing_recipient' ) {
            echo '<div class="notice notice-warning"><p>Test email not sent: recipient email missing.</p></div>';
        } elseif ( $test === 'sent' ) {
            echo '<div class="notice notice-success is-dismissible"><p>Test email sent successfully.</p></div>';
        } else {
            echo '<div class="notice notice-info"><p>Test result: ' . esc_html($test) . '</p></div>';
        }
    }
}
add_action('admin_notices', 'spa_settings_admin_notices');


/**
 * AJAX handler to save email settings and send test email without page reload.
 */
function spa_ajax_send_test_email() {
    check_ajax_referer('spa_admin_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error('Unauthorized', 403);
    }

    // Save basic email settings from POST
    $notification_email = isset($_POST['spa_notification_email']) ? sanitize_email(wp_unslash($_POST['spa_notification_email'])) : '';
    $enable_email = isset($_POST['spa_enable_email']) ? 1 : 0;
    $email_provider = isset($_POST['spa_email_provider']) ? sanitize_text_field(wp_unslash($_POST['spa_email_provider'])) : 'wp_mail';

    update_option('spa_notification_email', $notification_email);
    update_option('spa_enable_email', $enable_email);
    update_option('spa_email_provider', $email_provider);

    // Provider-specific saves (same rules as admin_post handler)
    switch ( $email_provider ) {
        case 'smtp':
            $smtp_host = isset($_POST['spa_smtp_host']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_host'])) : '';
            $smtp_port = isset($_POST['spa_smtp_port']) ? intval($_POST['spa_smtp_port']) : 587;
            $smtp_user = isset($_POST['spa_smtp_user']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_user'])) : '';
            $smtp_pass = isset($_POST['spa_smtp_pass']) ? wp_unslash($_POST['spa_smtp_pass']) : '';
            $smtp_enc  = isset($_POST['spa_smtp_encryption']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_encryption'])) : 'tls';
            $smtp_from_address = isset($_POST['spa_smtp_from_address']) ? sanitize_email(wp_unslash($_POST['spa_smtp_from_address'])) : '';
            $smtp_from_name = isset($_POST['spa_smtp_from_name']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_from_name'])) : '';

            update_option('spa_smtp_host', $smtp_host);
            update_option('spa_smtp_port', $smtp_port);
            update_option('spa_smtp_user', $smtp_user);
            if ($smtp_pass !== '') update_option('spa_smtp_pass', $smtp_pass);
            update_option('spa_smtp_encryption', $smtp_enc);
            update_option('spa_smtp_from_address', $smtp_from_address);
            update_option('spa_smtp_from_name', $smtp_from_name);
            break;

        case 'sendgrid':
            $sendgrid_key = isset($_POST['spa_sendgrid_api_key']) ? sanitize_text_field(wp_unslash($_POST['spa_sendgrid_api_key'])) : '';
            $sendgrid_from_address = isset($_POST['spa_sendgrid_from']) ? sanitize_email(wp_unslash($_POST['spa_sendgrid_from'])) : '';
            $sendgrid_from_name = isset($_POST['spa_sendgrid_from_name']) ? sanitize_text_field(wp_unslash($_POST['spa_sendgrid_from_name'])) : '';
            if ($sendgrid_key !== '') update_option('spa_sendgrid_api_key', $sendgrid_key);
            update_option('spa_sendgrid_from', $sendgrid_from_address);
            update_option('spa_sendgrid_from_name', $sendgrid_from_name);
            break;

        case 'mailgun':
            $mailgun_key = isset($_POST['spa_mailgun_api_key']) ? sanitize_text_field(wp_unslash($_POST['spa_mailgun_api_key'])) : '';
            $mailgun_domain = isset($_POST['spa_mailgun_domain']) ? sanitize_text_field(wp_unslash($_POST['spa_mailgun_domain'])) : '';
            if ($mailgun_key !== '') update_option('spa_mailgun_api_key', $mailgun_key);
            update_option('spa_mailgun_domain', $mailgun_domain);
            break;

        case 'mailpoet':
            $mailpoet_list = isset($_POST['spa_mailpoet_list']) ? sanitize_text_field(wp_unslash($_POST['spa_mailpoet_list'])) : '';
            update_option('spa_mailpoet_list', $mailpoet_list);
            break;

        case 'ses':
            $ses_key = isset($_POST['spa_ses_key']) ? sanitize_text_field(wp_unslash($_POST['spa_ses_key'])) : '';
            $ses_secret = isset($_POST['spa_ses_secret']) ? wp_unslash($_POST['spa_ses_secret']) : '';
            $ses_region = isset($_POST['spa_ses_region']) ? sanitize_text_field(wp_unslash($_POST['spa_ses_region'])) : '';
            if ($ses_key !== '') update_option('spa_ses_key', $ses_key);
            if ($ses_secret !== '') update_option('spa_ses_secret', $ses_secret);
            update_option('spa_ses_region', $ses_region);
            break;

        case 'postmark':
            $postmark_token = isset($_POST['spa_postmark_token']) ? sanitize_text_field(wp_unslash($_POST['spa_postmark_token'])) : '';
            $postmark_from = isset($_POST['spa_postmark_from']) ? sanitize_email(wp_unslash($_POST['spa_postmark_from'])) : '';
            if ($postmark_token !== '') update_option('spa_postmark_token', $postmark_token);
            update_option('spa_postmark_from', $postmark_from);
            break;

        case 'mailersend':
            $mailersend_token = isset($_POST['spa_mailersend_token']) ? sanitize_text_field(wp_unslash($_POST['spa_mailersend_token'])) : '';
            $mailersend_from = isset($_POST['spa_mailersend_from']) ? sanitize_email(wp_unslash($_POST['spa_mailersend_from'])) : '';
            if ($mailersend_token !== '') update_option('spa_mailersend_token', $mailersend_token);
            update_option('spa_mailersend_from', $mailersend_from);
            break;

        default:
            break;
    }

    // Perform the test send
    $test_to = isset($_POST['spa_test_recipient']) ? sanitize_email(wp_unslash($_POST['spa_test_recipient'])) : '';
    if ( empty($test_to) ) {
        wp_send_json_error('missing_recipient');
    }
    $sent = spa_send_email($test_to, 'St. Paul\'s Admin - Test Email', '<p>This is a test email sent from the plugin to verify provider settings.</p>');
    if ( is_wp_error($sent) ) {
        wp_send_json_error($sent->get_error_message());
    }
    wp_send_json_success('sent');
}
add_action('wp_ajax_spa_send_test_email', 'spa_ajax_send_test_email');


function spa_settings_page() {

    $active_tab = isset($_REQUEST['tab'])
        ? sanitize_text_field(wp_unslash($_REQUEST['tab']))
        : 'general';

    $page_title = "Settings";
    include SPA_TEMPLATE_DIR . 'settings-page.php';

}


