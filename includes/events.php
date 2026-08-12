<?php
// Events functions
add_action(
    'wp_ajax_spa_load_event',
    'spa_load_event_ajax'
);

add_action(
    'wp_ajax_spa_load_events_page',
    'spa_load_events_page_ajax'
);

add_action(
    'wp_ajax_spa_save_event_modal',
    'spa_save_event_modal_ajax'
);

add_action(
    'wp_ajax_spa_save_event_details',
    'spa_save_event_details_ajax'
);

add_action(
    'wp_ajax_spa_delete_event',
    'spa_delete_event_ajax'
);

add_action(
    'wp_ajax_spa_override_event_volunteer',
    'spa_override_event_volunteer_ajax'
);

function spa_get_posted_service_builder_url($field = 'service_builder_url') {
    if ( ! array_key_exists($field, $_POST) ) {
        return null;
    }

    $raw_url = trim((string) wp_unslash($_POST[$field]));
    $url = spa_sanitize_service_builder_url($raw_url);
    if ( $raw_url !== '' && $url === '' ) {
        return new WP_Error(
            'invalid_service_builder_url',
            'Enter a valid Lutheran Service Builder day URL beginning with https://app.lutheranservicebuilder.com/holiday/.'
        );
    }

    return $url;
}

function spa_save_event_details_ajax() {
    global $wpdb;

    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $event_id = intval($_POST['event_id']);
    if ( ! $event_id ) {
        wp_send_json_error(array('message' => 'Invalid event ID'));
    }

    $service_type_id = isset($_POST['service_type_id']) ? intval($_POST['service_type_id']) : 0;
    $season = isset($_POST['season']) ? sanitize_text_field(wp_unslash($_POST['season'])) : '';
    $special_day = isset($_POST['special_day']) ? sanitize_text_field(wp_unslash($_POST['special_day'])) : '';
    if ( $season !== '' && ! in_array($season, spa_get_church_year_seasons(), true) ) {
        $season = '';
    }
    if ( $special_day !== '' && ! in_array($special_day, spa_get_church_year_special_days(), true) ) {
        $special_day = '';
    }
    $service_builder_url = spa_get_posted_service_builder_url();
    if ( is_wp_error($service_builder_url) ) {
        wp_send_json_error(array('message' => $service_builder_url->get_error_message()));
    }

    $update_scope = isset($_POST['update_scope']) ? sanitize_text_field($_POST['update_scope']) : 'parent';
    $event = spa_get_event_by_id($event_id);
    if ( ! $event ) {
        wp_send_json_error(array('message' => 'Event not found.'));
    }
    $is_series_parent = is_null($event->parent_event_id) && intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}spa_events WHERE parent_event_id = %d", $event_id))) > 0;
    if ($update_scope === 'series' && ! $is_series_parent) {
        wp_send_json_error(array('message' => 'Only recurring parent events can update the full series.'));
    }

    // Update event fields
    $event_data = array(
        'name'                => sanitize_text_field($_POST['name']),
        'season'              => $season,
        'special_day'         => $special_day,
        'location'            => sanitize_text_field($_POST['location']),
        'description'         => sanitize_textarea_field($_POST['description']),
        'event_date'          => sanitize_text_field($_POST['event_date']),
        'start_time'          => sanitize_text_field($_POST['start_time']),
        'end_time'            => sanitize_text_field($_POST['end_time']),
        'service_type_id'     => $service_type_id > 0 ? $service_type_id : null,
        'is_recurring'        => intval($_POST['is_recurring']),
        'recurrence_type'     => sanitize_text_field($_POST['recurrence_type']),
        'recurrence_end_date' => sanitize_text_field($_POST['recurrence_end_date']),
        'notify_volunteers'   => ! empty($_POST['notify_volunteers']) ? 1 : 0,
    );
    $event_formats = array('%s','%s','%s','%s','%s','%s','%s','%s','%d','%d','%s','%s','%d');
    if ( $service_builder_url !== null ) {
        $event_data['service_builder_url'] = $service_builder_url ?: null;
        $event_formats[] = '%s';
    }

    if ($update_scope === 'series') {
        $child_events = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$wpdb->prefix}spa_events WHERE parent_event_id = %d ORDER BY event_date, start_time", $event_id));
        $all_events = array_merge(array((object) array('id' => $event_id)), $child_events);
        foreach ($all_events as $series_event) {
            $series_event_data = $event_data;
            $series_event_formats = $event_formats;

            if (intval($series_event->id) !== $event_id) {
                unset($series_event_data['event_date']);
                unset($series_event_data['service_builder_url']);
                $series_event_formats = array('%s','%s','%s','%s','%s','%s','%s','%d','%d','%s','%s','%d');
            }

            $result = $wpdb->update(
                $wpdb->prefix . 'spa_events',
                $series_event_data,
                array('id' => $series_event->id),
                $series_event_formats,
                array('%d')
            );
            if ( $result === false ) {
                wp_send_json_error(array('message' => 'Database update failed: ' . $wpdb->last_error));
            }
            $wpdb->delete($wpdb->prefix . 'spa_events_teams', array('event_id' => $series_event->id), array('%d'));
        }
        foreach ($all_events as $series_event) {
            spa_save_event_team_requirements($series_event->id, isset($_POST['teams']) ? (array) $_POST['teams'] : array());
        }
    } else {
        $result = $wpdb->update($wpdb->prefix . 'spa_events', $event_data, array('id' => $event_id), $event_formats, array('%d'));
        if ( $result === false ) {
            wp_send_json_error(array('message' => 'Database update failed: ' . $wpdb->last_error));
        }
        spa_save_event_team_requirements($event_id, isset($_POST['teams']) ? (array) $_POST['teams'] : array());
    }

    wp_send_json_success(array(
        'message' => $update_scope === 'series' ? 'Series saved.' : 'Event saved.',
    ));
}

