<?php

add_action('admin_init', 'spa_handle_scheduling_forms');
add_action('wp_ajax_spa_preview_event_rotation', 'spa_preview_event_rotation_ajax');
add_action('wp_ajax_spa_apply_event_rotation', 'spa_apply_event_rotation_ajax');
add_action('wp_ajax_spa_undo_event_rotation', 'spa_undo_event_rotation_ajax');
add_action('wp_ajax_spa_apply_swap_reminder', 'spa_apply_swap_reminder_ajax');
add_action('wp_ajax_spa_get_team_volunteers', 'spa_get_team_volunteers_ajax');

function spa_get_team_volunteers_ajax() {
    global $wpdb;

    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
    if ( ! $team_id ) {
        wp_send_json_error(array('message' => 'Select a team first.'));
    }

    $volunteers = array_map(
        function($volunteer) {
            return array(
                'id' => intval($volunteer->id),
                'first_name' => $volunteer->first_name,
                'last_name' => $volunteer->last_name,
            );
        },
        spa_get_active_team_volunteers($team_id)
    );

    wp_send_json_success(array('volunteers' => $volunteers));
}

function spa_get_rotation_undo_option_key() {
    return 'spa_rotation_last_apply_undo';
}

function spa_get_rotation_applied_option_key($event_id) {
    return 'spa_rotation_applied_' . intval($event_id);
}

function spa_get_rotation_preview_swaps_option_key($event_id) {
    return 'spa_rotation_preview_swaps_' . intval($event_id);
}

function spa_get_rotation_preview_swaps($event_id) {
    $swaps = get_option(spa_get_rotation_preview_swaps_option_key($event_id), array());
    return is_array($swaps) ? $swaps : array();
}

function spa_event_rotation_is_applied($event_id) {
    global $wpdb;

    $applied_state = get_option(spa_get_rotation_applied_option_key($event_id), null);
    if ( $applied_state !== null ) {
        return intval($applied_state) === 1;
    }

    return intval(
        $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM {$wpdb->prefix}spa_event_volunteers
                 WHERE event_id = %d",
                intval($event_id)
            )
        )
    ) > 0;
}

function spa_begin_rotation_undo_transaction() {
    global $wpdb;

    $lock_key = 'spa_rotation_apply_undo_lock';
    add_option($lock_key, 1, '', false);

    if ( $wpdb->query('START TRANSACTION') === false ) {
        return false;
    }

    $lock_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT option_id
             FROM {$wpdb->options}
             WHERE option_name = %s
             FOR UPDATE",
            $lock_key
        )
    );

    if ( ! $lock_id ) {
        $wpdb->query('ROLLBACK');
        return false;
    }

    return true;
}

function spa_write_rotation_option($option_name, $value) {
    global $wpdb;

    $option_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT option_id
             FROM {$wpdb->options}
             WHERE option_name = %s
             LIMIT 1",
            $option_name
        )
    );
    $serialized_value = maybe_serialize($value);

    if ( $option_id ) {
        return $wpdb->update(
            $wpdb->options,
            array('option_value' => $serialized_value),
            array('option_id' => intval($option_id)),
            array('%s'),
            array('%d')
        ) !== false;
    }

    return $wpdb->insert(
        $wpdb->options,
        array(
            'option_name'  => $option_name,
            'option_value' => $serialized_value,
            'autoload'     => 'no',
        ),
        array('%s', '%s', '%s')
    ) === 1;
}

function spa_delete_rotation_option($option_name) {
    global $wpdb;

    return $wpdb->delete(
        $wpdb->options,
        array('option_name' => $option_name),
        array('%s')
    ) !== false;
}

function spa_clear_rotation_option_caches($option_names) {
    foreach ( array_unique($option_names) as $option_name ) {
        wp_cache_delete($option_name, 'options');
    }

    wp_cache_delete('alloptions', 'options');
    wp_cache_delete('notoptions', 'options');
}

function spa_get_rotation_undo_state($event_id) {
    $undo_state = get_option(spa_get_rotation_undo_option_key(), false);

    if (
        ! is_array($undo_state)
        || ! isset($undo_state['event_id'], $undo_state['service_type_id'])
        || intval($undo_state['event_id']) !== intval($event_id)
        || empty($undo_state['service_type_id'])
    ) {
        return false;
    }

    return $undo_state;
}

