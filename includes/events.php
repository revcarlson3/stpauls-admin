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
    'wp_ajax_spa_toggle_volunteer',
    'spa_toggle_volunteer_ajax'
);

add_action(
    'wp_ajax_spa_save_event_modal',
    'spa_save_event_modal_ajax'
);

function spa_toggle_volunteer_ajax() {
    global $wpdb;

    if (! check_ajax_referer('spa_admin_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if (! current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $event_id = intval($_POST['event_id']);
    $team_id = intval($_POST['team_id']);
    $volunteer_id = intval($_POST['volunteer_id']);
    $assigned = intval($_POST['assigned']);

    $table = $wpdb->prefix . 'spa_event_volunteers';

    if ($assigned) {

        $wpdb->replace(
            $table,
            array(
                'event_id' => $event_id,
                'team_id' => $team_id,
                'volunteer_id' => $volunteer_id
            )
        );

    } else {

        $wpdb->delete(
            $table,
            array(
                'event_id' => $event_id,
                'team_id' => $team_id,
                'volunteer_id' => $volunteer_id
            )
        );

    }

    wp_send_json_success();
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
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $location = isset($_POST['location']) ? sanitize_text_field(wp_unslash($_POST['location'])) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
    $event_date = isset($_POST['event_date']) ? sanitize_text_field(wp_unslash($_POST['event_date'])) : '';
    $start_time = isset($_POST['start_time']) ? sanitize_text_field(wp_unslash($_POST['start_time'])) : '';
    $end_time = isset($_POST['end_time']) ? sanitize_text_field(wp_unslash($_POST['end_time'])) : '';
    $is_recurring = isset($_POST['is_recurring']) ? intval($_POST['is_recurring']) : 0;
    $recurrence_type = isset($_POST['recurrence_type']) ? sanitize_text_field(wp_unslash($_POST['recurrence_type'])) : '';
    $recurrence_end_date = isset($_POST['recurrence_end_date']) ? sanitize_text_field(wp_unslash($_POST['recurrence_end_date'])) : '';

    if (empty($name) || empty($event_date)) {
        wp_send_json_error('Event name and date are required');
    }

    $data = array(
        'name' => $name,
        'location' => $location,
        'description' => $description,
        'event_date' => $event_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'is_recurring' => $is_recurring,
        'recurrence_type' => $recurrence_type,
        'recurrence_end_date' => $recurrence_end_date
    );

    if ($event_id > 0) {
        $wpdb->update($table_name, $data, array('id' => $event_id));
    } else {
        $wpdb->insert($table_name, $data);
    }

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
             WHERE event_date >= %s",
            date('Y-m-d')
        )
    );

    $total_pages = ceil(
        $total_events / $events_per_page
    );

    $events = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT *
             FROM {$table_name}
             WHERE event_date >= %s
             ORDER BY event_date, start_time
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
             WHERE id = %d",
            $event_id
        )
    );

    $event_teams = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT
                 t.id,
                 t.name,
                 et.volunteers_needed
             FROM {$wpdb->prefix}spa_events_teams et
             INNER JOIN {$wpdb->prefix}spa_teams t
                 ON et.team_id = t.id
             WHERE et.event_id = %d
             ORDER BY t.name",
            $event_id
        )
    );

    $assigned_volunteers = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT team_id, volunteer_id
             FROM {$wpdb->prefix}spa_event_volunteers
             WHERE event_id = %d",
            $event_id
        )
    );

    $assigned_lookup = array();

    foreach($assigned_volunteers AS $assignment) {
        $assigned_lookup[$assignment->team_id][$assignment->volunteer_id] = true;
    }

    foreach ($event_teams as $team) {

        $team->volunteers = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    v.id,
                    v.first_name,
                    v.last_name
                 FROM {$wpdb->prefix}spa_volunteers v
                 INNER JOIN {$wpdb->prefix}spa_volunteer_teams vt
                    ON v.id = vt.volunteer_id
                 WHERE vt.team_id = %d
                 ORDER BY v.last_name, v.first_name",
                $team->id
            )
        );

    }

    if (!$event) {
        wp_send_json_error(
            array(
                'message' => 'Event not found.'
            )
        );
    }

    ob_start();
    include SPA_TEMPLATE_DIR . 'ajax-event-details.php';
    $details_html = ob_get_clean();

    ob_start();
    include SPA_TEMPLATE_DIR .'ajax-event-volunteers.php';
    $volunteers_html = ob_get_clean();

    wp_send_json_success(
        array(
            'details' => $details_html,
            'volunteers' => $volunteers_html
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
			$wpdb->delete($table_name, array('id' => $event_id));		
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
                    'event_date' => wp_unslash($_POST['spa_event_date']),
                    'start_time' => wp_unslash($_POST['spa_event_start_time']),
                    'end_time' => wp_unslash($_POST['spa_event_end_time']),
                    'description' => sanitize_textarea_field($_POST['spa_event_description']),
                    'location' => sanitize_text_field($_POST['spa_event_location']),
                    'is_recurring' => isset($_POST['spa_event_is_recurring']) ? 1 : 0,
                    'recurrence_type' => sanitize_text_field(wp_unslash($_POST['spa_event_recurrence_type'])),
                    'recurrence_end_date' => sanitize_text_field(wp_unslash($_POST['spa_event_recurrence_end_date']))
                ), array('id' => intval($_POST['event_id'])
                ));
                $event_id = intval($_POST['event_id']);
                $event_teams_table = $wpdb->prefix .'spa_events_teams';
                $wpdb->delete($event_teams_table, array(
                    'event_id' => $event_id
                ));
                if(isset($_POST['event_teams'])) {
                    foreach($_POST['event_teams'] AS $team_id) {
                        $wpdb->insert($event_teams_table, array(
                            'event_id' => $event_id,
                            'team_id' => intval($team_id),
                            'volunteers_needed' => intval($_POST['volunteers_needed'][$team_id])
                        ));
                    }
                }
            }
			wp_redirect(admin_url('admin.php?page=spa-events&updated=1'));
			exit;
        } else {
            $wpdb->insert($table_name, array(
                'name' => sanitize_text_field($_POST['spa_event_name']),
                'event_date' => wp_unslash($_POST['spa_event_date']),
                'start_time' => wp_unslash($_POST['spa_event_start_time']),
                'end_time' => wp_unslash($_POST['spa_event_end_time']),
                'description' => sanitize_textarea_field($_POST['spa_event_description']),
                'location' => sanitize_text_field($_POST['spa_event_location']),
                'is_recurring' => isset($_POST['spa_event_is_recurring']) ? 1 : 0,
                'recurrence_type' => sanitize_text_field(wp_unslash($_POST['spa_event_recurrence_type'])),
                'recurrence_end_date' => sanitize_text_field(wp_unslash($_POST['spa_event_recurrence_end_date']))                
            ));
            $parent_event_id = $wpdb->insert_id;

            if(isset($_POST['event_teams'])) {
                foreach($_POST['event_teams'] AS $team_id) {
                    $wpdb->insert($event_teams_table, array(
                        'event_id' => $parent_event_id,
                        'team_id' => intval($team_id),
                        'volunteers_needed' => intval($_POST['volunteers_needed'][$team_id])
                    ));
                }
            }

            $current_date = new DateTime(wp_unslash($_POST['spa_event_date']));
            $end_date = new DateTime(wp_unslash($_POST['spa_event_recurrence_end_date']));

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

            if($interval) {
                $current_date->modify($interval);
                while($current_date <= $end_date) {
                    $wpdb->insert($table_name, array(
                        'name' => sanitize_text_field($_POST['spa_event_name']),
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
                    if(isset($_POST['event_teams'])) {
                        foreach($_POST['event_teams'] AS $team_id) {
                            $wpdb->insert($event_teams_table, array( 
                                'event_id' => $recurring_event_id,
                                'team_id' => intval($team_id),
                                'volunteers_needed' => intval($_POST['volunteers_needed'][$team_id])
                            ));
                        }
                    }
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
         WHERE event_date >= CURDATE()"
    );

    $total_pages = ceil(
        $total_events / $events_per_page
    );

    $events = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT *
             FROM {$table_name}
             WHERE event_date >= %s
             ORDER BY event_date, start_time
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