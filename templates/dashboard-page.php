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
                            <?php if ( ! empty($sunday_service->service_id) ) : ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=spa-services&service_id=' . intval($sunday_service->service_id) . '&event_id=' . intval($sunday_service->id))); ?>">
                                    <?php echo esc_html($sunday_service->name); ?>
                                </a>
                            <?php else : ?>
                                <?php echo esc_html($sunday_service->name); ?>
                            <?php endif; ?>
                        </h3>

                        <?php if ( ! empty($sunday_service->special_day) || ! empty($sunday_service->season) ) : ?>
                            <p class="spa-service-church-day">
                                <span>Day of the Church Year:</span>
                                <?php echo esc_html(spa_get_church_year_day(
                                    $sunday_service->event_date,
                                    $sunday_service->special_day,
                                    $sunday_service->season
                                )); ?>
                            </p>
                        <?php endif; ?>

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
                                        <th>Service</th>
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
                                                <?php if ( ! empty($upcoming_event->service_id) ) : ?>
                                                    <span class="spa-assignment-status spa-assignment-status-assigned">
                                                        <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                                                        Created
                                                    </span>
                                                <?php else : ?>
                                                    <span class="spa-assignment-status spa-assignment-status-unassigned">
                                                        <span class="dashicons dashicons-minus" aria-hidden="true"></span>
                                                        Not created
                                                    </span>
                                                <?php endif; ?>
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
                    <?php elseif ( $card_id === 'communications' ) : ?>
                        <?php if ( ! empty($communication_failures) ) : ?>
                            <table class="spa-upcoming-events-table spa-communications-table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Volunteer</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $communication_failures as $failure ) : ?>
                                        <tr>
                                            <td>
                                                <span class="spa-communication-type">
                                                    <?php echo esc_html(strtoupper($failure->channel)); ?>
                                                </span>
                                            </td>
                                            <td><?php echo esc_html(mysql2date('M j, Y g:i a', $failure->failed_at)); ?></td>
                                            <td><?php echo esc_html($failure->volunteer_name); ?></td>
                                            <td class="spa-communication-reason"><?php echo esc_html($failure->failure_reason); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p>No failed email or SMS deliveries recorded.</p>
                        <?php endif; ?>
                    <?php elseif ( $card_id === 'future' ) : ?>
                        <p style="color:#999;font-style:italic;">Reserved for future functionality.</p>
                    <?php elseif ( $card_id === 'recent-activity' ) : ?>
                        <?php if ( ! empty($recent_activity) ) : ?>
                            <table class="spa-upcoming-events-table spa-recent-activity-table">
                                <thead>
                                    <tr>
                                        <th>Date and time</th>
                                        <th>Event</th>
                                        <th>Email</th>
                                        <th>SMS</th>
                                        <th>Push</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $recent_activity as $activity ) : ?>
                                        <tr>
                                            <td><?php echo esc_html(mysql2date('M j, Y g:i a', $activity->activity_time)); ?></td>
                                            <td><?php echo esc_html($activity->event_name ?: 'Notification'); ?></td>
                                            <td><?php echo intval($activity->email_count); ?></td>
                                            <td><?php echo intval($activity->sms_count); ?></td>
                                            <td>Not sent</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else : ?>
                            <p>No recent notification activity recorded.</p>
                        <?php endif; ?>
                    <?php else : ?>
                        <p style="color:#999;font-style:italic;">Coming soon.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

</div>
