<?php

add_shortcode('spa_events_calendar', 'spa_events_calendar_shortcode');
add_action('wp_ajax_spa_calendar_event_details', 'spa_calendar_event_details_ajax');

function spa_events_calendar_shortcode($atts) {
    $atts = shortcode_atts(array('view' => 'month'), $atts, 'spa_events_calendar');
    $events = spa_public_get_calendar_events();

    wp_enqueue_style('spa-public-calendar', SPA_PLUGIN_URL . 'css/spa_calendar.css', array(), SPA_VERSION);
    wp_enqueue_script('spa-public-calendar', SPA_PLUGIN_URL . 'js/spa_calendar.js', array(), SPA_VERSION, true);
    wp_localize_script('spa-public-calendar', 'spaCalendar', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('spa_calendar_nonce'),
        'isLoggedIn' => is_user_logged_in(),
        'defaultView' => in_array($atts['view'], array('month', 'week', 'agenda'), true) ? $atts['view'] : 'month',
        'today' => current_time('Y-m-d'),
        'strings' => array(
            'noEvents' => 'No events scheduled.',
            'loading' => 'Loading assignments.',
            'error' => 'Unable to load event assignments.',
        ),
    ));

    return sprintf(
        '<div class="spa-public-calendar" data-events="%s"></div>',
        esc_attr(wp_json_encode($events))
    );
}

function spa_calendar_event_details_ajax() {
    global $wpdb;

    if ( ! is_user_logged_in() ) {
        wp_send_json_error(array('message' => 'You must be logged in to view assignments.'), 403);
    }
    if ( ! check_ajax_referer('spa_calendar_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid request.'), 403);
    }

    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    if ( $event_id < 1 ) {
        wp_send_json_error(array('message' => 'Invalid event.'));
    }

    $event_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}spa_events WHERE id = %d AND active = 1",
        $event_id
    ));
    if ( ! $event_exists ) {
        wp_send_json_error(array('message' => 'Event not found.'));
    }

    $teams = $wpdb->get_results($wpdb->prepare(
        "SELECT t.name AS team_name, et.volunteers_needed,
                GROUP_CONCAT(DISTINCT CONCAT(v.first_name, ' ', v.last_name)
                    ORDER BY v.last_name, v.first_name SEPARATOR ', ') AS volunteer_names
         FROM {$wpdb->prefix}spa_events_teams et
         INNER JOIN {$wpdb->prefix}spa_teams t ON t.id = et.team_id AND t.active = 1
         LEFT JOIN {$wpdb->prefix}spa_event_volunteers ev
            ON ev.event_id = et.event_id AND ev.team_id = et.team_id
         LEFT JOIN {$wpdb->prefix}spa_volunteers v
            ON v.id = ev.volunteer_id AND v.active = 1
         WHERE et.event_id = %d
         GROUP BY t.id, t.name, et.volunteers_needed
         ORDER BY t.name",
        $event_id
    ));

    ob_start();
    ?>
    <div class="spa-calendar-assignment-details">
        <h4>Scheduled teams and volunteers</h4>
        <?php if ( ! empty($teams) ) : ?>
            <ul>
                <?php foreach ( $teams as $team ) : ?>
                    <li>
                        <strong><?php echo esc_html($team->team_name); ?></strong>
                        <?php if ( ! empty($team->volunteer_names) ) : ?>
                            <span><?php echo esc_html($team->volunteer_names); ?></span>
                        <?php else : ?>
                            <span class="spa-calendar-unassigned">Volunteer needed</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p>No teams have been scheduled for this event.</p>
        <?php endif; ?>
    </div>
    <?php
    wp_send_json_success(array('html' => ob_get_clean()));
}