function spa_override_event_volunteer_ajax() {
    global $wpdb;

    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $event_id = intval($_POST['event_id']);
    $team_id = intval($_POST['team_id']);
    $old_volunteer_id = intval($_POST['old_volunteer_id']);
    $new_volunteer_id = intval($_POST['new_volunteer_id']);

    if ( ! $event_id || ! $team_id || ! $old_volunteer_id || ! $new_volunteer_id ) {
        wp_send_json_error(array('message' => 'Invalid request.'));
    }

    if ( ! spa_is_active_event_team($event_id, $team_id) ) {
        wp_send_json_error(array('message' => 'Overrides are only available for active teams on this event.'));
    }

    if ( ! spa_is_active_team_volunteer($team_id, $new_volunteer_id) ) {
        wp_send_json_error(array('message' => 'Select an active volunteer from this team.'));
    }

    if ( $old_volunteer_id === $new_volunteer_id ) {
        wp_send_json_success(array('message' => 'Volunteer assignment unchanged.'));
    }

    $has_existing_assignment = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$wpdb->prefix}spa_event_volunteers
             WHERE event_id = %d
             AND team_id = %d
             AND volunteer_id = %d",
            $event_id,
            $team_id,
            $old_volunteer_id
        )
    );
    if ( ! $has_existing_assignment ) {
        wp_send_json_error(array('message' => 'The assignment being replaced no longer exists.'));
    }

    $wpdb->query('START TRANSACTION');
    $deleted = $wpdb->delete(
        $wpdb->prefix . 'spa_event_volunteers',
        array(
            'event_id'     => $event_id,
            'team_id'      => $team_id,
            'volunteer_id' => $old_volunteer_id,
        ),
        array('%d', '%d', '%d')
    );

    $inserted = $wpdb->insert(
        $wpdb->prefix . 'spa_event_volunteers',
        array(
            'event_id'     => $event_id,
            'team_id'      => $team_id,
            'volunteer_id' => $new_volunteer_id,
            'is_override'  => 1,
        ),
        array('%d', '%d', '%d', '%d')
    );

    if ( $deleted !== 1 || $inserted !== 1 ) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => 'Unable to save the volunteer override.'));
    }

    $wpdb->query('COMMIT');
    wp_send_json_success(array('message' => 'Volunteer override saved.'));
}