function spa_get_rotation_period_option_key($advance_rule, $service_type_id, $team_id) {
    if ( $advance_rule === 'weekly' ) {
        return 'spa_rotation_last_week_' . intval($service_type_id) . '_' . intval($team_id);
    }
    if ( $advance_rule === 'monthly' ) {
        return 'spa_rotation_last_month_' . intval($service_type_id) . '_' . intval($team_id);
    }
    return '';
}

function spa_get_rotation_period_value($advance_rule, $event_date) {
    if ( $event_date === '' ) {
        return '';
    }
    if ( $advance_rule === 'weekly' ) {
        return gmdate('o-W', strtotime($event_date));
    }
    if ( $advance_rule === 'monthly' ) {
        return gmdate('Y-m', strtotime($event_date));
    }
    return '';
}

function spa_capture_rotation_undo_state($preview_data) {
    global $wpdb;

    $event_id = intval($preview_data['event']->id);
    $service_type_id = intval($preview_data['event']->service_type_id);
    $state = array(
        'event_id'         => $event_id,
        'service_type_id'  => $service_type_id,
        'assignments'      => $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_id, team_id, volunteer_id, is_override
                 FROM {$wpdb->prefix}spa_event_volunteers
                 WHERE event_id = %d",
                $event_id
            ),
            ARRAY_A
        ),
        'teams'             => array(),
        'period_options'    => array(),
        'preview_swaps'     => spa_get_rotation_preview_swaps($event_id),
        'swap_reminders'   => array(),
    );

    foreach ( $state['preview_swaps'] as $preview_swap ) {
        if ( empty($preview_swap['swap_id']) ) {
            continue;
        }
        $reminder = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, status, applied_event_id, applied_at
                 FROM {$wpdb->prefix}spa_swap_reminders
                 WHERE id = %d",
                intval($preview_swap['swap_id'])
            ),
            ARRAY_A
        );
        if ( $reminder ) {
            $state['swap_reminders'][] = $reminder;
        }
    }

    foreach ( $preview_data['teams'] as $team_result ) {
        if ( empty($team_result['assignments']) ) {
            continue;
        }

        $team_id = intval($team_result['team_id']);
        $state['teams'][$team_id] = array(
            'next_rotation_ids' => array_map(
                'intval',
                $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT id
                         FROM {$wpdb->prefix}spa_team_rotations
                         WHERE service_type_id = %d
                         AND team_id = %d
                         AND is_next = 1",
                        $service_type_id,
                        $team_id
                    )
                )
            ),
        );

        $option_key = spa_get_rotation_period_option_key(
            $team_result['advance_rule'],
            $service_type_id,
            $team_id
        );
        if ( $option_key !== '' ) {
            $option_exists = (bool) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT option_id
                     FROM {$wpdb->options}
                     WHERE option_name = %s
                     LIMIT 1",
                    $option_key
                )
            );
            $state['period_options'][$option_key] = array(
                'exists' => $option_exists,
                'value'  => $option_exists ? get_option($option_key) : null,
            );
        }
    }

    return $state;
}

function spa_get_rotation_preview_data($event_id) {
    global $wpdb;

    $event = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT *
             FROM {$wpdb->prefix}spa_events
             WHERE id = %d
             AND active = 1",
            $event_id
        )
    );

    if ( ! $event ) {
        return new WP_Error('spa_missing_event', 'Event not found.');
    }

    if ( empty($event->service_type_id) ) {
        return new WP_Error('spa_missing_service_type', 'Select a service type for this event before generating assignments.');
    }

    $event_teams = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                et.team_id,
                MAX(et.volunteers_needed) AS volunteers_needed,
                t.name AS team_name
             FROM {$wpdb->prefix}spa_events_teams et
             INNER JOIN {$wpdb->prefix}spa_teams t
                ON t.id = et.team_id
                AND t.active = 1
             WHERE et.event_id = %d
             GROUP BY et.team_id, t.name
             ORDER BY t.name",
            $event_id
        )
    );

    $preview = array();

    foreach ( $event_teams as $event_team ) {
        $rotation_rows = spa_get_active_rotation_rows(
            $event->service_type_id,
            $event_team->team_id
        );

        $team_result = array(
            'team_id'                    => intval($event_team->team_id),
            'team_name'                  => $event_team->team_name,
            'volunteers_needed'          => intval($event_team->volunteers_needed),
            'assignments'                => array(),
            'message'                    => '',
            'advance_rule'               => ! empty($rotation_rows[0]->advance_rule) ? $rotation_rows[0]->advance_rule : 'every_event',
            'pointer_rotation_id'        => 0,
            'period_option_key'          => '',
            'period_value'               => '',
        );

        if ( empty($rotation_rows) ) {
            $team_result['message'] = 'No rotation is configured for this team and service type.';
            $preview[] = $team_result;
            continue;
        }

        $rotation_count = count($rotation_rows);
        $needed = intval($event_team->volunteers_needed);

        if ( $rotation_count < $needed ) {
            return new WP_Error(
                'spa_rotation_too_short',
                sprintf(
                    '%s needs %d volunteers, but its rotation only has %d.',
                    $event_team->team_name,
                    $needed,
                    $rotation_count
                )
            );
        }

        $calculated = spa_calculate_rotation_team_assignments(
            $rotation_rows,
            $needed,
            intval($event->service_type_id),
            intval($event_team->team_id),
            ! empty($event->event_date) ? $event->event_date : ''
        );
        $team_result['assignments'] = $calculated['assignments'];
        $team_result['pointer_rotation_id'] = $calculated['pointer_rotation_id'];
        $team_result['period_option_key'] = $calculated['period_option_key'];
        $team_result['period_value'] = $calculated['period_value'];

        $preview[] = $team_result;
    }

    return array(
        'event'   => $event,
        'teams'   => $preview,
    );
}

