
<div class="spa-event-form">

    <input
        type="hidden"
        id="spa-event-id"
        value="<?php echo intval($event->id); ?>">

    <div class="spa-form-field">

        <label for="spa-event-name">
            Event Name
        </label>

        <input
            type="text"
            id="spa-event-name"
            class="spa-event-field"
            value="<?php echo esc_attr($event->name); ?>">

    </div>

    <div class="spa-form-field">

        <label for="spa-event-location">
            Location
        </label>

        <input
            type="text"
            id="spa-event-location"
            class="spa-event-field"
            value="<?php echo esc_attr($event->location); ?>">

    </div>

    <div class="spa-form-field">

        <label for="spa-event-description">
            Description
        </label>

        <textarea
            id="spa-event-description"
            class="spa-event-field"
            rows="5"><?php echo esc_textarea($event->description); ?></textarea>

    </div>

    <div class="spa-event-datetime-row">

        <div class="spa-form-field">

            <label for="spa-event-date">
                Date
            </label>

            <input
                type="date"
                id="spa-event-date"
                class="spa-event-field"
                value="<?php echo esc_attr($event->event_date); ?>">

        </div>

        <div class="spa-form-field">

            <label for="spa-event-start-time">
                Start Time
            </label>

            <input
                type="time"
                id="spa-event-start-time"
                class="spa-event-field"
                value="<?php echo esc_attr($event->start_time); ?>">

        </div>

        <div class="spa-form-field">

            <label for="spa-event-end-time">
                End Time
            </label>

            <input
                type="time"
                id="spa-event-end-time"
                class="spa-event-field"
                value="<?php echo esc_attr($event->end_time); ?>">

        </div>

    </div>

    <hr class="hr" />

    <div class="spa-checkbox-field">

        <label>

            <input
                type="checkbox"
                id="spa-event-recurring"
                class="spa-event-field"
                <?php checked($event->is_recurring, 1); ?>>

            Recurring Event

        </label>

    </div>

    <div class="spa-event-recurrence-row">

        <div class="spa-form-field">

            <label for="spa-event-recurrence-type">
                Recurrence Type
            </label>

            <select
                id="spa-event-recurrence-type"
                class="spa-event-field">
                <option value="">Select</option>
                <option value="daily" <?php selected($event->recurrence_type, 'daily'); ?>>Daily</option>
                <option value="weekly" <?php selected($event->recurrence_type, 'weekly'); ?>>Weekly</option>
                <option value="monthly" <?php selected($event->recurrence_type, 'monthly'); ?>>Monthly</option>
            </select>

        </div>

        <div class="spa-form-field">

            <label for="spa-event-recurrence-end">
                Repeat Until
            </label>

            <input
                type="date"
                id="spa-event-recurrence-end"
                class="spa-event-field"
                value="<?php echo esc_attr($event->recurrence_end_date); ?>">

        </div>

    </div>

    <div id="spa-save-status"></div>
    <p style="margin-top:12px;">
        <button type="button" id="spa-save-event-details-btn" class="button button-primary">Save Event</button>
    </p>

    <hr class="hr" />

    <h3 style="margin-top:0;">Assigned Teams</h3>
    <p style="margin:0 0 8px;font-size:0.85em;color:#666;">Check teams for this event and enter the number of volunteers needed for each.</p>
    <div id="spa-event-teams-list">
        <?php if ( ! empty($all_teams) ) : ?>
            <?php foreach ( $all_teams as $team ) :
                $checked = in_array(intval($team->id), $assigned_team_ids);
                $needed = isset($team_volunteers_needed[$team->id]) ? $team_volunteers_needed[$team->id] : 1;
            ?>
            <div class="spa-event-team-row" style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                <label style="display:flex;align-items:center;gap:6px;flex:1;font-weight:normal;">
                    <input type="checkbox"
                           class="spa-event-team-check"
                           data-team-id="<?php echo intval($team->id); ?>"
                           <?php checked($checked); ?>>
                    <?php echo esc_html($team->name); ?>
                </label>
                <label style="display:flex;align-items:center;gap:4px;font-size:0.85em;color:#555;">
                    Volunteers needed:
                    <input type="number"
                           class="spa-event-team-needed"
                           data-team-id="<?php echo intval($team->id); ?>"
                           value="<?php echo intval($needed); ?>"
                           min="1"
                           style="width:55px;padding:2px 4px;<?php echo $checked ? '' : 'opacity:0.4;'; ?>">
                </label>
            </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p><em>No teams have been created yet. <a href="<?php echo esc_url(admin_url('admin.php?page=spa-teams')); ?>">Add a team</a>.</em></p>
        <?php endif; ?>
    </div>

</div>