function spa_delete_event_ajax() {
    global $wpdb;

    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $event_id = intval($_POST['event_id']);
    $delete_scope = isset($_POST['delete_scope']) ? sanitize_text_field($_POST['delete_scope']) : 'single';
    if ( ! $event_id ) {
        wp_send_json_error(array('message' => 'Invalid event ID'));
    }

    $event = spa_get_event_by_id($event_id);
    if ( ! $event ) {
        wp_send_json_error(array('message' => 'Event not found.'));
    }

    $is_series_parent = is_null($event->parent_event_id) && intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}spa_events WHERE parent_event_id = %d", $event_id))) > 0;
    if ($delete_scope === 'series' && ! $is_series_parent) {
        wp_send_json_error(array('message' => 'Only recurring parent events can delete the full series.'));
    }

    $event_ids = array($event_id);
    if ($delete_scope === 'series') {
        $child_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}spa_events WHERE parent_event_id = %d", $event_id));
        $event_ids = array_merge($event_ids, array_map('intval', $child_ids));
    }

    foreach ($event_ids as $id) {
        $wpdb->update($wpdb->prefix . 'spa_events', array('active' => 0), array('id' => $id), array('%d'), array('%d'));
    }

    wp_send_json_success(array('message' => $delete_scope === 'series' ? 'Series deleted.' : 'Event deleted.'));
}

