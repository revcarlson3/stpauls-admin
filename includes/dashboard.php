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
            'upcoming-swaps',
            'communications',
            'recent-activity',
        );
    }

    $dashboard_cards = array(
        'upcoming-events'  => array('title' => 'Upcoming Events'),
        'upcoming-swaps'   => array('title' => 'Upcoming Swaps'),
        'communications'   => array('title' => 'Communications'),
        'recent-activity'  => array('title' => 'Recent Activity'),
    );
    if ( ! in_array('upcoming-swaps', $dashboard_order, true) ) {
        $dashboard_order[] = 'upcoming-swaps';
    }

    $upcoming_events = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
            e.id,
            e.name,
            e.event_date,
            e.start_time,
            e.end_time,
            s.id AS service_id,
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
         LEFT JOIN {$wpdb->prefix}spa_services s ON s.event_id = e.id AND s.active = 1
         WHERE e.active = 1
         AND e.event_date >= %s
         ORDER BY e.event_date, e.start_time, e.id
         LIMIT 8",
            current_time('Y-m-d')
        )
    );

    $upcoming_swaps = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                sr.swap_date,
                t.name AS team_name,
                CONCAT(sv.first_name, ' ', sv.last_name) AS original_volunteer,
                CONCAT(rv.first_name, ' ', rv.last_name) AS swapped_volunteer,
                (
                    SELECT e.name
                    FROM {$wpdb->prefix}spa_events e
                    WHERE e.active = 1
                    AND e.event_date = sr.swap_date
                    ORDER BY e.start_time, e.id
                    LIMIT 1
                ) AS event_name
             FROM {$wpdb->prefix}spa_swap_reminders sr
             INNER JOIN {$wpdb->prefix}spa_teams t
                 ON t.id = sr.team_id
             INNER JOIN {$wpdb->prefix}spa_volunteers sv
                 ON sv.id = sr.scheduled_volunteer_id
             INNER JOIN {$wpdb->prefix}spa_volunteers rv
                 ON rv.id = sr.replacement_volunteer_id
             WHERE sr.status = 'pending'
             AND sr.swap_date >= %s
             ORDER BY sr.swap_date, sv.last_name, sv.first_name
             LIMIT 12",
            current_time('Y-m-d')
        )
    );

    $communication_deliveries = $wpdb->get_results(
        "SELECT channel, created_at, volunteer_name, status, failure_reason
         FROM {$wpdb->prefix}spa_notification_delivery_logs
         ORDER BY created_at DESC, id DESC
         LIMIT 8"
    );

    $activity_filter = isset($_GET['activity_filter']) ? sanitize_key(wp_unslash($_GET['activity_filter'])) : 'all';
    $allowed_activity_filters = array('all', 'events', 'services', 'volunteers', 'communications', 'scheduling', 'system');
    if ( ! in_array($activity_filter, $allowed_activity_filters, true) ) {
        $activity_filter = 'all';
    }
    $category_sql = $activity_filter === 'all' ? '' : $wpdb->prepare('AND a.category = %s', $activity_filter);
    $recent_activity = $wpdb->get_results(
        "SELECT a.created_at AS activity_time, a.category, a.description, e.name AS event_name
         FROM {$wpdb->prefix}spa_activity a
         LEFT JOIN {$wpdb->prefix}spa_events e ON e.id = a.event_id
         WHERE a.created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 90 DAY)
         {$category_sql}
         UNION ALL
         SELECT log.created_at AS activity_time, 'communications' AS category,
                CONCAT('Notification sent via ', log.channel) AS description, e.name AS event_name
         FROM {$wpdb->prefix}spa_notification_delivery_logs log
         LEFT JOIN {$wpdb->prefix}spa_events e ON e.id = log.event_id
         WHERE log.created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 90 DAY)
           AND log.status IN ('sent', 'delivered')
           " . ($activity_filter === 'all' || $activity_filter === 'communications' ? '' : 'AND 1 = 0') . "
         ORDER BY activity_time DESC
         LIMIT 8"
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