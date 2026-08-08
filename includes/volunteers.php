<?php
// Volunteers functions
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
	
    if(isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $editing = true;
        $volunteer_id = intval($_GET['id']);

        if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'edit_volunteer_'. $volunteer_id)) {
            $current_volunteer = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE id = %d", intval($_GET['id'])
                )
            );
        }
    }
	// Delete Volunteer
	if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
		$volunteer_id = intval($_GET['id']);
		
		if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_volunteer_'. $volunteer_id)) {
			$wpdb->delete($table_name, array('id' => $volunteer_id));		
			wp_redirect(admin_url('admin.php?page=spa-volunteers&deleted=1'));
			exit;
		}
	}

    // Save/update Volunteer
    $assigned_teams = array();

    if(isset($_POST['spa_volunteer_first_name']) && current_user_can('manage_options')) {
        $phone = trim(wp_unslash($_POST['spa_volunteer_phone']));
        if(!preg_match('/^\+[1-9]\d{1,14}$/', $phone)) {
            echo '<div class="notice notice-error>';
            echo '<p>Phone number must be in E.164 format (example: +13209999) and without dashes or perantheses.</p>';
            echo '</div>';

            return;
        }

        $email = sanitize_email(wp_unslash($_POST['spa_volunteer_email']));
        if(!is_email($email)) {
            echo '<div class="notice notice-error">';
            echo '<p>Please enter a valid email address.</p>';
            echo '</div>';

            return;            
        }

        $email_enabled = isset($_POST['email_enabled']) ? 1 : 0;
        $phone_enabled  = isset($_POST['phone_enabled']) ? 1 : 0;

        if(!empty($_POST['volunteer_id'])) {
            $wpdb->update($table_name, array(
                'first_name' => sanitize_text_field($_POST['spa_volunteer_first_name']),
                'last_name' => sanitize_textarea_field($_POST['spa_volunteer_last_name']),
                'phone' => sanitize_textarea_field($_POST['spa_volunteer_phone']),
                'email' => sanitize_textarea_field($_POST['spa_volunteer_email']),
                'phone_enabled' => $phone_enabled,
                'email_enabled' => $email_enabled
            ), array('id' => intval($_POST['volunteer_id'])
            ));
            $volunteer_id = intval($_POST['volunteer_id']);
            $wpdb->delete($volunteer_teams_table, array(
                'volunteer_id' => $volunteer_id
            ));
            if(isset($_POST['teams'])) {
                foreach($_POST['teams'] AS $team_id) {
                    $wpdb->insert($volunteer_teams_table, array(
                        'volunteer_id' => $volunteer_id,
                        'team_id' => intval($team_id)
                    ));
                }
            }

			wp_redirect(admin_url('admin.php?page=spa-volunteers&updated=1'));
			exit;
        } else {
            $wpdb->insert($table_name, array(
                'first_name' => sanitize_text_field($_POST['spa_volunteer_first_name']),
                'last_name' => sanitize_textarea_field($_POST['spa_volunteer_last_name']),
                'phone' => sanitize_textarea_field($_POST['spa_volunteer_phone']),
                'email' => sanitize_textarea_field($_POST['spa_volunteer_email']),
                'phone_enabled' => $phone_enabled,
                'email_enabled' => $email_enabled
            ));
            $volunteer_id = $wpdb->insert_id;

            if(isset($_POST['teams'])) {
                foreach($_POST['teams'] AS $team_id) {
                    $wpdb->insert($volunteer_teams_table, array(
                        'volunteer_id' => $volunteer_id,
                        'team_id' => intval($team_id)
                    ));
                }
            }
			wp_redirect(admin_url('admin.php?page=spa-volunteers&added=1'));
			exit;
        }
    }

    // Page Title
    $page_title = "Volunteers";
    include plugin_dir_path(__FILE__) . '../templates/volunteers-page.php';
}
?>