function spa_save_event_modal_ajax() {
    global $wpdb;

    if (! check_ajax_referer('spa_admin_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if (! current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $table_name = $wpdb->prefix . 'spa_events';
    
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $existing_event = $event_id ? $wpdb->get_row($wpdb->prepare("SELECT id, parent_event_id FROM {$table_name} WHERE id = %d", $event_id)) : null;
    $is_new_event = ! $existing_event;
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $season = isset($_POST['season']) ? sanitize_text_field(wp_unslash($_POST['season'])) : '';
    $special_day = isset($_POST['special_day']) ? sanitize_text_field(wp_unslash($_POST['special_day'])) : '';
    $location = isset($_POST['location']) ? sanitize_text_field(wp_unslash($_POST['location'])) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
    $event_date = isset($_POST['event_date']) ? sanitize_text_field(wp_unslash($_POST['event_date'])) : '';
    $start_time = isset($_POST['start_time']) ? sanitize_text_field(wp_unslash($_POST['start_time'])) : '';
    $end_time = isset($_POST['end_time']) ? sanitize_text_field(wp_unslash($_POST['end_time'])) : '';
    $is_recurring = isset($_POST['is_recurring']) ? intval($_POST['is_recurring']) : 0;
    $recurrence_type = isset($_POST['recurrence_type']) ? sanitize_text_field(wp_unslash($_POST['recurrence_type'])) : '';
    $recurrence_end_date = isset($_POST['recurrence_end_date']) ? sanitize_text_field(wp_unslash($_POST['recurrence_end_date'])) : '';
    $service_type_id = isset($_POST['service_type_id']) ? intval($_POST['service_type_id']) : 0;
    if ( $season !== '' && ! in_array($season, spa_get_church_year_seasons(), true) ) {
        $season = '';
    }
    if ( $special_day !== '' && ! in_array($special_day, spa_get_church_year_special_days(), true) ) {
        $special_day = '';
    }
    $service_builder_url = spa_get_posted_service_builder_url();
    if ( is_wp_error($service_builder_url) ) {
        wp_send_json_error(array('message' => $service_builder_url->get_error_message()));
    }

    if (empty($name) || empty($event_date)) {
        wp_send_json_error('Event name and date are required');
    }
    if ($is_recurring && empty($recurrence_type)) {
        wp_send_json_error('Please choose a recurrence type for recurring events.');
    }
    if ($is_recurring && empty($recurrence_end_date)) {
        wp_send_json_error('Please choose a repeat-until date for recurring events.');
    }
    if ($is_recurring && $recurrence_end_date < $event_date) {
        wp_send_json_error('Repeat-until date must be on or after the event date.');
    }

    $recurring_ok = !$is_recurring || (!empty($recurrence_type) && !empty($recurrence_end_date) && $recurrence_end_date >= $event_date);

    if ($is_recurring && empty($recurrence_type)) {
        wp_send_json_error(array('message' => 'Please choose a recurrence type for recurring events.'));
    }
    if ($is_recurring && empty($recurrence_end_date)) {
        wp_send_json_error(array('message' => 'Please choose a repeat-until date for recurring events.'));
    }
    if ($is_recurring && $recurrence_end_date < $event_date) {
        wp_send_json_error(array('message' => 'Repeat-until date must be on or after the event date.'));
    }

    $data = array(
        'name' => $name,
        'season' => $season,
        'special_day' => $special_day,
        'location' => $location,
        'description' => $description,
        'event_date' => $event_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'service_type_id' => $service_type_id > 0 ? $service_type_id : null,
        'is_recurring' => $is_recurring,
        'recurrence_type' => $recurrence_type,
        'recurrence_end_date' => $recurrence_end_date,
        'notify_volunteers' => ! empty($_POST['notify_volunteers']) ? 1 : 0
    );
    if ( $service_builder_url !== null ) {
        $data['service_builder_url'] = $service_builder_url ?: null;
    }

    if ($event_id > 0) {
        $wpdb->update($table_name, $data, array('id' => $event_id));
    } else {
        $wpdb->insert($table_name, $data);
        $event_id = $wpdb->insert_id;
    }

    $is_recurring_parent = $is_new_event || ( $existing_event && is_null($existing_event->parent_event_id) );
    if ($is_recurring && $recurring_ok && $event_id > 0 && $is_recurring_parent) {
        if ( ! $is_new_event ) {
            $child_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$table_name} WHERE parent_event_id = %d", $event_id));
            foreach ( $child_ids as $child_id ) {
                $wpdb->delete($wpdb->prefix . 'spa_events_teams', array('event_id' => intval($child_id)), array('%d'));
                $wpdb->delete($wpdb->prefix . 'spa_event_volunteers', array('event_id' => intval($child_id)), array('%d'));
            }
            if ( ! empty($child_ids) ) {
                $wpdb->query("DELETE FROM {$table_name} WHERE parent_event_id = " . implode(',', array_map('intval', $child_ids)));
            }
        }
        $interval = false;
        if ($recurrence_type === 'daily') {
            $interval = new DateInterval('P1D');
        } elseif ($recurrence_type === 'weekly') {
            $interval = new DateInterval('P1W');
        } elseif ($recurrence_type === 'monthly') {
            $interval = new DateInterval('P1M');
        }

        if ($interval) {
            $series_date = new DateTime($event_date);
            $series_end = new DateTime($recurrence_end_date);
            $series_date->add($interval);
            while ($series_date <= $series_end) {
                $duplicate = $data;
                $duplicate['event_date'] = $series_date->format('Y-m-d');
                $duplicate['parent_event_id'] = $event_id;
                $duplicate['is_recurring'] = 1;
                $duplicate['service_builder_url'] = null;
                $wpdb->insert($table_name, $duplicate);
                $series_date->add($interval);
            }
        }
    }

    spa_log_activity(
        'events',
        $is_new_event ? 'created' : 'updated',
        ($is_new_event ? 'Created event: ' : 'Updated event: ') . $name,
        $event_id
    );
    wp_send_json_success('Event saved');
}

function spa_load_events_page_ajax() {

    global $wpdb;

    if (! check_ajax_referer('spa_admin_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if (! current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $table_name = $wpdb->prefix . 'spa_events';

    $events_per_page = 15;

    $current_page = max(
        1,
        intval($_POST['page'])
    );

    $offset =
        ($current_page - 1)
        * $events_per_page;

    $total_events = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$table_name}
             WHERE active = 1
             AND event_date >= %s",
            date('Y-m-d')
        )
    );

    $total_pages = ceil(
        $total_events / $events_per_page
    );

    $events = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT e.*, s.id AS service_id
             FROM {$table_name} e
             LEFT JOIN {$wpdb->prefix}spa_services s ON s.event_id = e.id AND s.active = 1
             WHERE e.active = 1
             AND e.event_date >= %s
             ORDER BY e.event_date, e.start_time
             LIMIT %d OFFSET %d",
            date('Y-m-d'),
            $events_per_page,
            $offset
        )
    );

    ob_start();

    include SPA_TEMPLATE_DIR . 'event-list.php';

    $html = ob_get_clean();

    wp_send_json_success(
        array(
            'html' => $html
        )
    );
}

