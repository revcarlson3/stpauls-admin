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

// End of uninstall
