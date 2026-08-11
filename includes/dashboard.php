<?php
add_action(
    'wp_ajax_spa_save_dashboard_order',
    'spa_save_dashboard_order_ajax'
);

function spa_save_dashboard_order_ajax() {

    if (! check_ajax_referer('spa_admin_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if (! current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $user_id = get_current_user_id();

    $order = isset($_POST['order'])
        ? array_map(
            'sanitize_text_field',
            wp_unslash($_POST['order'])
        )
        : array();

    update_user_meta(
        $user_id,
        'spa_dashboard_order',
        $order
    );

    wp_send_json_success(
        array(
            'user_id' => get_current_user_id(),
            'order' => $order
        )
    );
}

function spa_dashboard_page() {

    if ( ! current_user_can('manage_options') ) {
        wp_die('Unauthorized', 'Error', array('response' => 403));
    }

    global $wpdb;

    $dashboard_order = get_user_meta(get_current_user_id(), 'spa_dashboard_order', true);

    if ( empty($dashboard_order) ) {
        $dashboard_order = array(
            'upcoming-events',
            'communications',
            'recent-activity',
        );
    }

    $dashboard_cards = array(
        'upcoming-events'  => array('title' => 'Upcoming Events'),
        'communications'   => array('title' => 'Communications'),
        'recent-activity'  => array('title' => 'Recent Activity'),
    );

    $upcoming_events = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
            e.id,
            e.name,
            e.event_date,
            e.start_time,
            e.end_time,
            (
                SELECT COUNT(*)
                FROM {$wpdb->prefix}spa_event_volunteers ev
                WHERE ev.event_id = e.id
            ) AS assigned_count,
            (
                SELECT GROUP_CONCAT(DISTINCT conflicting.name ORDER BY conflicting.start_time, conflicting.id SEPARATOR ', ')
                FROM {$wpdb->prefix}spa_events conflicting
                WHERE conflicting.active = 1
                AND conflicting.id <> e.id
                AND conflicting.event_date = e.event_date
                AND conflicting.start_time < e.end_time
                AND conflicting.end_time > e.start_time
            ) AS conflict_names
         FROM {$wpdb->prefix}spa_events e
         WHERE e.active = 1
         AND e.event_date >= %s
         ORDER BY e.event_date, e.start_time, e.id
         LIMIT 10",
            current_time('Y-m-d')
        )
    );

    $communication_failures = $wpdb->get_results(
        "SELECT channel, failed_at, volunteer_name, failure_reason
         FROM {$wpdb->prefix}spa_notification_delivery_logs
         WHERE status = 'failed'
         ORDER BY failed_at DESC, id DESC
         LIMIT 2"
    );

    $recent_activity = $wpdb->get_results(
        "SELECT
            DATE_FORMAT(log.created_at, '%Y-%m-%d %H:%i:00') AS activity_time,
            log.event_id,
            MAX(e.name) AS event_name,
            COUNT(CASE WHEN log.channel = 'email' THEN 1 END) AS email_count,
            COUNT(CASE WHEN log.channel = 'sms' THEN 1 END) AS sms_count,
            COUNT(*) AS total_count
         FROM {$wpdb->prefix}spa_notification_delivery_logs log
         LEFT JOIN {$wpdb->prefix}spa_events e ON e.id = log.event_id
         WHERE log.created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 30 DAY)
           AND log.status IN ('sent', 'delivered')
         GROUP BY log.event_id, DATE_FORMAT(log.created_at, '%Y-%m-%d %H:%i:00')
         ORDER BY activity_time DESC
         LIMIT 10"
    );

    $sunday_service = $wpdb->get_row(
        "SELECT e.*, s.id AS service_id
         FROM {$wpdb->prefix}spa_events e
         LEFT JOIN {$wpdb->prefix}spa_services s ON s.event_id = e.id AND s.active = 1
         WHERE e.active = 1
         AND e.event_date >= CURDATE()
         ORDER BY e.event_date
         LIMIT 1"
    );
    $sunday_teams = array();
    $open_volunteer_needs = 0;
    $duplicate_assignment_alerts = array();

    if ($sunday_service) {

        $teams = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    t.id,
                    t.name,
                    et.volunteers_needed
                 FROM {$wpdb->prefix}spa_events_teams et
                 INNER JOIN {$wpdb->prefix}spa_teams t
                     ON et.team_id = t.id
                    AND t.active = 1
                 WHERE et.event_id = %d",
                $sunday_service->id
            )
        );

        foreach ($teams as $team) {

            $assigned = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                     FROM {$wpdb->prefix}spa_event_volunteers
                     WHERE event_id = %d
                     AND team_id = %d",
                    $sunday_service->id,
                    $team->id
                )
            );

            $volunteers = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
                        v.first_name,
                        v.last_name
                     FROM {$wpdb->prefix}spa_event_volunteers ev
                     INNER JOIN {$wpdb->prefix}spa_volunteers v
                        ON ev.volunteer_id = v.id
                     WHERE ev.event_id = %d
                     AND ev.team_id = %d
                     ORDER BY v.last_name, v.first_name",
                    $sunday_service->id,
                    $team->id
                )
            );

            $needed = intval($team->volunteers_needed);

            $open_volunteer_needs += max(
                0,
                $needed - $assigned
            );

            $sunday_teams[] = array(
                'name'       => $team->name,
                'assigned'   => $assigned,
                'needed'     => $needed,
                'volunteers' => $volunteers
            );
        }

        $duplicate_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    ev.volunteer_id,
                    v.first_name,
                    v.last_name,
                    COUNT(DISTINCT ev.team_id) AS team_count,
                    GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS team_names
                 FROM {$wpdb->prefix}spa_event_volunteers ev
                 INNER JOIN {$wpdb->prefix}spa_volunteers v
                    ON ev.volunteer_id = v.id
                    AND v.active = 1
                 INNER JOIN {$wpdb->prefix}spa_teams t
                    ON ev.team_id = t.id
                    AND t.active = 1
                 WHERE ev.event_id = %d
                 GROUP BY ev.volunteer_id, v.first_name, v.last_name
                 HAVING COUNT(DISTINCT ev.team_id) > 1
                 ORDER BY v.last_name, v.first_name",
                $sunday_service->id
            )
        );

        foreach ( $duplicate_rows as $duplicate_row ) {
            $duplicate_assignment_alerts[] = array(
                'event_name'      => $sunday_service->name,
                'volunteer_name'  => trim($duplicate_row->first_name . ' ' . $duplicate_row->last_name),
                'team_names'      => $duplicate_row->team_names,
                'team_count'      => intval($duplicate_row->team_count),
            );
        }
    }

    // Page title
    $page_title = "Dashboard";
    include SPA_TEMPLATE_DIR .'dashboard-page.php';
}