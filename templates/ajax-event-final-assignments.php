<?php if ( empty($final_assignments) ) : ?>
    <p>No volunteers assigned yet.</p>
<?php else : ?>
    <ul class="spa-volunteer-list">
        <?php foreach ( $final_assignments as $assignment ) : ?>
            <li>
                <strong><?php echo esc_html($assignment->team_name); ?>:</strong>
                <?php echo esc_html($assignment->volunteer_name); ?>
                <?php if ( ! $assignment->team_active || ! $assignment->volunteer_active ) : ?>
                    <span style="color:#646970;">(Inactive)</span>
                <?php endif; ?>
                <?php if ( ! empty($assignment->is_override) ) : ?>
                    <span style="color:#b32d2e;">(Override)</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
