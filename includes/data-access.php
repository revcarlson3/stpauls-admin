<?php

function spa_get_active_team_volunteers($team_id) {
    global $wpdb;

    $team_id = intval($team_id);
    if ( ! $team_id ) {
        return array();
    }

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT v.id, v.first_name, v.last_name
             FROM {$wpdb->prefix}spa_volunteer_teams vt
             INNER JOIN {$wpdb->prefix}spa_volunteers v ON v.id = vt.volunteer_id
             WHERE vt.team_id = %d AND v.active = 1
             ORDER BY v.last_name, v.first_name",
            $team_id
        )
    );
}

function spa_are_active_team_volunteers($team_id, $volunteer_ids) {
    $volunteer_ids = array_values(array_unique(array_filter(array_map('intval', (array) $volunteer_ids))));
    if ( ! intval($team_id) || empty($volunteer_ids) ) {
        return false;
    }

    $team_volunteers = spa_get_active_team_volunteers($team_id);
    $team_volunteer_ids = array_map(
        'intval',
        wp_list_pluck($team_volunteers, 'id')
    );

    return count(array_diff($volunteer_ids, $team_volunteer_ids)) === 0;
}

function spa_get_event_by_id($event_id, $active_only = false) {
    global $wpdb;

    $event_id = intval($event_id);
    if ( ! $event_id ) {
        return null;
    }

    $active_sql = $active_only ? ' AND active = 1' : '';
    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT *
             FROM {$wpdb->prefix}spa_events
             WHERE id = %d{$active_sql}",
            $event_id
        )
    );
}

function spa_get_event_assignments($event_id) {
    global $wpdb;

    $event_id = intval($event_id);
    if ( ! $event_id ) {
        return array();
    }

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                ev.team_id,
                ev.volunteer_id,
                ev.is_override,
                t.name AS team_name,
                t.active AS team_active,
                CONCAT(v.first_name, ' ', v.last_name) AS volunteer_name,
                v.active AS volunteer_active
             FROM {$wpdb->prefix}spa_event_volunteers ev
             INNER JOIN {$wpdb->prefix}spa_teams t ON t.id = ev.team_id
             INNER JOIN {$wpdb->prefix}spa_volunteers v ON v.id = ev.volunteer_id
             WHERE ev.event_id = %d
             ORDER BY t.name, v.last_name, v.first_name",
            $event_id
        )
    );
}

function spa_get_pending_swap_reminders_for_event($event_id, $event_date) {
    global $wpdb;

    $event_id = intval($event_id);
    if ( ! $event_id || ! $event_date ) {
        return array();
    }

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                sr.*,
                e.event_date,
                t.name AS team_name,
                CONCAT(sv.first_name, ' ', sv.last_name) AS scheduled_volunteer_name,
                CONCAT(rv.first_name, ' ', rv.last_name) AS replacement_volunteer_name,
                rv.first_name AS replacement_first_name,
                rv.last_name AS replacement_last_name
             FROM {$wpdb->prefix}spa_swap_reminders sr
             INNER JOIN {$wpdb->prefix}spa_events e ON e.id = %d
             INNER JOIN {$wpdb->prefix}spa_events_teams et
                 ON et.event_id = e.id AND et.team_id = sr.team_id
             INNER JOIN {$wpdb->prefix}spa_teams t ON t.id = sr.team_id
             INNER JOIN {$wpdb->prefix}spa_volunteers sv ON sv.id = sr.scheduled_volunteer_id
             INNER JOIN {$wpdb->prefix}spa_volunteers rv ON rv.id = sr.replacement_volunteer_id
             WHERE sr.status = 'pending' AND sr.swap_date = %s
             ORDER BY t.name, sv.last_name, sv.first_name",
            $event_id,
            $event_date
        )
    );
}

function spa_get_active_rotation_rows($service_type_id, $team_id) {
    global $wpdb;

    $service_type_id = intval($service_type_id);
    $team_id = intval($team_id);
    if ( ! $service_type_id || ! $team_id ) {
        return array();
    }

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                r.id,
                r.volunteer_id,
                r.rotation_order,
                r.is_next,
                r.advance_rule,
                v.first_name,
                v.last_name
             FROM {$wpdb->prefix}spa_team_rotations r
             INNER JOIN {$wpdb->prefix}spa_volunteers v
                 ON v.id = r.volunteer_id AND v.active = 1
             WHERE r.service_type_id = %d AND r.team_id = %d
             ORDER BY r.rotation_order",
            $service_type_id,
            $team_id
        )
    );
}
