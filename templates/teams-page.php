<?php include SPA_TEMPLATE_DIR .'header.php'; ?>

<div class="wrap">

        <h1>Teams</h1>

        <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('spa_save_teams', 'spa_teams_nonce'); ?>
            <input type="hidden" name="action" value="spa_save_team">

            <?php if($editing && $current_team) : ?>

                <input type="hidden" name="team_id" value="<?php echo intval($current_team->id); ?>">

            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th>
                    <label for="spa_team_name">Team Name</label>
                    </th>
                    <td>
                    <input type="text" id="spa_team_name" name="spa_team_name" class="regular-text" value="<?php echo $editing ? esc_attr(wp_unslash($current_team->name)) : ''; ?>" required />
                    </td>
                </tr>
                <tr>
                    <th>
                    <label for="spa_team_description">Description</label>
                    </th>
                    <td>
                    <textarea id="spa_team_description" name="spa_team_description" rows="5" cols="50"><?php echo $editing ? esc_textarea(wp_unslash($current_team->description)) : ''; ?></textarea>
                    </td>
                </tr>
            </table>

            <?php submit_button($editing ? 'Update Team' : 'Add Team'); ?>
        </form>
    <?php

    // Display existing teams
    $teams = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY name");

    if($teams) {
        echo '<h2>Existing Teams</h2>';

        echo '<table class="widefat striped">';

        echo '<thead>
            <tr>
                <th>Team Name</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
            </thead>';

        echo '<tbody>';

        foreach ($teams AS $team) {
            $edit_url = wp_nonce_url(admin_url('admin.php?page=spa-teams&action=edit&id=' . $team->id),
                'edit_team_' . $team->id);
            $delete_url = wp_nonce_url(admin_url('admin.php?page=spa-teams&action=delete&id=' . $team->id),
                'delete_team_' . $team->id);

            echo '<tr>';

            echo '<td>' . esc_html(wp_unslash($team->name)) . '</td>';

            echo '<td>' . esc_html(wp_unslash($team->description)) . '</td>';

            echo '<td>';

            echo '<a href="'. esc_url($edit_url) .'">Edit</a>';

            echo ' | ';

            echo '<a href="'. esc_url($delete_url) .'" onclick="confirm(\'Are you SURE you want to do this??\');">Delete</a>';

            echo '</td>';

            echo '</tr>';
        }

        echo '</tbody>';

        echo '</table>';
    }

    echo '</div>';
?>