<?php include SPA_TEMPLATE_DIR .'header.php'; ?>

<div class="spa-dashboard">

    <div
        class="spa-dashboard-card spa-dashboard-feature"
        data-card="sunday-service">

        <div class="spa-card-header">
            This Sunday's Service
        </div>

        <div class="spa-card-body">

            <div class="spa-sunday-grid">

                <div class="spa-sunday-column">

                    <h4 class="spa-service-information">Service Information</h4>

                    <?php if (!empty($sunday_service)) : ?>

                        <h3 class="spa-service-name">
                            <?php echo esc_html($sunday_service->name); ?>
                        </h3>

                        <p class="spa-service-date">
                            <?php echo date(
                                'F j, Y',
                                strtotime($sunday_service->event_date)
                            ); ?>
                        </p>



                    <?php else : ?>

                        <p>No upcoming Sunday service found.</p>

                    <?php endif; ?>

                </div>

                <div class="spa-sunday-column">

                    <h4>Team Assignments</h4>

                    <?php if (!empty($sunday_teams)) : ?>
                        <div class="spa-volunteer-tabs">

                            <div class="spa-tab-buttons">

                                <?php foreach ($sunday_teams as $index => $team) : ?>

                                <?php

                                if ($team['assigned'] == 0) {

                                    $status_class = 'spa-tab-empty';

                                } elseif ($team['assigned'] < $team['needed']) {

                                    $status_class = 'spa-tab-partial';

                                } else {

                                    $status_class = 'spa-tab-full';

                                }

                                ?>

                                <button
                                    class="spa-tab-button <?php echo $status_class; ?> <?php echo ($index === 0) ? 'active' : ''; ?>"
                                    data-tab="team-<?php echo $index; ?>"><span class="spa-tab-dot"></span>
                                    <?php echo esc_html($team['name']) ?>

                                    </button>

                                <?php endforeach; ?>

                            </div>

                            <?php foreach ($sunday_teams as $index => $team) : ?>

                                <div
                                    class="spa-tab-panel <?php echo ($index === 0) ? 'active' : ''; ?>"
                                    id="team-<?php echo $index; ?>">

                                    <p class="spa-tab-status">

                                        Assigned:
                                        <?php echo intval($team['assigned']); ?>
                                        /
                                        <?php echo intval($team['needed']); ?>

                                    </p>

                                    <?php if (!empty($team['volunteers'])) : ?>

                                        <ul class="spa-tab-volunteers">

                                            <?php foreach ($team['volunteers'] as $volunteer) : ?>

                                                <li>

                                                    <?php echo esc_html(
                                                        $volunteer->first_name . ' ' . $volunteer->last_name
                                                    ); ?>

                                                </li>

                                            <?php endforeach; ?>

                                        </ul>

                                    <?php else : ?>

                                        <div class="spa-unassigned">

                                            Volunteer Needed

                                        </div>

                                    <?php endif; ?>

                                </div>

                            <?php endforeach; ?>

                        </div>
                    <?php else : ?>
                        <p>No team assignments available.</p>
                    <?php endif; ?>

                </div>

                <div class="spa-sunday-column">

                    <h4>Assignment Conflicts</h4>

                    <?php if ( ! empty($duplicate_assignment_alerts) ) : ?>
                        <ul class="spa-dashboard-alert-list">
                            <?php foreach ( $duplicate_assignment_alerts as $alert ) : ?>
                                <li>
                                    <strong><?php echo esc_html($alert['volunteer_name']); ?></strong>
                                    is assigned to
                                    <strong><?php echo esc_html($alert['team_count']); ?></strong>
                                    teams for
                                    <strong><?php echo esc_html($alert['event_name']); ?></strong>:
                                    <?php echo esc_html($alert['team_names']); ?>.
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p>No duplicate volunteer team assignments detected for this service.</p>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

    <div id="spa-dashboard-widgets" class="spa-dashboard-widgets">

        <?php foreach ( $dashboard_order as $card_id ) :
            if ( ! isset($dashboard_cards[$card_id]) ) continue;
            $card = $dashboard_cards[$card_id];
        ?>
            <div class="spa-dashboard-card" data-card="<?php echo esc_attr($card_id); ?>">
                <div class="spa-card-header">
                    <span class="spa-card-drag-handle dashicons dashicons-move" title="Drag to reorder"></span>
                    <?php echo esc_html($card['title']); ?>
                </div>
                <div class="spa-card-body">
                    <?php if ( $card_id === 'upcoming-events' ) : ?>
                        <?php if ( ! empty($upcoming_events) ) : ?>
                            <table class="spa-upcoming-events-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Event</th>
                                        <th>Volunteers</th>
                                        <th><span class="screen-reader-text">Conflicts</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $upcoming_events as $upcoming_event ) : ?>
                                        <tr>
                                            <td>
                                                <?php echo esc_html(mysql2date('M j, Y', $upcoming_event->event_date)); ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo esc_url(add_query_arg(array('page' => 'spa-events', 'event_id' => intval($upcoming_event->id)), admin_url('admin.php'))); ?>">
                                                    <?php echo esc_html(wp_unslash($upcoming_event->name)); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if ( intval($upcoming_event->assigned_count) > 0 ) : ?>
                                                    <span class="spa-assignment-status spa-assignment-status-assigned">
                                                        <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                                                        Assigned
                                                    </span>
                                                <?php else : ?>
                                                    <span class="spa-assignment-status spa-assignment-status-unassigned">
                                                        <span class="dashicons dashicons-minus" aria-hidden="true"></span>
                                                        Not assigned
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="spa-upcoming-event-conflict">
                                                <?php if ( ! empty($upcoming_event->conflict_names) ) : ?>
                                                    <span
                                                        class="dashicons dashicons-warning spa-overlap-warning"
                                                        title="<?php echo esc_attr('Time overlaps with: ' . $upcoming_event->conflict_names); ?>"
                                                        aria-label="<?php echo esc_attr('Warning: time overlaps with ' . $upcoming_event->conflict_names); ?>"
                                                        role="img"></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p>No upcoming events found.</p>
                        <?php endif; ?>
                    <?php elseif ( $card_id === 'future' ) : ?>
                        <p style="color:#999;font-style:italic;">Reserved for future functionality.</p>
                    <?php else : ?>
                        <p style="color:#999;font-style:italic;">Coming soon.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

</div>