function spa_load_event_ajax() {
    global $wpdb;

    if (! check_ajax_referer('spa_admin_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if (! current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $event_id = intval($_POST['event_id']);
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
        wp_send_json_error(array('message' => 'Event not found.'));
    }

    $event_teams = spa_get_event_teams($event_id);

    // Build lookup of assigned team IDs and their volunteers_needed values
    $assigned_team_ids = array();
    $team_volunteers_needed = array();
    foreach ( $event_teams as $et ) {
        $assigned_team_ids[] = intval($et->id);
        $team_volunteers_needed[$et->id] = intval($et->volunteers_needed);
    }

    $final_assignments = spa_get_event_assignments($event_id);

    $assigned_volunteer_ids_by_team = array();
    foreach ( $final_assignments as $assignment ) {
        $assigned_volunteer_ids_by_team[$assignment->team_id][] = intval($assignment->volunteer_id);
    }

    $pending_swaps = spa_get_pending_swap_reminders_for_event($event_id, $event->event_date);

    $rotation_preview_swaps = spa_get_rotation_preview_swaps($event_id);
    $rotation_preview_data = spa_get_rotation_preview_data($event_id);
    $preview_swap_ids = array();
    if ( ! spa_event_rotation_is_applied($event_id) && ! is_wp_error($rotation_preview_data) ) {
        foreach ( $rotation_preview_data['teams'] as $team_result ) {
            foreach ( $team_result['assignments'] as $preview_assignment ) {
                foreach ( $pending_swaps as $pending_swap ) {
                    if (
                        intval($pending_swap->team_id) === intval($team_result['team_id'])
                        && intval($pending_swap->scheduled_volunteer_id) === intval($preview_assignment['volunteer_id'])
                    ) {
                        $preview_swap_ids[intval($pending_swap->id)] = true;
                    }
                }
            }
        }
    }
    foreach ( $pending_swaps as $pending_swap ) {
        $pending_swap->has_current_assignment = ! empty($assigned_volunteer_ids_by_team[$pending_swap->team_id])
            && in_array(intval($pending_swap->scheduled_volunteer_id), $assigned_volunteer_ids_by_team[$pending_swap->team_id], true);
        $pending_swap->is_in_rotation_preview = isset($preview_swap_ids[intval($pending_swap->id)]);
        $pending_swap->is_saved_to_rotation_preview = isset($rotation_preview_swaps[intval($pending_swap->id)]);
    }

    // All teams for checkboxes in the event details form
    $all_teams = $wpdb->get_results(
        "SELECT id, name FROM {$wpdb->prefix}spa_teams WHERE active = 1 ORDER BY name"
    );

    $service_types = $wpdb->get_results(
        "SELECT id, name
         FROM {$wpdb->prefix}spa_service_types
         WHERE active = 1
         ORDER BY name"
    );

    $override_candidates = array();
    foreach ($event_teams as $team) {
        $override_candidates[$team->id] = spa_get_active_team_volunteers($team->id);

    }

    $is_series_parent = is_null($event->parent_event_id) && intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}spa_events WHERE parent_event_id = %d", $event_id))) > 0;
    $can_undo_rotation = spa_get_rotation_undo_state($event_id) !== false;
    $rotation_applied = spa_event_rotation_is_applied($event_id);

    ob_start();
    include SPA_TEMPLATE_DIR . 'ajax-event-details.php';
    $details_html = ob_get_clean();

    ob_start();
    include SPA_TEMPLATE_DIR . 'ajax-event-rotation-panel.php';
    $rotation_html = ob_get_clean();

    ob_start();
    include SPA_TEMPLATE_DIR . 'ajax-event-final-assignments.php';
    $final_assignments_html = ob_get_clean();

    wp_send_json_success(
        array(
            'details' => $details_html,
            'rotation' => $rotation_html,
            'final_assignments' => $final_assignments_html,
            'is_series_parent' => $is_series_parent ? 1 : 0
        )
    );
}

