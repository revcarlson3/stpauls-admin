<p class="spa-event-rotation-actions" style="margin-top:0;">
    <button type="button" id="spa-preview-event-rotation-btn" class="button">Preview Rotation Assignments</button>
    <button
        type="button"
        id="spa-apply-event-rotation-btn"
        class="button"
        <?php disabled($rotation_applied); ?>
        <?php if ( $rotation_applied ) : ?>title="Undo the last apply before applying rotation assignments again."<?php endif; ?>>
        Apply Rotation Assignments
    </button>
    <?php if ( $can_undo_rotation ) : ?>
        <button type="button" id="spa-undo-event-rotation-btn" class="button">Undo Last Apply</button>
    <?php endif; ?>
</p>

<div id="spa-event-rotation-preview"></div>

<?php if ( ! empty($pending_swaps) ) : ?>
    <h3>Pending Volunteer Swaps</h3>
    <table class="widefat striped">
        <thead>
            <tr>
                <th>Team</th>
                <th>Scheduled Volunteer</th>
                <th>Replacement Volunteer</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $pending_swaps as $swap ) : ?>
            <tr>
                <td><?php echo esc_html($swap->team_name); ?></td>
                <td><?php echo esc_html($swap->scheduled_volunteer_name); ?></td>
                <td><?php echo esc_html($swap->replacement_volunteer_name); ?></td>
                <td>
                    <?php if ( $swap->is_saved_to_rotation_preview ) : ?>
                        <span class="description">Saved to rotation preview; apply the rotation to finalize.</span>
                    <?php elseif ( ! $swap->has_current_assignment && ! $swap->is_in_rotation_preview ) : ?>
                        <span class="description">Unavailable until the scheduled volunteer is assigned or appears in the rotation preview.</span>
                    <?php else : ?>
                    <button type="button" class="button button-small spa-apply-swap-reminder"
                        data-swap-id="<?php echo intval($swap->id); ?>"
                        data-event-id="<?php echo intval($event->id); ?>"><?php echo $swap->has_current_assignment ? 'Apply Swap' : 'Apply to Preview'; ?></button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h3>Current Assignments</h3>

<?php if ( empty($final_assignments) ) : ?>
    <p>No rotation assignments have been applied yet.</p>
<?php else : ?>
    <table class="widefat striped spa-current-assignments-table">
        <colgroup>
            <col class="spa-assignment-team-column">
            <col class="spa-assignment-volunteer-column">
            <col class="spa-assignment-override-column">
        </colgroup>
        <thead>
            <tr>
                <th scope="col">Team</th>
                <th scope="col">Assigned Volunteer</th>
                <th scope="col">Override</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $final_assignments as $assignment ) : ?>
            <tr class="spa-current-assignment">
                <th scope="row"><?php echo esc_html($assignment->team_name); ?></th>
                <td>
                    <?php echo esc_html($assignment->volunteer_name); ?>
                    <?php if ( ! $assignment->team_active || ! $assignment->volunteer_active ) : ?>
                        <span class="spa-assignment-label spa-assignment-inactive">Inactive</span>
                    <?php elseif ( ! empty($assignment->is_override) ) : ?>
                        <span class="spa-assignment-label spa-assignment-overridden">Override</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $assignment_candidates = array();
                    $assigned_team_volunteer_ids = isset($assigned_volunteer_ids_by_team[$assignment->team_id])
                        ? $assigned_volunteer_ids_by_team[$assignment->team_id]
                        : array();
                    if ( ! empty($override_candidates[$assignment->team_id]) ) {
                        foreach ( $override_candidates[$assignment->team_id] as $candidate ) {
                            $candidate_id = intval($candidate->id);
                            if (
                                $candidate_id === intval($assignment->volunteer_id)
                                || ! in_array($candidate_id, $assigned_team_volunteer_ids, true)
                            ) {
                                $assignment_candidates[] = $candidate;
                            }
                        }
                    }
                    $has_replacement = count($assignment_candidates) > 1
                        || (
                            count($assignment_candidates) === 1
                            && intval($assignment_candidates[0]->id) !== intval($assignment->volunteer_id)
                        );
                    ?>

                    <?php if ( $has_replacement ) : ?>
                        <div class="spa-assignment-override-controls">
                            <select class="spa-override-volunteer-select">
                                <?php foreach ( $assignment_candidates as $candidate ) : ?>
                                    <option
                                        value="<?php echo intval($candidate->id); ?>"
                                        <?php selected(intval($candidate->id), intval($assignment->volunteer_id)); ?>>
                                        <?php echo esc_html($candidate->first_name . ' ' . $candidate->last_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button
                                type="button"
                                class="button button-small spa-override-volunteer"
                                data-event-id="<?php echo intval($event->id); ?>"
                                data-team-id="<?php echo intval($assignment->team_id); ?>"
                                data-volunteer-id="<?php echo intval($assignment->volunteer_id); ?>">
                                Override
                            </button>
                        </div>
                    <?php else : ?>
                        <span class="spa-no-override">No alternate available</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