function spa_preview_event_rotation_ajax() {
    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    if ( ! $event_id ) {
        wp_send_json_error(array('message' => 'Invalid event ID.'));
    }

    $preview_data = spa_get_rotation_preview_data($event_id);
    if ( is_wp_error($preview_data) ) {
        wp_send_json_error(array('message' => $preview_data->get_error_message()));
    }

    ob_start();
    $rotation_preview = $preview_data['teams'];
    include SPA_TEMPLATE_DIR . 'ajax-event-rotation-preview.php';
    $preview_html = ob_get_clean();

    wp_send_json_success(array('preview_html' => $preview_html));
}

function spa_apply_event_rotation_ajax() {
    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    if ( ! $event_id ) {
        wp_send_json_error(array('message' => 'Invalid event ID.'));
    }

    $result = spa_apply_event_rotation($event_id);
    if ( is_wp_error($result) ) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success(array('message' => 'Rotation assignments applied.'));
}
function spa_undo_event_rotation_ajax() {
    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    if ( ! $event_id ) {
        wp_send_json_error(array('message' => 'Invalid event ID.'));
    }

    $result = spa_undo_event_rotation($event_id);
    if ( is_wp_error($result) ) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success(array('message' => 'The last rotation application was undone.'));
}
function spa_apply_swap_reminder_ajax() {
    global $wpdb;

    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $swap_id = isset($_POST['swap_id']) ? intval($_POST['swap_id']) : 0;
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    if ( ! $swap_id || ! $event_id ) {
        wp_send_json_error(array('message' => 'Invalid swap or event ID.'));
    }

    $event = spa_get_event_by_id($event_id);
    $swap = null;
    foreach ( spa_get_pending_swap_reminders_for_event($event_id, $event ? $event->event_date : '') as $pending_swap ) {
        if ( intval($pending_swap->id) === $swap_id ) {
            $swap = $pending_swap;
            break;
        }
    }
    if ( ! $swap || $swap->event_date !== $swap->swap_date ) {
        wp_send_json_error(array('message' => 'This swap reminder does not match the event date.'));
    }

    $assignment = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT volunteer_id
             FROM {$wpdb->prefix}spa_event_volunteers
             WHERE event_id = %d AND team_id = %d AND volunteer_id = %d",
            $event_id,
            $swap->team_id,
            $swap->scheduled_volunteer_id
        )
    );
    if ( ! $assignment ) {
        if ( spa_event_rotation_is_applied($event_id) ) {
            wp_send_json_error(array('message' => 'The rotation assignments have already been applied, but the scheduled volunteer is not assigned to this event team. Undo and reapply the rotation, or verify the scheduled volunteer before applying this swap.'));
        }
        $preview_data = spa_get_rotation_preview_data($event_id);
        if ( is_wp_error($preview_data) ) {
            wp_send_json_error(array('message' => $preview_data->get_error_message()));
        }

        $preview_assignment = false;
        foreach ( $preview_data['teams'] as $team_result ) {
            if ( intval($team_result['team_id']) !== intval($swap->team_id) ) {
                continue;
            }
            foreach ( $team_result['assignments'] as $preview_row ) {
                if ( intval($preview_row['volunteer_id']) === intval($swap->scheduled_volunteer_id) ) {
                    $preview_assignment = true;
                    break 2;
                }
            }
        }
        if ( ! $preview_assignment ) {
            wp_send_json_error(array('message' => 'Rotation assignments are not applied yet, and the scheduled volunteer is not in this event’s rotation preview. Preview the rotation and verify the team before applying this swap.'));
        }

        $preview_swaps = spa_get_rotation_preview_swaps($event_id);
        $preview_swaps[$swap_id] = array(
            'swap_id'                   => intval($swap_id),
            'team_id'                   => intval($swap->team_id),
            'scheduled_volunteer_id'    => intval($swap->scheduled_volunteer_id),
            'replacement_volunteer_id'  => intval($swap->replacement_volunteer_id),
            'replacement_volunteer_name' => trim($swap->replacement_first_name . ' ' . $swap->replacement_last_name),
        );
        update_option(spa_get_rotation_preview_swaps_option_key($event_id), $preview_swaps, false);
        wp_send_json_success(array('message' => 'Swap saved to the rotation preview. Apply Rotation Assignments to finalize it.'));
    }

    if ( $wpdb->query('START TRANSACTION') === false ) {
        wp_send_json_error(array('message' => 'Unable to begin applying the volunteer swap.'));
    }

    $existing_replacement = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT volunteer_id
             FROM {$wpdb->prefix}spa_event_volunteers
             WHERE event_id = %d AND team_id = %d AND volunteer_id = %d",
            $event_id,
            $swap->team_id,
            $swap->replacement_volunteer_id
        )
    );
    if ( $existing_replacement ) {
        $deleted = $wpdb->delete(
            $wpdb->prefix . 'spa_event_volunteers',
            array('event_id' => $event_id, 'team_id' => $swap->team_id, 'volunteer_id' => $swap->scheduled_volunteer_id),
            array('%d', '%d', '%d')
        );
    } else {
        $deleted = $wpdb->update(
            $wpdb->prefix . 'spa_event_volunteers',
            array('volunteer_id' => $swap->replacement_volunteer_id, 'is_override' => 1),
            array('event_id' => $event_id, 'team_id' => $swap->team_id, 'volunteer_id' => $swap->scheduled_volunteer_id),
            array('%d', '%d'),
            array('%d', '%d', '%d')
        );
    }
    if ( $deleted === false ) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'Unable to apply the volunteer swap.'));
    }

    $updated = $wpdb->update(
        $wpdb->prefix . 'spa_swap_reminders',
        array('status' => 'applied', 'applied_event_id' => $event_id, 'applied_at' => current_time('mysql')),
        array('id' => $swap_id, 'status' => 'pending'),
        array('%s', '%d', '%s'),
        array('%d', '%s')
    );
    if ( $updated !== 1 ) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'The swap was changed, but its reminder could not be marked applied.'));
    }

    if ( $wpdb->query('COMMIT') === false ) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'Unable to apply the volunteer swap.'));
    }

    wp_send_json_success(array('message' => 'Volunteer swap applied.'));
}

