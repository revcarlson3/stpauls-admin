<?php include SPA_TEMPLATE_DIR . 'header.php'; ?>

<div class="wrap">
    <h2>Scheduling</h2>
    <p>Set up service types and team rotation lists here so Sunday and midweek services can follow different assignment patterns.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;margin-bottom:24px;">
        <div class="postbox" style="padding:16px;">
            <h3 style="margin-top:0;">Add Service Type</h3>
            <form method="post">
                <?php wp_nonce_field('spa_scheduling_action', 'spa_scheduling_nonce'); ?>
                <input type="hidden" name="spa_scheduling_action" value="add_service_type">

                <p>
                    <label for="service_type_name"><strong>Name</strong></label><br>
                    <input type="text" id="service_type_name" name="service_type_name" class="regular-text" required>
                </p>

                <p>
                    <label for="service_type_description"><strong>Description</strong></label><br>
                    <textarea id="service_type_description" name="service_type_description" class="large-text" rows="4"></textarea>
                </p>

                <p>
                    <button type="submit" class="button button-primary">Save Service Type</button>
                </p>
            </form>
            <?php if ( ! empty($service_types) ) : ?>
                <table class="widefat striped" style="margin-top:16px;">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $service_types as $service_type ) : ?>
                            <tr>
                                <td><?php echo esc_html($service_type->name); ?></td>
                                <td><?php echo esc_html($service_type->description); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No service types created yet.</p>
            <?php endif; ?>
        </div>

        <div class="postbox" style="padding:16px;">
            <h3 style="margin-top:0;">Save Team Rotation</h3>
            <form method="post">
                <?php wp_nonce_field('spa_scheduling_action', 'spa_scheduling_nonce'); ?>
                <input type="hidden" name="spa_scheduling_action" value="save_rotation">

                <p>
                    <label for="rotation_service_type_id"><strong>Service Type</strong></label><br>
                    <select id="rotation_service_type_id" name="rotation_service_type_id" class="regular-text" required>
                        <option value="">Select</option>
                        <?php foreach ( $service_types as $service_type ) : ?>
                            <option value="<?php echo intval($service_type->id); ?>"><?php echo esc_html($service_type->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p>
                    <label for="rotation_team_id"><strong>Team</strong></label><br>
                    <select id="rotation_team_id" name="rotation_team_id" class="regular-text" required>
                        <option value="">Select</option>
                        <?php foreach ( $teams as $team ) : ?>
                            <option value="<?php echo intval($team->id); ?>"><?php echo esc_html($team->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <p>
                    <strong>Volunteers in rotation order</strong><br>
                    <span style="color:#666;font-size:12px;">Add volunteers to the list, then drag them into the exact rotation order.</span>
                </p>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:12px;">
                    <div>
                        <label for="spa-rotation-available-volunteers"><strong>Available volunteers</strong></label>
                        <select id="spa-rotation-available-volunteers" class="widefat" size="10"></select>
                        <p style="margin:8px 0 0;">
                            <button type="button" class="button" id="spa-rotation-add-volunteer">Add to Rotation</button>
                        </p>
                    </div>
                    <div>
                        <label><strong>Rotation order</strong></label>
                        <ul id="spa-rotation-selected-list" style="margin:0;border:1px solid #ccd0d4;background:#fff;min-height:238px;padding:8px 10px;overflow:auto;"></ul>
                    </div>
                </div>

                <div id="spa-rotation-hidden-inputs"></div>

                <p>
                    <label for="rotation_next_position"><strong>Next position</strong></label><br>
                    <input type="number" id="rotation_next_position" name="rotation_next_position" min="1" value="1" class="small-text">
                </p>

                <p>
                    <label for="rotation_advance_rule"><strong>Advance rule</strong></label><br>
                    <select id="rotation_advance_rule" name="rotation_advance_rule" class="regular-text">
                        <option value="every_event">Every event</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="manual">Manual</option>
                    </select>
                </p>

                <p>
                    <button type="submit" class="button button-primary">Save Rotation</button>
                </p>
            </form>
        </div>
    </div>

    <div style="margin-top:24px;">
        <div class="postbox" style="padding:16px;">
            <h3 style="margin-top:0;">Pending Volunteer Swap Reminders</h3>
            <p>Record a future date now, even before the event is scheduled. The reminder will appear on a matching event later.</p>
            <form method="post">
                <?php wp_nonce_field('spa_scheduling_action', 'spa_scheduling_nonce'); ?>
                <input type="hidden" name="spa_scheduling_action" value="add_swap_reminder">
                <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:end;">
                    <p style="margin:0;">
                        <label for="swap_team_id"><strong>Team</strong></label><br>
                        <select id="swap_team_id" name="swap_team_id" required>
                            <option value="">Select a team</option>
                            <?php foreach ( $teams as $team ) : ?>
                                <option value="<?php echo intval($team->id); ?>"><?php echo esc_html($team->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p style="margin:0;">
                        <label for="swap_scheduled_volunteer_id"><strong>Scheduled volunteer</strong></label><br>
                        <select id="swap_scheduled_volunteer_id" name="swap_scheduled_volunteer_id" required disabled>
                            <option value="">Select a team first</option>
                        </select>
                    </p>
                    <p style="margin:0;">
                        <label for="swap_replacement_volunteer_id"><strong>Replacement volunteer</strong></label><br>
                        <select id="swap_replacement_volunteer_id" name="swap_replacement_volunteer_id" required disabled>
                            <option value="">Select a team first</option>
                        </select>
                    </p>
                    <p style="margin:0;">
                        <label for="swap_date"><strong>Date</strong></label><br>
                        <input type="date" id="swap_date" name="swap_date" required>
                    </p>
                    <p style="margin:0 0 8px;">
                        <label>
                            <input type="checkbox" name="swap_permanent" value="1">
                            Permanent swap?
                        </label>
                    </p>
                    <p style="margin:0;"><button type="submit" class="button button-primary">Save Swap Reminder</button></p>
                </div>
            </form>
            <?php if ( ! empty($swap_reminders) ) : ?>
                <table class="widefat striped" style="margin-top:16px;">
                    <thead><tr><th>Date</th><th>Team</th><th>Scheduled Volunteer</th><th>Replacement Volunteer</th><th>Permanent</th></tr></thead>
                    <tbody>
                    <?php foreach ( $swap_reminders as $swap ) : ?>
                        <tr>
                            <td><?php echo esc_html(mysql2date(get_option('date_format'), $swap->swap_date)); ?></td>
                            <td><?php echo esc_html($swap->team_name); ?></td>
                            <td><?php echo esc_html($swap->scheduled_volunteer_name); ?></td>
                            <td><?php echo esc_html($swap->replacement_volunteer_name); ?></td>
                            <td><?php echo ! empty($swap->permanent) ? 'Yes' : 'No'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No pending swap reminders.</p>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-top:24px;">
        <details class="postbox" style="padding:16px;" open>
            <summary style="cursor:pointer;font-size:1.1em;font-weight:600;">Rotation View</summary>
            <?php if ( ! empty($rotations) ) : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Service Type</th>
                            <th>Team</th>
                            <th>Order</th>
                            <th>Advance</th>
                            <th>Volunteer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $rotations as $rotation ) : ?>
                            <tr>
                                <td><?php echo esc_html($rotation->service_type_name); ?></td>
                                <td><?php echo esc_html($rotation->team_name); ?></td>
                                <td><?php echo intval($rotation->rotation_order); ?></td>
                                <td><?php echo esc_html(ucwords(str_replace('_', ' ', $rotation->advance_rule))); ?></td>
                                <td><?php echo esc_html($rotation->volunteer_name); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No rotations created yet.</p>
            <?php endif; ?>
        </details>
    </div>

</div>

<script>
jQuery(function($) {
    var volunteersByTeam = <?php echo wp_json_encode($volunteers_by_team); ?>;
    var rotationMap = <?php echo wp_json_encode($rotation_map); ?>;

    function spaRenderRotationHiddenInputs() {
        var $inputs = $('#spa-rotation-hidden-inputs');
        $inputs.empty();

        $('#spa-rotation-selected-list .spa-rotation-item').each(function() {
            var volunteerId = $(this).data('volunteer-id');
            $('<input>', {
                type: 'hidden',
                name: 'rotation_volunteer_ids[]',
                value: volunteerId
            }).appendTo($inputs);
        });
    }

    function spaRenderRotationList(selectedIds, teamId) {
        var $list = $('#spa-rotation-selected-list');
        var teamVolunteers = volunteersByTeam[teamId] || [];
        var volunteerMap = {};

        $list.empty();

        $.each(teamVolunteers, function(_, volunteer) {
            volunteerMap[String(volunteer.id)] = volunteer;
        });

        $.each(selectedIds, function(index, volunteerId) {
            var volunteer = volunteerMap[String(volunteerId)];
            if (!volunteer) {
                return;
            }

            var $item = $('<li class="spa-rotation-item" style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:6px 0;border-bottom:1px solid #eee;"></li>');
            $item.attr('data-volunteer-id', volunteer.id);
            $item.append('<span><span class="spa-rotation-drag-handle" style="cursor:move;font-size:16px;margin-right:6px;" title="Drag to reorder" aria-label="Drag to reorder">&#9776;</span><strong>' + (index + 1) + '.</strong> ' + volunteer.last_name + ', ' + volunteer.first_name + ' (#' + volunteer.id + ')</span>');
            $item.append(
                '<span class="spa-rotation-controls">' +
                '<button type="button" class="button-link-delete spa-rotation-remove">Remove</button>' +
                '</span>'
            );
            $list.append($item);
        });

        spaRenderRotationHiddenInputs();
        $('#rotation_next_position').attr('max', Math.max(selectedIds.length, 1));
    }

    function spaPopulateAvailableVolunteers(teamId) {
        var $select = $('#spa-rotation-available-volunteers');
        var teamVolunteers = volunteersByTeam[teamId] || [];

        $select.empty();

        if (!teamId || !teamVolunteers.length) {
            $select.append('<option value="">No volunteers available for this team</option>');
            return;
        }

        $.each(teamVolunteers, function(_, volunteer) {
            $select.append(
                $('<option></option>')
                    .val(volunteer.id)
                    .text(volunteer.last_name + ', ' + volunteer.first_name + ' (#' + volunteer.id + ')')
            );
        });
    }

    function spaLoadRotationEditor() {
        var teamId = $('#rotation_team_id').val();
        var serviceTypeId = $('#rotation_service_type_id').val();
        var existing = [];
        var nextPosition = 1;

        spaPopulateAvailableVolunteers(teamId);

        if (serviceTypeId && teamId && rotationMap[serviceTypeId] && rotationMap[serviceTypeId][teamId]) {
            existing = rotationMap[serviceTypeId][teamId].volunteer_ids || [];
            nextPosition = rotationMap[serviceTypeId][teamId].next_position || 1;
            $('#rotation_advance_rule').val(rotationMap[serviceTypeId][teamId].advance_rule || 'every_event');
        } else {
            $('#rotation_advance_rule').val('every_event');
        }

        spaRenderRotationList(existing, teamId);
        $('#rotation_next_position').val(nextPosition);
    }

    function spaAddVolunteerToRotation() {
        var teamId = $('#rotation_team_id').val();
        var volunteerId = $('#spa-rotation-available-volunteers').val();

        if (!teamId || !volunteerId) {
            return;
        }

        var selectedIds = [];
        $('#spa-rotation-selected-list .spa-rotation-item').each(function() {
            selectedIds.push(String($(this).data('volunteer-id')));
        });

        selectedIds.push(String(volunteerId));
        spaRenderRotationList(selectedIds, teamId);
    }

    $(document).on('click', '#spa-rotation-add-volunteer', function() {
        spaAddVolunteerToRotation();
    });

    $(document).on('dblclick', '#spa-rotation-available-volunteers', function() {
        spaAddVolunteerToRotation();
    });

    $(document).on('click', '.spa-rotation-remove', function() {
        var teamId = $('#rotation_team_id').val();
        $(this).closest('.spa-rotation-item').remove();

        var selectedIds = [];
        $('#spa-rotation-selected-list .spa-rotation-item').each(function() {
            selectedIds.push(String($(this).data('volunteer-id')));
        });

        spaRenderRotationList(selectedIds, teamId);
    });

    $('#spa-rotation-selected-list').sortable({
        handle: '.spa-rotation-drag-handle',
        placeholder: 'spa-rotation-sortable-placeholder',
        update: function() {
            var selectedIds = [];
            $('#spa-rotation-selected-list .spa-rotation-item').each(function() {
                selectedIds.push(String($(this).data('volunteer-id')));
            });
            spaRenderRotationList(selectedIds, $('#rotation_team_id').val());
        }
    });

    $('#rotation_team_id, #rotation_service_type_id').on('change', spaLoadRotationEditor);
    spaLoadRotationEditor();
});
</script>
