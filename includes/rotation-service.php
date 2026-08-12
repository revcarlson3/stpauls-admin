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

function spa_apply_event_rotation($event_id) {
    global $wpdb;

    if ( ! spa_begin_rotation_undo_transaction() ) {
        return new WP_Error('spa_apply_rotation_error', 'Unable to begin applying the rotation assignments.');
    }

    if ( spa_event_rotation_is_applied($event_id) ) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('spa_apply_rotation_error', 'Rotation assignments have already been applied. Undo the last apply before applying them again.');
    }

    $preview_data = spa_get_rotation_preview_data($event_id);
    if ( is_wp_error($preview_data) ) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('spa_apply_rotation_error', $preview_data->get_error_message());
    }

    $undo_state = spa_capture_rotation_undo_state($preview_data);
    $preview_swaps = spa_get_rotation_preview_swaps($event_id);
    $applied_preview_swap_ids = array();
    $period_updates = array();

    foreach ( $preview_data['teams'] as $team_result ) {
        if ( empty($team_result['assignments']) ) {
            continue;
        }

        $team_id = intval($team_result['team_id']);

        $deleted = $wpdb->delete(
            $wpdb->prefix . 'spa_event_volunteers',
            array(
                'event_id' => $event_id,
                'team_id'  => $team_id,
            ),
            array('%d', '%d')
        );
        if ( $deleted === false ) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('spa_apply_rotation_error', 'Unable to replace the existing event assignments.');
        }

        $team_assignments = $team_result['assignments'];
        foreach ( $preview_swaps as $preview_swap ) {
            if (
                empty($preview_swap['swap_id'])
                || intval($preview_swap['team_id']) !== $team_id
                || empty($preview_swap['scheduled_volunteer_id'])
            ) {
                continue;
            }

            $scheduled_index = false;
            foreach ( $team_assignments as $index => $assignment ) {
                if ( intval($assignment['volunteer_id']) === intval($preview_swap['scheduled_volunteer_id']) ) {
                    $scheduled_index = $index;
                    break;
                }
            }
            if ( $scheduled_index === false ) {
                continue;
            }

            $replacement_exists = false;
            foreach ( $team_assignments as $assignment ) {
                if ( intval($assignment['volunteer_id']) === intval($preview_swap['replacement_volunteer_id']) ) {
                    $replacement_exists = true;
                    break;
                }
            }
            if ( $replacement_exists ) {
                unset($team_assignments[$scheduled_index]);
                $team_assignments = array_values($team_assignments);
            } else {
                $team_assignments[$scheduled_index]['volunteer_id'] = intval($preview_swap['replacement_volunteer_id']);
                $team_assignments[$scheduled_index]['volunteer_name'] = $preview_swap['replacement_volunteer_name'];
            }
            $applied_preview_swap_ids[] = intval($preview_swap['swap_id']);
        }

        foreach ( $team_assignments as $assignment ) {
            $inserted = $wpdb->insert(
                $wpdb->prefix . 'spa_event_volunteers',
                array(
                    'event_id'     => $event_id,
                    'team_id'      => $team_id,
                    'volunteer_id' => intval($assignment['volunteer_id']),
                ),
                array('%d', '%d', '%d')
            );
            if ( $inserted !== 1 ) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('spa_apply_rotation_error', 'Unable to save the new event assignments.');
            }

        }

        if ( ! empty($team_result['period_option_key']) && $team_result['period_value'] !== '' ) {
            $period_updates[$team_result['period_option_key']] = $team_result['period_value'];
        }

        if ( ! empty($team_result['pointer_rotation_id']) ) {
            $cleared = $wpdb->update(
                $wpdb->prefix . 'spa_team_rotations',
                array('is_next' => 0),
                array(
                    'service_type_id' => intval($preview_data['event']->service_type_id),
                    'team_id'         => $team_id,
                ),
                array('%d'),
                array('%d', '%d')
            );
            if ( $cleared === false ) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('spa_apply_rotation_error', 'Unable to advance the team rotation.');
            }

            $advanced = $wpdb->update(
                $wpdb->prefix . 'spa_team_rotations',
                array('is_next' => 1),
                array(
                    'id'              => intval($team_result['pointer_rotation_id']),
                    'service_type_id' => intval($preview_data['event']->service_type_id),
                    'team_id'         => $team_id,
                ),
                array('%d'),
                array('%d', '%d', '%d')
            );
            if ( $advanced !== 1 ) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('spa_apply_rotation_error', 'Unable to advance the team rotation.');
            }
        }
    }

    if ( count(array_unique($applied_preview_swap_ids)) !== count($preview_swaps) ) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('spa_apply_rotation_error', 'A saved preview swap no longer matches the rotation preview. Refresh the event and verify the swap before applying assignments.');
    }
    foreach ( array_unique($applied_preview_swap_ids) as $swap_id ) {
        $updated = $wpdb->update(
            $wpdb->prefix . 'spa_swap_reminders',
            array('status' => 'applied', 'applied_event_id' => $event_id, 'applied_at' => current_time('mysql')),
            array('id' => $swap_id, 'status' => 'pending'),
            array('%s', '%d', '%s'),
            array('%d', '%s')
        );
        if ( $updated !== 1 ) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('spa_apply_rotation_error', 'A preview swap changed before it could be applied. Refresh the event and try again.');
        }
    }

    $changed_option_keys = array();
    foreach ( $period_updates as $option_key => $option_value ) {
        if ( ! spa_write_rotation_option($option_key, $option_value) ) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('spa_apply_rotation_error', 'Unable to save the rotation advancement marker.');
        }
        $changed_option_keys[] = $option_key;
    }

    $applied_option_key = spa_get_rotation_applied_option_key($event_id);
    if ( ! spa_write_rotation_option($applied_option_key, 1) ) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('spa_apply_rotation_error', 'Unable to save the applied rotation state.');
    }
    $changed_option_keys[] = $applied_option_key;

    $undo_option_key = spa_get_rotation_undo_option_key();
    if ( ! spa_write_rotation_option($undo_option_key, $undo_state) ) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('spa_apply_rotation_error', 'Unable to save the rotation undo state.');
    }
    $changed_option_keys[] = $undo_option_key;

    if ( ! empty($preview_swaps) ) {
        if ( ! spa_delete_rotation_option(spa_get_rotation_preview_swaps_option_key($event_id)) ) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('spa_apply_rotation_error', 'Unable to clear the applied preview swaps.');
        }
        $changed_option_keys[] = spa_get_rotation_preview_swaps_option_key($event_id);
    }

    if ( $wpdb->query('COMMIT') === false ) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('spa_apply_rotation_error', 'Unable to apply the rotation assignments.');
    }
    spa_clear_rotation_option_caches($changed_option_keys);

    return true;
}

