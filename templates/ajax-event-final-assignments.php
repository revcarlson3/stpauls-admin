<h3 style="margin-top:0;">Final Assignments</h3>

<?php if ( empty($final_assignments) ) : ?>
    <p>No volunteers assigned yet.</p>
<?php else : ?>
    <ul class="spa-volunteer-list">
        <?php foreach ( $final_assignments as $assignment ) : ?>
            <li>
                <strong><?php echo esc_html($assignment->team_name); ?>:</strong>
                <?php echo esc_html($assignment->volunteer_name); ?>
                <?php if ( ! empty($assignment->is_override) ) : ?>
                    <span style="color:#b32d2e;">(Override)</span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
