<?php include SPA_TEMPLATE_DIR .'header.php'; ?>

<div class="wrap">

        <h1>Volunteers</h1>

        <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('spa_save_volunteers', 'spa_volunteers_nonce'); ?>
            <input type="hidden" name="action" value="spa_save_volunteer">

            <?php
            $assigned_teams = array();
            
            if($editing && $current_volunteer) : ?>

                <input type="hidden" name="volunteer_id" value="<?php echo intval($current_volunteer->id); ?>">

                <?php
                $assigned_teams = $wpdb->get_col($wpdb->prepare(
                    "SELECT team_id FROM {$wpdb->prefix}spa_volunteer_teams WHERE volunteer_id = %d", $current_volunteer->id
                ));

            endif;
            ?>

            <table class="form-table">
                <tr>
                    <th>
                    <label for="spa_volunteer_first_name">Volunteer First Name</label>
                    </th>
                    <td colspan="2">
                    <input type="text" id="spa_volunteer_first_name" name="spa_volunteer_first_name" class="regular-text" value="<?php echo $editing ? esc_attr(wp_unslash($current_volunteer->first_name)) : ''; ?>" required />
                    </td>
                </tr>
                <tr>
                    <th>
                    <label for="spa_volunteer_last_name">Last Name</label>
                    </th>
                    <td colspan="2">
                    <input type="text" id="spa_volunteer_last_name" name="spa_volunteer_last_name" class="regular-text" value="<?php echo $editing ? esc_attr(wp_unslash($current_volunteer->last_name)) : ''; ?>" required />
                    </td>
                </tr>
                <tr>
                    <th>
                    <label for="spa_volunteer_phone">Cell Phone/SMS Number</label>
                    </th>
                    <td>
                    <input type="tel" pattern="^\+[1-9]\d{1,14}$" id="spa_volunteer_phone" name="spa_volunteer_phone" class="regular-text" value="<?php echo $editing ? esc_attr(wp_unslash($current_volunteer->phone)) : ''; ?>" required />
                    <p class="description">Enter phone number in E.164 format (example: +13209999999) without dashes or perantheses.</p>
                    </td>
                    <td>
                    <label>
                    <input type="checkbox" name="phone_enabled" value="1" <?php checked($current_volunteer->phone_enabled, 1); ?> /> SMS Enabled
                    </label>
                    </td>
                </tr>
                <tr>
                    <th>
                    <label for="spa_volunteer_email">Email Address</label>
                    </th>
                    <td>
                     <input type="email" id="spa_volunteer_email" name="spa_volunteer_email" class="regular-text" value="<?php echo $editing ? esc_attr(wp_unslash($current_volunteer->email)) : ''; ?>" required />
                    </td>
                    <td>
                    <label>
                    <input type="checkbox" name="email_enabled" value="1" <?php checked($current_volunteer->email_enabled, 1); ?> /> Email Enabled
                    </label>
                    </td>
                </tr>
            </table>

            <h2>Teams</h2>

            <?php
            $teams = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}spa_teams ORDER BY name");

            echo '<div class="spa-team-columns">';

            foreach($teams as $team) {
                echo '<label>';

                echo '<input type="checkbox" ';
                echo 'name="teams[]" ';
                echo 'value="'. intval($team->id) .'" ';

                if(in_array($team->id, $assigned_teams)) {
                    echo 'checked ';
                }

                echo '/>';

                echo esc_html($team->name);

                echo '</label>';
            }

            echo '</div>';
            ?>

            <?php submit_button($editing ? 'Update Volunteer' : 'Add Volunteer'); ?>
        </form>
    <?php

    // Display existing volunteers
    if(!$editing) {
        $volunteers = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY last_name");

        if($volunteers) {
            echo '<h2>Existing Volunteers</h2>';

            echo '<table class="widefat striped">';

            echo '<thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Cell/SMS Number</th>
                    <th>Email Address</th>
                    <th>Actions</th>
                </tr>
                </thead>';

            echo '<tbody>';

            foreach ($volunteers AS $volunteer) {
                $email = esc_html($volunteer->email);
                if($volunteer->email_enabled) {
                    $email .= ' <span style="color:green;">✓</span>';
                } else {
                    $email .= ' <span style="color:red;">✗</span>';
                }

                $phone = esc_html($volunteer->phone);
                if($volunteer->phone_enabled) {
                    $phone .= ' <span style="color:green;">✓</span>';
                } else {
                    $phone .= ' <span style="color:red;">✗</span>';
                }
                $edit_url = wp_nonce_url(admin_url('admin.php?page=spa-volunteers&action=edit&id=' . $volunteer->id),
                    'edit_volunteer_' . $volunteer->id);
                $delete_url = wp_nonce_url(admin_url('admin.php?page=spa-volunteers&action=delete&id=' . $volunteer->id),
                    'delete_volunteer_' . $volunteer->id);

                echo '<tr>';

                echo '<td>' . esc_html(wp_unslash($volunteer->first_name)) . '</td>';

                echo '<td>' . esc_html(wp_unslash($volunteer->last_name)) . '</td>';

                echo '<td>' . $phone . '</td>';

                echo '<td>' . $email . '</td>';

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
    }
?>