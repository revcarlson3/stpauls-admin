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

$dashboard_order = get_user_meta(
    get_current_user_id(),
    'spa_dashboard_order',
    true
);

if (empty($dashboard_order)) {

    $dashboard_order = array(
        'volunteer-alerts',
        'upcoming-events',
        'quick-statistics',
        'communications',
        'recent-activity',
        'future'
    );

}

$dashboard_cards = array(
    'volunteer-alerts' => array('title' => 'Volunteer Alerts'),
    'upcoming-events'  => array('title' => 'Upcoming Events'),
    'quick-statistics' => array('title' => 'Quick Statistics'),
    'communications'   => array('title' => 'Communications'),
    'recent-activity'  => array('title' => 'Recent Activity'),
    'future'           => array('title' => 'Future'),
);

function spa_dashboard_page() {

    global $wpdb;

    $sunday_service = $wpdb->get_row(
        "SELECT *
         FROM {$wpdb->prefix}spa_events
         WHERE event_date >= CURDATE()
         ORDER BY event_date
         LIMIT 1"
    );
    $sunday_teams = array();
    $open_volunteer_needs = 0;

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
    }

    // Page title
    $page_title = "Dashboard";
    include SPA_TEMPLATE_DIR .'dashboard-page.php';
}