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

function spa_get_event_teams($event_id, $active_only = true) {
    global $wpdb;

    $event_id = intval($event_id);
    if ( ! $event_id ) {
        return array();
    }

    $active_sql = $active_only ? ' AND t.active = 1' : '';
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                t.id,
                t.id AS team_id,
                t.name,
                t.name AS team_name,
                MAX(et.volunteers_needed) AS volunteers_needed
             FROM {$wpdb->prefix}spa_events_teams et
             INNER JOIN {$wpdb->prefix}spa_teams t ON t.id = et.team_id
             WHERE et.event_id = %d{$active_sql}
             GROUP BY t.id, t.name
             ORDER BY t.name",
            $event_id
        )
    );
}

function spa_save_event_team_requirements($event_id, $teams, $volunteers_needed = array()) {
    global $wpdb;

    $event_id = intval($event_id);
    if ( ! $event_id ) {
        return false;
    }

    $table = $wpdb->prefix . 'spa_events_teams';
    $wpdb->delete($table, array('event_id' => $event_id), array('%d'));

    foreach ( (array) $teams as $team_key => $needed ) {
        $team_id = intval($team_key);
        if ( ! empty($volunteers_needed) ) {
            $team_id = intval($needed);
            $needed = isset($volunteers_needed[$team_id]) ? intval($volunteers_needed[$team_id]) : 0;
        }
        if ( $team_id <= 0 ) {
            continue;
        }

        $wpdb->insert(
            $table,
            array(
                'event_id'          => $event_id,
                'team_id'           => $team_id,
                'volunteers_needed' => max(1, intval($needed)),
            ),
            array('%d', '%d', '%d')
        );
    }

    return true;
}

function spa_is_active_event_team($event_id, $team_id) {
    global $wpdb;

    return (bool) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}spa_events e
             INNER JOIN {$wpdb->prefix}spa_events_teams et ON et.event_id = e.id
             INNER JOIN {$wpdb->prefix}spa_teams t ON t.id = et.team_id
             WHERE e.id = %d AND e.active = 1 AND et.team_id = %d AND t.active = 1",
            intval($event_id),
            intval($team_id)
        )
    );
}

function spa_is_active_team_volunteer($team_id, $volunteer_id) {
    global $wpdb;

    return (bool) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}spa_volunteer_teams vt
             INNER JOIN {$wpdb->prefix}spa_volunteers v
                ON v.id = vt.volunteer_id AND v.active = 1
             WHERE vt.team_id = %d AND vt.volunteer_id = %d",
            intval($team_id),
            intval($volunteer_id)
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
