
<?php if ( ! empty($duplicate_assignment_alerts) ) : ?>
    <div class="notice notice-warning inline" style="margin:0 0 16px;">
        <p><strong>Volunteer assignment warning:</strong></p>
        <ul style="margin:8px 0 0 18px;list-style:disc;">
            <?php foreach ( $duplicate_assignment_alerts as $alert ) : ?>
                <li>
                    <strong><?php echo esc_html($alert['volunteer_name']); ?></strong>
                    is assigned to
                    <strong><?php echo esc_html($alert['team_count']); ?></strong>
                    teams for this event:
                    <?php echo esc_html($alert['team_names']); ?>.
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (empty($event_teams)) : ?>

    <p>No teams assigned to this event.</p>

<?php else : ?>

    <?php foreach ($event_teams as $team) :
        $assigned_count = 0;
        foreach($team->team_volunteers as $volunteer) {
            if(isset($assigned_lookup[$team->id][$volunteer->id])) {
                $assigned_count++;
            }
        }
        $team_is_full = $assigned_count >= $team->volunteers_needed;
        ?>

        <div class="spa-team-card" data-team-card="<?php echo intval($team->id); ?>">

            <h3>

                <?php echo esc_html($team->name); ?>

            </h3>

            <div
                class="spa-team-count <?php echo ($assigned_count >= $team->volunteers_needed)
                    ? 'spa-team-full'
                    : 'spa-team-short'; ?>"
                data-needed="<?php echo intval($team->volunteers_needed); ?>">

                Assigned:

                <span class="spa-assigned-count">

                    <?php echo intval($assigned_count); ?>

                </span>

                of

                <?php echo intval($team->volunteers_needed); ?>

                <span class="spa-team-complete">

                    <?php if ($assigned_count >= $team->volunteers_needed) : ?>
                        &#10003;
                    <?php endif; ?>

                </span>

            </div>

            <?php if (!empty($team->team_volunteers)) : ?>

                <ul class="spa-volunteer-list">

                    <?php foreach ($team->team_volunteers as $volunteer) : ?>

                        <li>

                            <label>
                                <?php $is_assigned = isset($assigned_lookup[$team->id][$volunteer->id]) ?>
                                <input
                                    type="checkbox"
                                    class="spa-volunteer-checkbox"
                                    data-event-id="<?php echo intval($event->id); ?>"
                                    data-team-id="<?php echo intval($team->id); ?>"
                                    data-volunteer-id="<?php echo intval($volunteer->id); ?>"
                                    <?php checked(isset(
                                        $assigned_lookup[$team->id][$volunteer->id])
                                    ); disabled($team_is_full && !$is_assigned); ?>>

                                <span class="spa-volunteer-name">

                                <?php echo esc_html(
                                    $volunteer->first_name . ' ' . $volunteer->last_name
                                ); ?>

                                </span>

                                <?php if ( $is_assigned ) : ?>
                                    <button
                                        type="button"
                                        class="button-link spa-override-volunteer"
                                        data-event-id="<?php echo intval($event->id); ?>"
                                        data-team-id="<?php echo intval($team->id); ?>"
                                        data-volunteer-id="<?php echo intval($volunteer->id); ?>">
                                        Override
                                    </button>
                                <?php endif; ?>

                            </label>

                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php else : ?>

                <p>No volunteers assigned to this team.</p>

            <?php endif; ?>

        </div>

    <?php endforeach; ?>

<?php endif; ?>