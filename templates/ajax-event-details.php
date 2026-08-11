<?php
$event_date = (!empty($event->event_date) && $event->event_date !== '0000-00-00') ? $event->event_date : '';
$start_time = (!empty($event->start_time) && $event->start_time !== '00:00:00') ? substr($event->start_time, 0, 5) : '';
$end_time = (!empty($event->end_time) && $event->end_time !== '00:00:00') ? substr($event->end_time, 0, 5) : '';
$recurrence_end_date = (!empty($event->recurrence_end_date) && $event->recurrence_end_date !== '0000-00-00') ? $event->recurrence_end_date : '';
?>

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
            value="<?php echo esc_attr(wp_unslash($event->name)); ?>">

    </div>

    <div class="spa-form-field">

        <label for="spa-event-service-type">
            Service Type
        </label>

        <select
            id="spa-event-service-type"
            class="spa-event-field spa-event-service-type-select">
            <option value="">Select</option>
            <?php foreach ( $service_types as $service_type ) : ?>
                <option value="<?php echo intval($service_type->id); ?>" <?php selected(intval($event->service_type_id), intval($service_type->id)); ?>>
                    <?php echo esc_html($service_type->name); ?>
                </option>
            <?php endforeach; ?>
        </select>

    </div>

    <div class="spa-form-field">
        <label for="spa-event-season">Season</label>
        <select id="spa-event-season" class="spa-event-field">
            <option value="">Select</option>
            <?php foreach ( spa_get_church_year_seasons() as $season ) : ?>
                <option value="<?php echo esc_attr($season); ?>" <?php selected($event->season ?? '', $season); ?>>
                    <?php echo esc_html($season); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="spa-form-field">
        <label for="spa-event-special-day">Special Day</label>
        <select id="spa-event-special-day" class="spa-event-field">
            <option value="">None</option>
            <?php foreach ( spa_get_church_year_special_days() as $special_day ) : ?>
                <option value="<?php echo esc_attr($special_day); ?>" <?php selected($event->special_day ?? '', $special_day); ?>>
                    <?php echo esc_html($special_day); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="spa-form-field">

        <label for="spa-event-location">
            Location
        </label>

        <input
            type="text"
            id="spa-event-location"
            class="spa-event-field"
            value="<?php echo esc_attr(wp_unslash($event->location)); ?>">

    </div>

    <div class="spa-form-field">

        <label for="spa-event-service-builder-url">
            Lutheran Service Builder Day URL
        </label>

        <input
            type="url"
            id="spa-event-service-builder-url"
            class="spa-event-field"
            value="<?php echo esc_url($event->service_builder_url ?? ''); ?>"
            placeholder="https://app.lutheranservicebuilder.com/holiday/...">

        <p class="description">
            Optional. Paste the public day link used by the Readers <code>{readings}</code> notification tag.
            Each recurring occurrence needs its own date-specific link.
        </p>

    </div>

    <div class="spa-form-field">

        <label for="spa-event-description">
            Description
        </label>

        <textarea
            id="spa-event-description"
            class="spa-event-field"
            rows="5"><?php echo esc_textarea(wp_unslash($event->description)); ?></textarea>

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
                value="<?php echo esc_attr($event_date); ?>">

        </div>

        <div class="spa-form-field">

            <label for="spa-event-start-time">
                Start Time
            </label>

            <input
                type="time"
                id="spa-event-start-time"
                class="spa-event-field"
                value="<?php echo esc_attr($start_time); ?>">

        </div>

        <div class="spa-form-field">

            <label for="spa-event-end-time">
                End Time
            </label>

            <input
                type="time"
                id="spa-event-end-time"
                class="spa-event-field"
                value="<?php echo esc_attr($end_time); ?>">

        </div>

    </div>

    <hr class="hr" />

    <div class="spa-checkbox-field" style="margin-top:10px;">
        <label>
            <input
                type="checkbox"
                id="spa-event-notify-volunteers"
                class="spa-event-field"
                <?php checked(!empty($event->notify_volunteers), 1); ?>>
            Notify Volunteers
        </label>
    </div>

    <input type="hidden" id="spa-event-series-parent" value="<?php echo !empty($is_series_parent) ? '1' : '0'; ?>">

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
                value="<?php echo esc_attr($recurrence_end_date); ?>">

        </div>

    </div>

    <div id="spa-save-status"></div>
    <p style="margin-top:12px;">
        <button type="button" id="spa-save-event-details-btn" class="button button-primary">Save Event</button>
        <button type="button" id="spa-delete-event-btn" class="button">Delete Event</button>
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