function spa_handle_scheduling_forms() {
    if ( ! is_admin() || ! current_user_can('manage_options') ) {
        return;
    }

    if ( empty($_POST['spa_scheduling_action']) ) {
        return;
    }

    if ( ! isset($_POST['spa_scheduling_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['spa_scheduling_nonce'])), 'spa_scheduling_action') ) {
        wp_die('Invalid scheduling request.');
    }

    global $wpdb;

    $action = sanitize_text_field(wp_unslash($_POST['spa_scheduling_action']));

    if ( $action === 'add_service_type' ) {
        $name = isset($_POST['service_type_name']) ? sanitize_text_field(wp_unslash($_POST['service_type_name'])) : '';
        $description = isset($_POST['service_type_description']) ? sanitize_textarea_field(wp_unslash($_POST['service_type_description'])) : '';

        if ( $name !== '' ) {
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}spa_service_types WHERE name = %s",
                    $name
                )
            );

            if ( $existing ) {
                $wpdb->update(
                    $wpdb->prefix . 'spa_service_types',
                    array(
                        'description' => $description,
                        'active'      => 1,
                    ),
                    array('id' => intval($existing)),
                    array('%s', '%d'),
                    array('%d')
                );
            } else {
                $wpdb->insert(
                    $wpdb->prefix . 'spa_service_types',
                    array(
                        'name'        => $name,
                        'description' => $description,
                        'active'      => 1,
                    ),
                    array('%s', '%s', '%d')
                );
            }
        }
    }

    if ( $action === 'save_rotation' ) {
        $service_type_id = isset($_POST['rotation_service_type_id']) ? intval($_POST['rotation_service_type_id']) : 0;
        $team_id = isset($_POST['rotation_team_id']) ? intval($_POST['rotation_team_id']) : 0;
        $volunteer_ids = isset($_POST['rotation_volunteer_ids']) ? array_map('intval', (array) wp_unslash($_POST['rotation_volunteer_ids'])) : array();
        $next_position = isset($_POST['rotation_next_position']) ? max(1, intval($_POST['rotation_next_position'])) : 1;
        $advance_rule = isset($_POST['rotation_advance_rule']) ? sanitize_text_field(wp_unslash($_POST['rotation_advance_rule'])) : 'every_event';
        $volunteer_ids = array_values(array_unique(array_filter($volunteer_ids)));

        if ( $service_type_id > 0 && $team_id > 0 ) {
            $wpdb->delete(
                $wpdb->prefix . 'spa_team_rotations',
                array(
                    'service_type_id' => $service_type_id,
                    'team_id'         => $team_id,
                ),
                array('%d', '%d')
            );

            foreach ( $volunteer_ids as $index => $volunteer_id ) {
                $order = $index + 1;
                $wpdb->insert(
                    $wpdb->prefix . 'spa_team_rotations',
                    array(
                        'service_type_id' => $service_type_id,
                        'team_id'         => $team_id,
                        'volunteer_id'    => $volunteer_id,
                        'rotation_order'  => $order,
                        'is_next'         => $order === $next_position ? 1 : 0,
                        'advance_rule'    => $advance_rule,
                    ),
                    array('%d', '%d', '%d', '%d', '%d', '%s')
                );
            }

        }
    }

    if ( $action === 'add_swap_reminder' ) {
        $scheduled_volunteer_id = isset($_POST['swap_scheduled_volunteer_id']) ? intval($_POST['swap_scheduled_volunteer_id']) : 0;
        $replacement_volunteer_id = isset($_POST['swap_replacement_volunteer_id']) ? intval($_POST['swap_replacement_volunteer_id']) : 0;
        $team_id = isset($_POST['swap_team_id']) ? intval($_POST['swap_team_id']) : 0;
        $swap_date = isset($_POST['swap_date']) ? sanitize_text_field(wp_unslash($_POST['swap_date'])) : '';
        $permanent = ! empty($_POST['swap_permanent']) ? 1 : 0;

        $date_parts = explode('-', $swap_date);
        $date_valid = count($date_parts) === 3
            && strlen($date_parts[0]) === 4
            && strlen($date_parts[1]) === 2
            && strlen($date_parts[2]) === 2
            && checkdate(intval($date_parts[1]), intval($date_parts[2]), intval($date_parts[0]));
        if ( $scheduled_volunteer_id > 0 && $replacement_volunteer_id > 0 && $scheduled_volunteer_id !== $replacement_volunteer_id && $team_id > 0 && $date_valid ) {
            if ( spa_are_active_team_volunteers($team_id, array($scheduled_volunteer_id, $replacement_volunteer_id)) ) {
                $inserted = $wpdb->insert(
                    $wpdb->prefix . 'spa_swap_reminders',
                    array(
                        'scheduled_volunteer_id' => $scheduled_volunteer_id,
                        'replacement_volunteer_id' => $replacement_volunteer_id,
                        'team_id' => $team_id,
                        'swap_date' => $swap_date,
                        'permanent' => $permanent,
                        'status' => 'pending',
                    ),
                    array('%d', '%d', '%d', '%s', '%d', '%s')
                );
                if ( $inserted && $permanent ) {
                    $rotation_rows = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT id, service_type_id, is_next
                             FROM {$wpdb->prefix}spa_team_rotations
                             WHERE team_id = %d AND volunteer_id = %d
                             ORDER BY service_type_id, id",
                            $team_id,
                            $scheduled_volunteer_id
                        )
                    );
                    foreach ( $rotation_rows as $rotation_row ) {
                        $replacement_exists = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT id
                                 FROM {$wpdb->prefix}spa_team_rotations
                                 WHERE service_type_id = %d AND team_id = %d AND volunteer_id = %d
                                 LIMIT 1",
                                $rotation_row->service_type_id,
                                $team_id,
                                $replacement_volunteer_id
                            )
                        );
                        if ( $replacement_exists ) {
                            if ( intval($rotation_row->is_next) === 1 ) {
                                $wpdb->update(
                                    $wpdb->prefix . 'spa_team_rotations',
                                    array('is_next' => 1),
                                    array('id' => intval($replacement_exists)),
                                    array('%d'),
                                    array('%d')
                                );
                            }
                            $wpdb->delete(
                                $wpdb->prefix . 'spa_team_rotations',
                                array('id' => intval($rotation_row->id)),
                                array('%d')
                            );
                        } else {
                            $wpdb->update(
                                $wpdb->prefix . 'spa_team_rotations',
                                array('volunteer_id' => $replacement_volunteer_id),
                                array('id' => intval($rotation_row->id)),
                                array('%d'),
                                array('%d')
                            );
                        }
                    }
                }
            }
        }
    }

    wp_safe_redirect(admin_url('admin.php?page=spa-scheduling'));
    exit;
}

