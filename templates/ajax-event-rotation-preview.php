<?php if ( empty($rotation_preview) ) : ?>
    <p>No team assignments are available for this event yet.</p>
<?php else : ?>
    <div class="spa-rotation-preview-list">
        <?php foreach ( $rotation_preview as $team_result ) : ?>
            <div class="spa-team-card" style="margin-bottom:16px;">
                <h3><?php echo esc_html($team_result['team_name']); ?></h3>
                <p style="margin:0 0 8px;"><strong>Needed:</strong> <?php echo intval($team_result['volunteers_needed']); ?></p>
                <p style="margin:0 0 8px;"><strong>Advance:</strong> <?php echo esc_html(ucwords(str_replace('_', ' ', $team_result['advance_rule']))); ?></p>

                <?php if ( ! empty($team_result['message']) ) : ?>
                    <p style="margin:0;color:#a00;"><?php echo esc_html($team_result['message']); ?></p>
                <?php elseif ( ! empty($team_result['assignments']) ) : ?>
                    <ol style="margin:0 0 0 18px;">
                        <?php foreach ( $team_result['assignments'] as $assignment ) : ?>
                            <li><?php echo esc_html($assignment['volunteer_name']); ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
