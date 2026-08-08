<?php

function spa_settings_page() {

    $active_tab = isset($_REQUEST['tab'])
        ? sanitize_text_field(wp_unslash($_REQUEST['tab']))
        : 'general';

    // Handle saves
    if ( isset($_POST['spa_settings_nonce']) && wp_verify_nonce(wp_unslash($_POST['spa_settings_nonce']), 'spa_save_settings') ) {
        if ( ! current_user_can('manage_options') ) {
            wp_die('Unauthorized', 'Error', array('response' => 403));
        }

        // Save fields based on tab
        $posted_tab = isset($_POST['active_tab']) ? sanitize_text_field(wp_unslash($_POST['active_tab'])) : 'general';

        if ( $posted_tab === 'general' ) {
            $org_name = isset($_POST['spa_org_name']) ? sanitize_text_field(wp_unslash($_POST['spa_org_name'])) : '';
            update_option('spa_org_name', $org_name);
        }

        if ( $posted_tab === 'email' ) {
            $notification_email = isset($_POST['spa_notification_email']) ? sanitize_email(wp_unslash($_POST['spa_notification_email'])) : '';
            $enable_email = isset($_POST['spa_enable_email']) ? 1 : 0;
            update_option('spa_notification_email', $notification_email);
            update_option('spa_enable_email', $enable_email);
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

        // Redirect back to avoid re-post on refresh
        $redirect_url = add_query_arg(array('page' => 'spa-settings', 'tab' => $posted_tab, 'saved' => '1'), admin_url('admin.php'));
        wp_safe_redirect($redirect_url);
        exit;
    }

    $page_title = "Settings";
    include SPA_TEMPLATE_DIR . 'settings-page.php';

}