function spa_events_page() {
    global $wpdb;

    $table_name = $wpdb->prefix .'spa_events';
    $editing = false;

    if(isset($_GET['updated'])) {
        echo '<div class="notice notice-success"><p>Event updated successfully.</p></div>';
    }
    if(isset($_GET['added'])) {
        echo '<div class="notice notice-success"><p>Event added successfully.</p></div>';
    }
    if(isset($_GET['deleted'])) {
        echo '<div class="notice notice-success"><p>Event deleted successfully.</p></div>';
    }
    if(isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $editing = true;
        $event_id = intval($_GET['id']);

        if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'edit_event_'. $event_id)) {
            $current_event = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE id = %d", intval($_GET['id'])
                )
            );
        }
    }
	// Delete Event
	if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
		$event_id = intval($_GET['id']);
		
		if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_event_'. $event_id)) {
			$wpdb->update($table_name, array('active' => 0), array('id' => $event_id), array('%d'), array('%d'));		
			wp_redirect(admin_url('admin.php?page=spa-events&deleted=1'));
			exit;
		}
	}
    // Save/update Event
    if(isset($_POST['spa_event_name']) && current_user_can('manage_options')) {
        if(!empty($_POST['event_id'])) {
            $update_scope = isset($_POST['update_scope']) ? sanitize_text_field(wp_unslash($_POST['update_scope'])) : 'parent';
            if($update_scope === 'series') {
                    // Will do later
            } else {
                $wpdb->update($table_name, array(
                    'name' => sanitize_text_field($_POST['spa_event_name']),
                    'season' => isset($_POST['spa_event_season']) ? sanitize_text_field(wp_unslash($_POST['spa_event_season'])) : '',
                    'special_day' => isset($_POST['spa_event_special_day']) ? sanitize_text_field(wp_unslash($_POST['spa_event_special_day'])) : '',
                    'event_date' => wp_unslash($_POST['spa_event_date']),
                    'start_time' => wp_unslash($_POST['spa_event_start_time']),
                    'end_time' => wp_unslash($_POST['spa_event_end_time']),
                    'description' => sanitize_textarea_field($_POST['spa_event_description']),
                    'location' => sanitize_text_field($_POST['spa_event_location']),
                    'is_recurring' => isset($_POST['spa_event_is_recurring']) ? 1 : 0,
                    'recurrence_type' => sanitize_text_field(wp_unslash($_POST['spa_event_recurrence_type'])),
                    'recurrence_end_date' => sanitize_text_field(wp_unslash($_POST['spa_event_recurrence_end_date'])),
                    'notify_volunteers' => isset($_POST['notify_volunteers']) ? 1 : 0
                ), array('id' => intval($_POST['event_id'])
                ));
                $event_id = intval($_POST['event_id']);
                $event_volunteers_table = $wpdb->prefix . 'spa_event_volunteers';
                $wpdb->delete($event_volunteers_table, array('event_id' => $event_id));
                spa_save_event_team_requirements(
                    $event_id,
                    isset($_POST['event_teams']) ? (array) $_POST['event_teams'] : array(),
                    isset($_POST['volunteers_needed']) ? (array) $_POST['volunteers_needed'] : array()
                );
            }
			wp_redirect(admin_url('admin.php?page=spa-events&updated=1'));
			exit;
        } else {
            $wpdb->insert($table_name, array(
                'name' => sanitize_text_field($_POST['spa_event_name']),
                'season' => isset($_POST['spa_event_season']) ? sanitize_text_field(wp_unslash($_POST['spa_event_season'])) : '',
                'special_day' => isset($_POST['spa_event_special_day']) ? sanitize_text_field(wp_unslash($_POST['spa_event_special_day'])) : '',
                'event_date' => wp_unslash($_POST['spa_event_date']),
                'start_time' => wp_unslash($_POST['spa_event_start_time']),
                'end_time' => wp_unslash($_POST['spa_event_end_time']),
                'description' => sanitize_textarea_field($_POST['spa_event_description']),
                'location' => sanitize_text_field($_POST['spa_event_location']),
                'is_recurring' => isset($_POST['spa_event_is_recurring']) ? 1 : 0,
                'recurrence_type' => sanitize_text_field(wp_unslash($_POST['spa_event_recurrence_type'])),
                'recurrence_end_date' => sanitize_text_field(wp_unslash($_POST['spa_event_recurrence_end_date'])),
                'notify_volunteers' => isset($_POST['notify_volunteers']) ? 1 : 0
            ));
            $parent_event_id = $wpdb->insert_id;

            spa_save_event_team_requirements(
                $parent_event_id,
                isset($_POST['event_teams']) ? (array) $_POST['event_teams'] : array(),
                isset($_POST['volunteers_needed']) ? (array) $_POST['volunteers_needed'] : array()
            );

            $current_date = new DateTime(wp_unslash($_POST['spa_event_date']));
            $end_date = !empty($_POST['spa_event_recurrence_end_date'])
                ? new DateTime(wp_unslash($_POST['spa_event_recurrence_end_date']))
                : null;

            switch(sanitize_text_field(wp_unslash($_POST['spa_event_recurrence_type']))) {
                case 'daily':
                    $interval = '+1 day';
                    break;
                case 'weekly':
                    $interval = '+1 week';
                    break;
                case 'monthly':
                    $interval = '+1 month';
                    break;
                default:
                    $interval = null;
                    break;
            }

            if($interval && $end_date) {
                $current_date->modify($interval);
                while($current_date <= $end_date) {
                    $wpdb->insert($table_name, array(
                        'name' => sanitize_text_field($_POST['spa_event_name']),
                        'season' => isset($_POST['spa_event_season']) ? sanitize_text_field(wp_unslash($_POST['spa_event_season'])) : '',
                        'special_day' => isset($_POST['spa_event_special_day']) ? sanitize_text_field(wp_unslash($_POST['spa_event_special_day'])) : '',
                        'event_date' => $current_date->format('Y-m-d'),
                        'start_time' => wp_unslash($_POST['spa_event_start_time']),
                        'end_time' => wp_unslash($_POST['spa_event_end_time']),
                        'description' => sanitize_textarea_field($_POST['spa_event_description']),
                        'location' => sanitize_text_field($_POST['spa_event_location']),
                        'is_recurring' => 1,
                        'recurrence_type' => sanitize_text_field(wp_unslash($_POST['spa_event_recurrence_type'])),
                        'recurrence_end_date' => sanitize_text_field(wp_unslash($_POST['spa_event_recurrence_end_date'])),
                        'parent_event_id' => $parent_event_id
                    ));
                    $recurring_event_id = $wpdb->insert_id;
                    spa_save_event_team_requirements(
                        $recurring_event_id,
                        isset($_POST['event_teams']) ? (array) $_POST['event_teams'] : array(),
                        isset($_POST['volunteers_needed']) ? (array) $_POST['volunteers_needed'] : array()
                    );
                    $current_date->modify($interval);
                }
            }
			wp_redirect(admin_url('admin.php?page=spa-events&added=1'));
			exit;
        }
    }

    // Adding and Editing Form
    $is_parent_event = false;
    $assigned_teams = array();
    $volunteers_needed = array();

    if($editing && $current_event && is_null($current_event->parent_event_id)) {
        $child_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE parent_event_id = %d", $current_event->id));
        if($child_count > 0) {
            $is_parent_event = true;
        }
    }


    $events_per_page = 15;

    $current_page = isset($_GET['event_page'])
        ? max(1, intval($_GET['event_page']))
        : 1;

    $offset = ($current_page - 1) * $events_per_page;

    $total_events = $wpdb->get_var(
        "SELECT COUNT(*)
         FROM {$table_name}
         WHERE active = 1
         AND event_date >= CURDATE()"
    );

    $total_pages = ceil(
        $total_events / $events_per_page
    );

    $events = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT e.*, s.id AS service_id
             FROM {$table_name} e
             LEFT JOIN {$wpdb->prefix}spa_services s ON s.event_id = e.id AND s.active = 1
             WHERE e.active = 1
             AND e.event_date >= %s
             ORDER BY e.event_date, e.start_time
             LIMIT %d OFFSET %d",
            date('Y-m-d'),
            $events_per_page,
            $offset
        )
    );

    // Page title
    $page_title = "Events";
    include SPA_TEMPLATE_DIR .'events-page.php';
}
?>