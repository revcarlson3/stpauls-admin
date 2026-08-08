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


                </div>

                <div class="spa-sunday-column">

                    <h4>Additional Information</h4>

                </div>

            </div>

        </div>

    </div>

    <div id="spa-dashboard-widgets" class="spa-dashboard-widgets">

        <?php
        foreach($dashboard_order AS $card_id) {
            ?>

            <div
                class="spa-dashboard-card"
                data-card="<?php echo esc_attr($card_id); ?>">

                <div class="spa-card-header">
                    <?php echo esc_html($card['title']); ?>
                </div>

                <div class="spa-card-body">
                    Coming Soon
                </div>

            </div>

            <?php
        }
        ?>

    </div>

</div>


