<?php
// Activate plugin and create tables
function spa_activate_plugin() {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $charset_collate = $wpdb->get_charset_collate();

    // Teams table
    $teams_table = $wpdb->prefix . 'spa_teams';

    $sql = "CREATE TABLE $teams_table (
         id mediumint(9) NOT NULL AUTO_INCREMENT,
         name varchar(255) NOT NULL,
         description text NULL,
         active tinyint(1) NOT NULL DEFAULT 1,
         created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
         PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);

    // Volunteers table
    $volunteers_table = $wpdb->prefix .'spa_volunteers';

    $sql = "CREATE TABLE $volunteers_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        first_name varchar(100) NOT NULL,
        last_name varchar(100) NOT NULL,
        email varchar(255) NULL,
        phone varchar(50) NULL,
        email_enabled tinyint(1) NOT NULL DEFAULT 1,
        phone_enabled tinyint(1) NOT NULL DEFAULT 1,
        active tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    dbDelta($sql);

    // Volunteer-Teams Table
    $volunteer_teams_table = $wpdb->prefix .'spa_volunteer_teams';

    $sql = "CREATE TABLE $volunteer_teams_table (
        volunteer_id mediumint(9) NOT NULL,
        team_id mediumint(9) NOT NULL,
        PRIMARY KEY (volunteer_id, team_id)
    ) $charset_collate;";

    dbDelta($sql);

    // Events Table
    $events_table = $wpdb->prefix .'spa_events';

    $sql = "CREATE TABLE $events_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        season varchar(30) NULL,
        event_date date NOT NULL,
        start_time time NOT NULL,
        end_time time NOT NULL,
        description text NULL,
        location varchar(255) NULL,
        service_builder_url varchar(500) NULL,
        service_type_id mediumint(9) NULL,
        is_recurring tinyint(1) NOT NULL DEFAULT 0,
        recurrence_type varchar(20) NULL,
        recurrence_end_date date NULL,
        parent_event_id mediumint(9) NULL,
        notify_volunteers tinyint(1) NOT NULL DEFAULT 0,
        active tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);

    $events_teams_table = $wpdb->prefix .'spa_events_teams';

    $sql = "CREATE TABLE $events_teams_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NOT NULL,
        team_id mediumint(9) NOT NULL,
        volunteers_needed mediumint(3) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);

    $event_volunteers_table = $wpdb->prefix .'spa_event_volunteers';

    $sql = "CREATE TABLE $event_volunteers_table (
        event_id mediumint(9) NOT NULL,
        team_id mediumint(9) NOT NULL,
        volunteer_id mediumint(9) NOT NULL,
        is_override tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (event_id, team_id, volunteer_id)
        ) $charset_collate;";
    
    dbDelta($sql);

    $service_types_table = $wpdb->prefix . 'spa_service_types';

    $sql = "CREATE TABLE $service_types_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text NULL,
        active tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);

    $rotation_table = $wpdb->prefix . 'spa_team_rotations';

    $sql = "CREATE TABLE $rotation_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        service_type_id mediumint(9) NOT NULL,
        team_id mediumint(9) NOT NULL,
        volunteer_id mediumint(9) NOT NULL,
        rotation_order mediumint(9) NOT NULL DEFAULT 0,
        is_next tinyint(1) NOT NULL DEFAULT 0,
        advance_rule varchar(20) NOT NULL DEFAULT 'every_event',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);

    $delivery_logs_table = $wpdb->prefix . 'spa_notification_delivery_logs';

    $sql = "CREATE TABLE $delivery_logs_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        event_id mediumint(9) NULL,
        volunteer_id mediumint(9) NULL,
        volunteer_name varchar(255) NOT NULL,
        channel varchar(10) NOT NULL,
        provider varchar(30) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        provider_message_id varchar(191) NULL,
        failure_reason text NULL,
        failed_at datetime NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY status_failed_at (status, failed_at),
        KEY provider_message_id (provider_message_id)
    ) $charset_collate;";

    dbDelta($sql);

    // Notification Templates Table
    $templates_table = $wpdb->prefix . 'spa_notification_templates';

    $sql = "CREATE TABLE $templates_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        type varchar(10) NOT NULL,
        subject varchar(255) NULL,
        body longtext NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);
}
?>