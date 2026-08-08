
<?php if (empty($event_teams)) : ?>

    <p>No teams assigned to this event.</p>

<?php else : ?>

    <?php foreach ($event_teams as $team) :
        $assigned_count = 0;
        foreach($team->volunteers as $volunteer) {
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

            <?php if (!empty($team->volunteers)) :
                $assigned_team_volunteers = array();
                $unassigned_team_volunteers = array();
                foreach($team->volunteers AS $volunteer) {
                    if(in_array(
                        $volunteer->id,
                        $assigned_volunteers)) {
                            $assigned_team_volunteers[] = $volunteer;
                        } else {
                            $unassigned_team_volunteers[] = $volunteer;
                        }
                }
                $display_volunteers = array_merge(
                    $assigned_team_volunteers,
                    $unassigned_team_volunteers
                );
                ?>

                <ul class="spa-volunteer-list">

                    <?php foreach ($display_volunteers as $volunteer) : ?>

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