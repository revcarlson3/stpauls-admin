<?php

function spa_public_get_calendar_events() {
    global $wpdb;

    $events = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT e.id, e.name, e.season, e.special_day, e.event_date, e.start_time, e.end_time, e.description, e.location,
                    s.id AS service_id
             FROM {$wpdb->prefix}spa_events e
             LEFT JOIN {$wpdb->prefix}spa_services s ON s.event_id = e.id AND s.active = 1
             WHERE e.active = 1
               AND e.event_date >= %s
             ORDER BY e.event_date, e.start_time, e.id",
            current_time('Y-m-d')
        )
    );

    foreach ( $events as $event ) {
        $event->church_day = spa_get_church_year_day($event->event_date, $event->special_day, $event->season);
        $event->service_url = $event->service_id
            ? add_query_arg('service_id', intval($event->service_id), spa_services_get_details_url())
            : '';
    }

    return $events;
}

function spa_public_get_sermons() {
    global $wpdb;

    return $wpdb->get_results(
        "SELECT s.*, e.name AS event_name, e.event_date, p.name AS preacher_name
         FROM {$wpdb->prefix}spa_services s
         INNER JOIN {$wpdb->prefix}spa_events e ON e.id = s.event_id
         LEFT JOIN {$wpdb->prefix}spa_preachers p ON p.id = s.preacher_id
         WHERE s.active = 1
           AND e.active = 1
         ORDER BY e.event_date DESC, e.start_time DESC, s.id DESC"
    );
}

function spa_public_get_sermon($service_id = 0) {
    global $wpdb;

    $service_filter = $service_id ? $wpdb->prepare('AND s.id = %d', $service_id) : '';

    return $wpdb->get_row(
        "SELECT s.*, e.name AS event_name, e.event_date, e.start_time,
                p.name AS preacher_name, ss.name AS series_name
         FROM {$wpdb->prefix}spa_services s
         INNER JOIN {$wpdb->prefix}spa_events e ON e.id = s.event_id
         LEFT JOIN {$wpdb->prefix}spa_preachers p ON p.id = s.preacher_id
         LEFT JOIN {$wpdb->prefix}spa_sermon_series ss ON ss.id = s.series_id
         WHERE s.active = 1
           AND e.active = 1
           {$service_filter}
         ORDER BY e.event_date DESC, e.start_time DESC, s.id DESC
         LIMIT 1"
    );
}

function spa_public_get_sermon_lessons($service_id) {
    global $wpdb;

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT reference FROM {$wpdb->prefix}spa_service_lessons
             WHERE service_id = %d
             ORDER BY lesson_order, id",
            $service_id
        )
    );
}

function spa_public_get_sermon_hymns($service_id) {
    global $wpdb;

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spa_service_hymns WHERE service_id = %d ORDER BY hymn_order, id",
            $service_id
        )
    );
}

function spa_public_get_related_sermons($service_id) {
    global $wpdb;

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT s.*, e.name AS event_name, e.event_date, p.name AS preacher_name,
                    COUNT(DISTINCT matching_rel.tag_id) AS matching_tags
             FROM {$wpdb->prefix}spa_services s
             INNER JOIN {$wpdb->prefix}spa_events e ON e.id = s.event_id
             LEFT JOIN {$wpdb->prefix}spa_preachers p ON p.id = s.preacher_id
             INNER JOIN {$wpdb->prefix}spa_service_tag_relationships matching_rel
                ON matching_rel.service_id = s.id
             INNER JOIN {$wpdb->prefix}spa_service_tag_relationships current_rel
                ON current_rel.tag_id = matching_rel.tag_id
               AND current_rel.service_id = %d
             WHERE s.active = 1
               AND e.active = 1
               AND s.id <> %d
             GROUP BY s.id
             ORDER BY matching_tags DESC, e.event_date DESC, s.id DESC
             LIMIT 3",
            $service_id,
            $service_id
        )
    );
}
