<?php

function spa_calculate_rotation_team_assignments($rotation_rows, $needed, $service_type_id, $team_id, $event_date) {
    $rotation_count = count($rotation_rows);
    $needed = intval($needed);
    if ( ! $rotation_count || $needed < 1 ) {
        return array(
            'assignments'         => array(),
            'pointer_rotation_id' => 0,
            'period_option_key'   => '',
            'period_value'        => '',
        );
    }

    $current_index = 0;
    foreach ( $rotation_rows as $index => $rotation_row ) {
        if ( intval($rotation_row->is_next) === 1 ) {
            $current_index = $index;
            break;
        }
    }

    $assignment_index = $current_index;
    $advance_rule = ! empty($rotation_rows[0]->advance_rule)
        ? $rotation_rows[0]->advance_rule
        : 'every_event';
    $period_option_key = spa_get_rotation_period_option_key($advance_rule, $service_type_id, $team_id);
    $period_value = spa_get_rotation_period_value($advance_rule, $event_date);
    $pointer_rotation_id = 0;

    if ( $period_option_key !== '' && $period_value !== '' ) {
        $previous_period = get_option($period_option_key, '');
        if ( $previous_period !== $period_value ) {
            if ( $previous_period !== '' ) {
                $assignment_index = ($current_index + $needed) % $rotation_count;
                $pointer_rotation_id = intval($rotation_rows[$assignment_index]->id);
            }
        }
    } elseif ( $advance_rule === 'every_event' ) {
        $pointer_rotation_id = intval(
            $rotation_rows[($current_index + $needed) % $rotation_count]->id
        );
    }

    $assignments = array();
    for ( $offset = 0; $offset < $needed; $offset++ ) {
        $row = $rotation_rows[($assignment_index + $offset) % $rotation_count];
        $assignments[] = array(
            'volunteer_id'   => intval($row->volunteer_id),
            'volunteer_name' => trim($row->first_name . ' ' . $row->last_name),
            'rotation_id'    => intval($row->id),
        );
    }

    return array(
        'assignments'         => $assignments,
        'pointer_rotation_id' => $pointer_rotation_id,
        'period_option_key'   => $period_option_key !== '' && $period_value !== '' && get_option($period_option_key, '') !== $period_value
            ? $period_option_key
            : '',
        'period_value'        => $period_option_key !== '' && $period_value !== '' && get_option($period_option_key, '') !== $period_value
            ? $period_value
            : '',
    );
}
