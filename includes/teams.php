<?php
// Teams functions

function spa_handle_teams_post() {
    // Handle team saves via admin-post.php
    if ( ! isset($_POST['spa_teams_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['spa_teams_nonce']), 'spa_save_teams') ) {
        wp_die('Invalid nonce', 'Error', array('response' => 403));
    }
    if ( ! current_user_can('manage_options') ) {
        wp_die('Unauthorized', 'Error', array('response' => 403));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'spa_teams';
    
    $team_name = isset($_POST['spa_team_name']) ? sanitize_text_field(wp_unslash($_POST['spa_team_name'])) : '';
    $team_description = isset($_POST['spa_team_description']) ? sanitize_textarea_field(wp_unslash($_POST['spa_team_description'])) : '';
    $team_id = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;

    if (!empty($team_id)) {
        $wpdb->update($table_name, array(
            'name' => $team_name,
            'description' => $team_description
        ), array('id' => $team_id));
        wp_redirect(admin_url('admin.php?page=spa-teams&updated=1'));
    } else {
        $existing_team_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE name = %s LIMIT 1",
            $team_name
        ));
        if ($existing_team_id) {
            $wpdb->update($table_name, array(
                'description' => $team_description,
                'active' => 1
            ), array('id' => $existing_team_id), array('%s', '%d'), array('%d'));
            wp_redirect(admin_url('admin.php?page=spa-teams&updated=1'));
            exit;
        }
        $wpdb->insert($table_name, array(
            'name' => $team_name,
            'description' => $team_description,
            'active' => 1
        ));
        wp_redirect(admin_url('admin.php?page=spa-teams&added=1'));
    }
    exit;
}
add_action('admin_post_spa_save_team', 'spa_handle_teams_post');

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
                    "SELECT * FROM {$table_name} WHERE id = %d AND active = 1",
                    intval($_GET['id'])
                )
            );
        }
    }
	// Delete Team
	if(isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
		$team_id = intval($_GET['id']);
		
		if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_team_'. $team_id)) {
			$wpdb->update($table_name, array('active' => 0), array('id' => $team_id), array('%d'), array('%d'));		
			wp_redirect(admin_url('admin.php?page=spa-teams&deleted=1'));
			exit;
		}
	}

    // Page title
    $page_title = "Teams";
    include plugin_dir_path(__FILE__) . '../templates/teams-page.php';

}
?>