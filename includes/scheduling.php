<?php

add_action('admin_init', 'spa_handle_scheduling_forms');
add_action('wp_ajax_spa_preview_event_rotation', 'spa_preview_event_rotation_ajax');
add_action('wp_ajax_spa_apply_event_rotation', 'spa_apply_event_rotation_ajax');

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
            'team_id'           => intval($event_team->team_id),
            'team_name'         => $event_team->team_name,
            'volunteers_needed' => intval($event_team->volunteers_needed),
            'assignments'       => array(),
            'message'           => '',
            'advance_rule'      => ! empty($rotation_rows[0]->advance_rule) ? $rotation_rows[0]->advance_rule : 'every_event',
        );

        if ( empty($rotation_rows) ) {
            $team_result['message'] = 'No rotation is configured for this team and service type.';
            $preview[] = $team_result;
            continue;
        }

        $next_index = 0;
        foreach ( $rotation_rows as $index => $rotation_row ) {
            if ( intval($rotation_row->is_next) === 1 ) {
                $next_index = $index;
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

        for ( $offset = 0; $offset < $needed; $offset++ ) {
            $row = $rotation_rows[($next_index + $offset) % $rotation_count];
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

    $preview_data = spa_get_rotation_preview_data($event_id);
    if ( is_wp_error($preview_data) ) {
        wp_send_json_error(array('message' => $preview_data->get_error_message()));
    }

    foreach ( $preview_data['teams'] as $team_result ) {
        if ( empty($team_result['assignments']) ) {
            continue;
        }

        $team_id = intval($team_result['team_id']);

        $wpdb->delete(
            $wpdb->prefix . 'spa_event_volunteers',
            array(
                'event_id' => $event_id,
                'team_id'  => $team_id,
            ),
            array('%d', '%d')
        );

        foreach ( $team_result['assignments'] as $assignment ) {
            $wpdb->insert(
                $wpdb->prefix . 'spa_event_volunteers',
                array(
                    'event_id'     => $event_id,
                    'team_id'      => $team_id,
                    'volunteer_id' => intval($assignment['volunteer_id']),
                ),
                array('%d', '%d', '%d')
            );
        }

        $rotation_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id
                 FROM {$wpdb->prefix}spa_team_rotations
                 WHERE service_type_id = %d
                 AND team_id = %d
                 ORDER BY rotation_order",
                intval($preview_data['event']->service_type_id),
                $team_id
            )
        );

        if ( ! empty($rotation_rows) ) {
            $advance_rule = isset($team_result['advance_rule']) ? $team_result['advance_rule'] : 'every_event';

            if ( $advance_rule !== 'manual' ) {
                $should_advance = true;
                $event_date = ! empty($preview_data['event']->event_date) ? $preview_data['event']->event_date : '';

                if ( $advance_rule === 'weekly' && $event_date !== '' ) {
                    $year_week = gmdate('o-W', strtotime($event_date));
                    $last_key = 'spa_rotation_last_week_' . intval($preview_data['event']->service_type_id) . '_' . $team_id;
                    $previous_week = get_option($last_key, '');
                    $should_advance = $previous_week !== $year_week;
                    if ( $should_advance ) {
                        update_option($last_key, $year_week, false);
                    }
                } elseif ( $advance_rule === 'monthly' && $event_date !== '' ) {
                    $year_month = gmdate('Y-m', strtotime($event_date));
                    $last_key = 'spa_rotation_last_month_' . intval($preview_data['event']->service_type_id) . '_' . $team_id;
                    $previous_month = get_option($last_key, '');
                    $should_advance = $previous_month !== $year_month;
                    if ( $should_advance ) {
                        update_option($last_key, $year_month, false);
                    }
                }

                if ( $should_advance ) {
                    $next_rotation_id = intval($team_result['assignments'][count($team_result['assignments']) - 1]['rotation_id']);
                    $next_index = 0;

                    foreach ( $rotation_rows as $index => $rotation_row ) {
                        if ( intval($rotation_row->id) === $next_rotation_id ) {
                            $next_index = ($index + 1) % count($rotation_rows);
                            break;
                        }
                    }

                    $wpdb->update(
                        $wpdb->prefix . 'spa_team_rotations',
                        array('is_next' => 0),
                        array(
                            'service_type_id' => intval($preview_data['event']->service_type_id),
                            'team_id'         => $team_id,
                        ),
                        array('%d'),
                        array('%d', '%d')
                    );

                    $wpdb->update(
                        $wpdb->prefix . 'spa_team_rotations',
                        array('is_next' => 1),
                        array('id' => intval($rotation_rows[$next_index]->id)),
                        array('%d'),
                        array('%d')
                    );
                }
            }
        }
    }

    wp_send_json_success(array('message' => 'Rotation assignments applied.'));
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