function spa_undo_event_rotation($event_id) {
    global $wpdb;

    if ( ! spa_begin_rotation_undo_transaction() ) {
        return new WP_Error('spa_undo_rotation_error', 'Unable to begin undoing the rotation assignments.');
    }

    $option_key = spa_get_rotation_undo_option_key();
    $undo_state = spa_get_rotation_undo_state($event_id);

    if ( $undo_state === false ) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('spa_undo_rotation_error', 'There is no rotation application to undo.');
    }

    $deleted = $wpdb->delete(
        $wpdb->prefix . 'spa_event_volunteers',
        array('event_id' => $event_id),
        array('%d')
    );
    if ( $deleted === false ) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('spa_undo_rotation_error', 'Unable to restore the previous assignments.');
    }

    foreach ( $undo_state['assignments'] as $assignment ) {
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'spa_event_volunteers',
            array(
                'event_id'     => intval($assignment['event_id']),
                'team_id'      => intval($assignment['team_id']),
                'volunteer_id' => intval($assignment['volunteer_id']),
                'is_override'  => intval($assignment['is_override']),
            ),
            array('%d', '%d', '%d', '%d')
        );
        if ( $inserted !== 1 ) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('spa_undo_rotation_error', 'Unable to restore the previous assignments.');
        }

    }

    if ( ! empty($undo_state['swap_reminders']) ) {
        foreach ( $undo_state['swap_reminders'] as $reminder ) {
            $restored = $wpdb->update(
                $wpdb->prefix . 'spa_swap_reminders',
                array(
                    'status'           => $reminder['status'],
                    'applied_event_id' => $reminder['applied_event_id'],
                    'applied_at'       => $reminder['applied_at'],
                ),
                array('id' => intval($reminder['id'])),
                array('%s', '%d', '%s'),
                array('%d')
            );
            if ( $restored === false ) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('spa_undo_rotation_error', 'Unable to restore the volunteer swap reminders.');
            }
        }
    }

    $preview_swaps_key = spa_get_rotation_preview_swaps_option_key($event_id);
    if ( ! empty($undo_state['preview_swaps']) ) {
        $preview_swaps_restored = spa_write_rotation_option($preview_swaps_key, $undo_state['preview_swaps']);
    } else {
        $preview_swaps_restored = spa_delete_rotation_option($preview_swaps_key);
    }
    if ( ! $preview_swaps_restored ) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('spa_undo_rotation_error', 'Unable to restore the rotation preview swaps.');
    }

    foreach ( $undo_state['teams'] as $team_id => $team_state ) {
        $cleared = $wpdb->update(
            $wpdb->prefix . 'spa_team_rotations',
            array('is_next' => 0),
            array(
                'service_type_id' => intval($undo_state['service_type_id']),
                'team_id'         => intval($team_id),
            ),
            array('%d'),
            array('%d', '%d')
        );
        if ( $cleared === false ) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('spa_undo_rotation_error', 'Unable to restore the previous rotation positions.');
        }

        foreach ( $team_state['next_rotation_ids'] as $next_rotation_id ) {
            $restored = $wpdb->update(
                $wpdb->prefix . 'spa_team_rotations',
                array('is_next' => 1),
                array(
                    'id'              => intval($next_rotation_id),
                    'service_type_id' => intval($undo_state['service_type_id']),
                    'team_id'         => intval($team_id),
                ),
                array('%d'),
                array('%d', '%d', '%d')
            );
            if ( $restored !== 1 ) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('spa_undo_rotation_error', 'Unable to restore the previous rotation positions.');
            }
        }
    }

    $changed_option_keys = array();
    foreach ( $undo_state['period_options'] as $period_key => $period_state ) {
        if ( $period_state['exists'] ) {
            $option_restored = spa_write_rotation_option($period_key, $period_state['value']);
        } else {
            $option_restored = spa_delete_rotation_option($period_key);
        }
        if ( ! $option_restored ) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('spa_undo_rotation_error', 'Unable to restore the rotation advancement markers.');
        }
        $changed_option_keys[] = $period_key;
    }

    if ( ! spa_delete_rotation_option($option_key) ) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('spa_undo_rotation_error', 'Unable to clear the rotation undo state.');
    }
    $changed_option_keys[] = $option_key;
    $changed_option_keys[] = $preview_swaps_key;

    $applied_option_key = spa_get_rotation_applied_option_key($event_id);
    if ( ! spa_write_rotation_option($applied_option_key, 0) ) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('spa_undo_rotation_error', 'Unable to reset the applied rotation state.');
    }
    $changed_option_keys[] = $applied_option_key;

    if ( $wpdb->query('COMMIT') === false ) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('spa_undo_rotation_error', 'Unable to undo the rotation assignments.');
    }
    spa_clear_rotation_option_caches($changed_option_keys);

    return true;
}
