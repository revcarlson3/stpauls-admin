<?php

add_action('admin_init', 'spa_handle_scheduling_forms');
add_action('wp_ajax_spa_preview_event_rotation', 'spa_preview_event_rotation_ajax');
add_action('wp_ajax_spa_apply_event_rotation', 'spa_apply_event_rotation_ajax');
add_action('wp_ajax_spa_undo_event_rotation', 'spa_undo_event_rotation_ajax');

function spa_get_rotation_undo_option_key() {
    return 'spa_rotation_last_apply_undo';
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
    );

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
        $rotation_rows = $wpdb->get_results(
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
                    ON v.id = r.volunteer_id
                    AND v.active = 1
                 WHERE r.service_type_id = %d
                 AND r.team_id = %d
                 ORDER BY r.rotation_order",
                $event->service_type_id,
                $event_team->team_id
            )
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

        $current_index = 0;
        foreach ( $rotation_rows as $index => $rotation_row ) {
            if ( intval($rotation_row->is_next) === 1 ) {
                $current_index = $index;
                break;
            }
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

        $assignment_index = $current_index;
        $period_option_key = spa_get_rotation_period_option_key(
            $team_result['advance_rule'],
            intval($event->service_type_id),
            intval($event_team->team_id)
        );
        $period_value = spa_get_rotation_period_value(
            $team_result['advance_rule'],
            ! empty($event->event_date) ? $event->event_date : ''
        );

        if ( $period_option_key !== '' && $period_value !== '' ) {
            $previous_period = get_option($period_option_key, '');
            if ( $previous_period !== $period_value ) {
                $team_result['period_option_key'] = $period_option_key;
                $team_result['period_value'] = $period_value;

                if ( $previous_period !== '' ) {
                    $assignment_index = ($current_index + $needed) % $rotation_count;
                    $team_result['pointer_rotation_id'] = intval($rotation_rows[$assignment_index]->id);
                }
            }
        } elseif ( $team_result['advance_rule'] === 'every_event' ) {
            $team_result['pointer_rotation_id'] = intval(
                $rotation_rows[($current_index + $needed) % $rotation_count]->id
            );
        }

        for ( $offset = 0; $offset < $needed; $offset++ ) {
            $row = $rotation_rows[($assignment_index + $offset) % $rotation_count];
            $team_result['assignments'][] = array(
                'volunteer_id'   => intval($row->volunteer_id),
                'volunteer_name' => trim($row->first_name . ' ' . $row->last_name),
                'rotation_id'    => intval($row->id),
            );
        }

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
    global $wpdb;

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

    if ( ! spa_begin_rotation_undo_transaction() ) {
        wp_send_json_error(array('message' => 'Unable to begin applying the rotation assignments.'));
    }

    $preview_data = spa_get_rotation_preview_data($event_id);
    if ( is_wp_error($preview_data) ) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => $preview_data->get_error_message()));
    }

    $undo_state = spa_capture_rotation_undo_state($preview_data);
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
            wp_send_json_error(array('message' => 'Unable to replace the existing event assignments.'));
        }

        foreach ( $team_result['assignments'] as $assignment ) {
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
                wp_send_json_error(array('message' => 'Unable to save the new event assignments.'));
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
                wp_send_json_error(array('message' => 'Unable to advance the team rotation.'));
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
                wp_send_json_error(array('message' => 'Unable to advance the team rotation.'));
            }
        }
    }

    $changed_option_keys = array();
    foreach ( $period_updates as $option_key => $option_value ) {
        if ( ! spa_write_rotation_option($option_key, $option_value) ) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(array('message' => 'Unable to save the rotation advancement marker.'));
        }
        $changed_option_keys[] = $option_key;
    }

    $undo_option_key = spa_get_rotation_undo_option_key();
    if ( ! spa_write_rotation_option($undo_option_key, $undo_state) ) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'Unable to save the rotation undo state.'));
    }
    $changed_option_keys[] = $undo_option_key;

    if ( $wpdb->query('COMMIT') === false ) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'Unable to apply the rotation assignments.'));
    }
    spa_clear_rotation_option_caches($changed_option_keys);

    wp_send_json_success(array('message' => 'Rotation assignments applied.'));
}

function spa_undo_event_rotation_ajax() {
    global $wpdb;

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

    if ( ! spa_begin_rotation_undo_transaction() ) {
        wp_send_json_error(array('message' => 'Unable to begin undoing the rotation assignments.'));
    }

    $option_key = spa_get_rotation_undo_option_key();
    $undo_state = spa_get_rotation_undo_state($event_id);

    if ( $undo_state === false ) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'There is no rotation application to undo.'));
    }

    $deleted = $wpdb->delete(
        $wpdb->prefix . 'spa_event_volunteers',
        array('event_id' => $event_id),
        array('%d')
    );
    if ( $deleted === false ) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'Unable to restore the previous assignments.'));
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
            wp_send_json_error(array('message' => 'Unable to restore the previous assignments.'));
        }
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
            wp_send_json_error(array('message' => 'Unable to restore the previous rotation positions.'));
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
                wp_send_json_error(array('message' => 'Unable to restore the previous rotation positions.'));
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
            wp_send_json_error(array('message' => 'Unable to restore the rotation advancement markers.'));
        }
        $changed_option_keys[] = $period_key;
    }

    if ( ! spa_delete_rotation_option($option_key) ) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'Unable to clear the rotation undo state.'));
    }
    $changed_option_keys[] = $option_key;

    if ( $wpdb->query('COMMIT') === false ) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'Unable to undo the rotation assignments.'));
    }
    spa_clear_rotation_option_caches($changed_option_keys);

    wp_send_json_success(array('message' => 'The last rotation application was undone.'));
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
        $volunteers_by_team[$team->id] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    v.id,
                    v.first_name,
                    v.last_name
                 FROM {$wpdb->prefix}spa_volunteer_teams vt
                 INNER JOIN {$wpdb->prefix}spa_volunteers v
                    ON v.id = vt.volunteer_id
                 WHERE vt.team_id = %d
                 AND v.active = 1
                 ORDER BY v.last_name, v.first_name",
                $team->id
            )
        );
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

    $page_title = 'Scheduling';
    include SPA_TEMPLATE_DIR . 'scheduling-page.php';
}