function spa_scheduling_page() {
    global $wpdb;

    $service_types = $wpdb->get_results(
        "SELECT *
         FROM {$wpdb->prefix}spa_service_types
         WHERE active = 1
         ORDER BY name"
    );

    $teams = $wpdb->get_results(
        "SELECT id, name
         FROM {$wpdb->prefix}spa_teams
         WHERE active = 1
         ORDER BY name"
    );

    $volunteers = $wpdb->get_results(
        "SELECT id, first_name, last_name
         FROM {$wpdb->prefix}spa_volunteers
         WHERE active = 1
         ORDER BY last_name, first_name"
    );

    $rotations = $wpdb->get_results(
        "SELECT
            r.*,
            st.name AS service_type_name,
            t.name AS team_name,
            CONCAT(v.first_name, ' ', v.last_name) AS volunteer_name
         FROM {$wpdb->prefix}spa_team_rotations r
         INNER JOIN {$wpdb->prefix}spa_service_types st
            ON st.id = r.service_type_id
         INNER JOIN {$wpdb->prefix}spa_teams t
            ON t.id = r.team_id
         INNER JOIN {$wpdb->prefix}spa_volunteers v
            ON v.id = r.volunteer_id
         ORDER BY st.name, t.name, r.rotation_order"
    );

    $volunteers_by_team = array();
    foreach ( $teams as $team ) {
        $volunteers_by_team[$team->id] = spa_get_active_team_volunteers($team->id);
    }

    $rotation_map = array();
    foreach ( $rotations as $rotation ) {
        if ( ! isset($rotation_map[$rotation->service_type_id]) ) {
            $rotation_map[$rotation->service_type_id] = array();
        }
        if ( ! isset($rotation_map[$rotation->service_type_id][$rotation->team_id]) ) {
            $rotation_map[$rotation->service_type_id][$rotation->team_id] = array(
                'volunteer_ids' => array(),
                'next_position' => 1,
                'advance_rule'  => 'every_event',
            );
        }

        $rotation_map[$rotation->service_type_id][$rotation->team_id]['volunteer_ids'][] = intval($rotation->volunteer_id);

        if ( intval($rotation->is_next) === 1 ) {
            $rotation_map[$rotation->service_type_id][$rotation->team_id]['next_position'] = intval($rotation->rotation_order);
        }

        if ( ! empty($rotation->advance_rule) ) {
            $rotation_map[$rotation->service_type_id][$rotation->team_id]['advance_rule'] = $rotation->advance_rule;
        }
    }

    $swap_reminders = $wpdb->get_results(
        "SELECT
            sr.*,
            t.name AS team_name,
            CONCAT(sv.first_name, ' ', sv.last_name) AS scheduled_volunteer_name,
            CONCAT(rv.first_name, ' ', rv.last_name) AS replacement_volunteer_name
         FROM {$wpdb->prefix}spa_swap_reminders sr
         INNER JOIN {$wpdb->prefix}spa_teams t ON t.id = sr.team_id
         INNER JOIN {$wpdb->prefix}spa_volunteers sv ON sv.id = sr.scheduled_volunteer_id
         INNER JOIN {$wpdb->prefix}spa_volunteers rv ON rv.id = sr.replacement_volunteer_id
         WHERE sr.status = 'pending'
         ORDER BY sr.swap_date, t.name, sv.last_name, sv.first_name"
    );

    $page_title = 'Scheduling';
    include SPA_TEMPLATE_DIR . 'scheduling-page.php';
}
