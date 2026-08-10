<?php
// Volunteers functions

function spa_normalize_phone_to_e164($phone) {
    $phone = trim((string) $phone);

    if ( $phone === '' ) {
        return '';
    }

    $has_plus = strpos($phone, '+') === 0;
    $digits = preg_replace('/\D+/', '', $phone);

    if ( $digits === '' ) {
        return '';
    }

    if ( $has_plus ) {
        $normalized = '+' . $digits;
    } elseif ( strlen($digits) === 10 ) {
        $normalized = '+1' . $digits;
    } elseif ( strlen($digits) === 11 && strpos($digits, '1') === 0 ) {
        $normalized = '+' . $digits;
    } else {
        return '';
    }

    if ( ! preg_match('/^\+[1-9]\d{1,14}$/', $normalized) ) {
        return '';
    }

    return $normalized;
}

function spa_handle_volunteers_post() {
    // Handle volunteer saves via admin-post.php
    if ( ! isset($_POST['spa_volunteers_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['spa_volunteers_nonce']), 'spa_save_volunteers') ) {
        wp_die('Invalid nonce', 'Error', array('response' => 403));
    }
    if ( ! current_user_can('manage_options') ) {
        wp_die('Unauthorized', 'Error', array('response' => 403));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'spa_volunteers';
    $volunteer_teams_table = $wpdb->prefix . 'spa_volunteer_teams';

    $phone = spa_normalize_phone_to_e164(wp_unslash($_POST['spa_volunteer_phone']));
    if($phone === '') {
        wp_redirect(admin_url('admin.php?page=spa-volunteers&error=invalid_phone'));
        exit;
    }

    $email = sanitize_email(wp_unslash($_POST['spa_volunteer_email']));
    if(!is_email($email)) {
        wp_redirect(admin_url('admin.php?page=spa-volunteers&error=invalid_email'));
        exit;
    }

    $first_name = sanitize_text_field(wp_unslash($_POST['spa_volunteer_first_name']));
    $last_name = sanitize_text_field(wp_unslash($_POST['spa_volunteer_last_name']));
    $email_enabled = isset($_POST['email_enabled']) ? 1 : 0;
    $phone_enabled = isset($_POST['phone_enabled']) ? 1 : 0;
    $volunteer_id = isset($_POST['volunteer_id']) ? intval($_POST['volunteer_id']) : 0;

    if (!empty($volunteer_id)) {
        $wpdb->update($table_name, array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'email' => $email,
            'phone_enabled' => $phone_enabled,
            'email_enabled' => $email_enabled
        ), array('id' => $volunteer_id));

        // Update team assignments
        $wpdb->delete($volunteer_teams_table, array('volunteer_id' => $volunteer_id));
        if(isset($_POST['teams'])) {
            foreach((array)$_POST['teams'] as $team_id) {
                $wpdb->insert($volunteer_teams_table, array(
                    'volunteer_id' => $volunteer_id,
                    'team_id' => intval($team_id)
                ));
            }
        }
        wp_redirect(admin_url('admin.php?page=spa-volunteers&updated=1'));
    } else {
        $existing_volunteer_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE first_name = %s AND last_name = %s AND email = %s AND phone = %s LIMIT 1",
            $first_name,
            $last_name,
            $email,
            $phone
        ));
        if ($existing_volunteer_id) {
            $wpdb->update($table_name, array(
                'phone_enabled' => $phone_enabled,
                'email_enabled' => $email_enabled,
                'active' => 1
            ), array('id' => $existing_volunteer_id), array('%d', '%d', '%d'), array('%d'));
            $volunteer_id = intval($existing_volunteer_id);
            $wpdb->delete($volunteer_teams_table, array('volunteer_id' => $volunteer_id));
            if(isset($_POST['teams'])) {
                foreach((array)$_POST['teams'] as $team_id) {
                    $wpdb->insert($volunteer_teams_table, array(
                        'volunteer_id' => $volunteer_id,
                        'team_id' => intval($team_id)
                    ));
                }
            }
            wp_redirect(admin_url('admin.php?page=spa-volunteers&updated=1'));
            exit;
        }
        $wpdb->insert($table_name, array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'phone' => $phone,
            'email' => $email,
            'phone_enabled' => $phone_enabled,
            'email_enabled' => $email_enabled,
            'active' => 1
        ));
        $volunteer_id = $wpdb->insert_id;

        if(isset($_POST['teams'])) {
            foreach((array)$_POST['teams'] as $team_id) {
                $wpdb->insert($volunteer_teams_table, array(
                    'volunteer_id' => $volunteer_id,
                    'team_id' => intval($team_id)
                ));
            }
        }
        wp_redirect(admin_url('admin.php?page=spa-volunteers&added=1'));
    }
    exit;
}
add_action('admin_post_spa_save_volunteer', 'spa_handle_volunteers_post');

function spa_volunteers_page() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'spa_volunteers';
    $volunteer_teams_table = $wpdb->prefix .'spa_volunteer_teams';

    $editing = false;
    $current_volunteer = null;

	if(isset($_GET['updated'])) {
    	echo '<div class="notice notice-success"><p>Volunteer updated successfully.</p></div>';
	}
	if(isset($_GET['added'])) {
    	echo '<div class="notice notice-success"><p>Volunteer added successfully.</p></div>';
	}
	if(isset($_GET['deleted'])) {
    	echo '<div class="notice notice-success"><p>Volunteer deleted successfully.</p></div>';		
	}
	if(isset($_GET['error'])) {
		if($_GET['error'] === 'invalid_phone') {
			echo '<div class="notice notice-error"><p>Phone number could not be normalized. Use a valid number such as (320) 123-4567, 320-123-4567, or +13201234567.</p></div>';
		} elseif($_GET['error'] === 'invalid_email') {
			echo '<div class="notice notice-error"><p>Please enter a valid email address.</p></div>';
		}
	}
	
    if(isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $editing = true;
        $volunteer_id = intval($_GET['id']);

        if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'edit_volunteer_'. $volunteer_id)) {
            $current_volunteer = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE id = %d AND active = 1",
                    intval($_GET['id'])
                )
            );
        }
    }
	// Delete Volunteer
	if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
		$volunteer_id = intval($_GET['id']);
		
		if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_volunteer_'. $volunteer_id)) {
			$wpdb->update($table_name, array('active' => 0), array('id' => $volunteer_id), array('%d'), array('%d'));		
			wp_redirect(admin_url('admin.php?page=spa-volunteers&deleted=1'));
			exit;
		}
	}

    // Page Title
    $page_title = "Volunteers";
    include plugin_dir_path(__FILE__) . '../templates/volunteers-page.php';
}
?>