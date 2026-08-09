<?php
/**
 * Uninstall handler for St. Paul's Admin plugin
 * Removes plugin-created database tables and user meta.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$tables = array(
    $wpdb->prefix . 'spa_event_volunteers',
    $wpdb->prefix . 'spa_events_teams',
    $wpdb->prefix . 'spa_events',
    $wpdb->prefix . 'spa_volunteer_teams',
    $wpdb->prefix . 'spa_volunteers',
    $wpdb->prefix . 'spa_teams',
    $wpdb->prefix . 'spa_notification_templates',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// Remove user meta entries created by the plugin (delete across all users)
if ( function_exists( 'delete_metadata' ) ) {
    // delete_metadata( $meta_type, $object_id, $meta_key, $meta_value = '', $delete_all = false )
    delete_metadata( 'user', 0, 'spa_dashboard_order', '', true );
}

// Clean up any plugin-specific transients
if ( isset( $wpdb->options ) ) {
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_spa_%'" );
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_spa_%'" );
}

// Remove plugin options
delete_option('spa_active_email_template');
delete_option('spa_active_sms_template');
delete_option('spa_db_version');
delete_option('spa_notification_email');
delete_option('spa_enable_email');
delete_option('spa_sms_provider');
delete_option('spa_enable_sms');

// End of uninstall
