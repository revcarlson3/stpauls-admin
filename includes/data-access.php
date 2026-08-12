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
