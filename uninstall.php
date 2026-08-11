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
    $wpdb->prefix . 'spa_service_tag_relationships',
    $wpdb->prefix . 'spa_service_tags',
    $wpdb->prefix . 'spa_service_lessons',
    $wpdb->prefix . 'spa_service_hymns',
    $wpdb->prefix . 'spa_services',
    $wpdb->prefix . 'spa_sermon_series',
    $wpdb->prefix . 'spa_preachers',
    $wpdb->prefix . 'spa_event_volunteers',
    $wpdb->prefix . 'spa_events_teams',
    $wpdb->prefix . 'spa_events',
    $wpdb->prefix . 'spa_volunteer_teams',
    $wpdb->prefix . 'spa_volunteers',
    $wpdb->prefix . 'spa_teams',
    $wpdb->prefix . 'spa_notification_templates',
    $wpdb->prefix . 'spa_notification_delivery_logs',
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
delete_option('spa_biblegateway_api_key');
delete_option('spa_biblegateway_api_secret');
delete_option('spa_biblegateway_translations');
delete_option('spa_reftagger_translations');
delete_option('spa_sermons_page_id');
delete_option('spa_sermon_details_page_id');

// End of uninstall
