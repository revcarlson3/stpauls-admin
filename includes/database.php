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
        special_day varchar(100) NULL,
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

    // Services foundation. A service is intentionally a one-to-one extension
    // of an event so the records can later be exposed through a public API.
    $preachers_table = $wpdb->prefix . 'spa_preachers';
    dbDelta("CREATE TABLE $preachers_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        bio text NULL,
        active tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY active_name (active, name)
    ) $charset_collate;");

    $series_table = $wpdb->prefix . 'spa_sermon_series';
    dbDelta("CREATE TABLE $series_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text NULL,
        active tinyint(1) NOT NULL DEFAULT 1,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY active_name (active, name)
    ) $charset_collate;");

    $services_table = $wpdb->prefix . 'spa_services';
    dbDelta("CREATE TABLE $services_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        event_id bigint(20) unsigned NOT NULL,
        sermon_title varchar(255) NOT NULL DEFAULT '',
        sermon_text longtext NULL,
        sermon_file_id bigint(20) unsigned NULL,
        sermon_file_url varchar(500) NULL,
        bible_translation varchar(100) NOT NULL DEFAULT '',
        video_url varchar(500) NULL,
        audio_file_id bigint(20) unsigned NULL,
        audio_file_url varchar(500) NULL,
        bulletin_file_id bigint(20) unsigned NULL,
        bulletin_file_url varchar(500) NULL,
        preacher_id bigint(20) unsigned NULL,
        series_id bigint(20) unsigned NULL,
        featured_image_id bigint(20) unsigned NULL,
        active tinyint(1) NOT NULL DEFAULT 1,
        created_by bigint(20) unsigned NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY event_id (event_id),
        KEY active_event (active, event_id),
        KEY preacher_id (preacher_id),
        KEY series_id (series_id)
    ) $charset_collate;");

    $lessons_table = $wpdb->prefix . 'spa_service_lessons';
    dbDelta("CREATE TABLE $lessons_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        service_id bigint(20) unsigned NOT NULL,
        reference varchar(255) NOT NULL,
        link_url varchar(500) NULL,
        lesson_order smallint(5) unsigned NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY service_order (service_id, lesson_order)
    ) $charset_collate;");

    $tags_table = $wpdb->prefix . 'spa_service_tags';
    dbDelta("CREATE TABLE $tags_table (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;");

    $tag_rel_table = $wpdb->prefix . 'spa_service_tag_relationships';
    dbDelta("CREATE TABLE $tag_rel_table (
        service_id bigint(20) unsigned NOT NULL,
        tag_id bigint(20) unsigned NOT NULL,
        PRIMARY KEY (service_id, tag_id),
        KEY tag_id (tag_id)
    ) $charset_collate;");
}
?>