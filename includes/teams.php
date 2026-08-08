<?php
// Teams functions
function spa_teams_page() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'spa_teams';
    $editing = false;
    $current_team = null;

	if(isset($_GET['updated'])) {
    	echo '<div class="notice notice-success"><p>Team updated successfully.</p></div>';
	}
	if(isset($_GET['added'])) {
    	echo '<div class="notice notice-success"><p>Team added successfully.</p></div>';
	}
	if(isset($_GET['deleted'])) {
    	echo '<div class="notice notice-success"><p>Team deleted successfully.</p></div>';		
	}
	
    if(isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $editing = true;
        $team_id = intval($_GET['id']);

        if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'edit_team_'. $team_id)) {
            $current_team = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} WHERE id = %d", intval($_GET['id'])
                )
            );
        }
    }
	// Delete Team
	if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
		$team_id = intval($_GET['id']);
		
		if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_team_'. $team_id)) {
			$wpdb->delete($table_name, array('id' => $team_id));		
			wp_redirect(admin_url('admin.php?page=spa-teams&deleted=1'));
			exit;
		}
	}

    // Save/update Team
    if(isset($_POST['spa_team_name']) && current_user_can('manage_options')) {
        if(!empty($_POST['team_id'])) {
            $wpdb->update($table_name, array(
                'name' => sanitize_text_field($_POST['spa_team_name']),
                'description' => sanitize_textarea_field($_POST['spa_team_description'])
            ), array('id' => intval($_POST['team_id'])
            ));
			wp_redirect(admin_url('admin.php?page=spa-teams&updated=1'));
			exit;
        } else {
            $wpdb->insert($table_name, array(
                'name'=> sanitize_text_field($_POST['spa_team_name']),
                'description'=> sanitize_textarea_field($_POST['spa_team_description'])
            ));
			wp_redirect(admin_url('admin.php?page=spa-teams&added=1'));
			exit;
        }
    }

    // Page title
    $page_title = "Teams";
    include plugin_dir_path(__FILE__) . '../templates/teams-page.php';

}
?>