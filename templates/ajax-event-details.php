
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

                <option
                    value="daily"
                    <?php selected($event->recurrence_type, 'daily'); ?>>
                    Daily
                </option>

                <option
                    value="weekly"
                    <?php selected($event->recurrence_type, 'weekly'); ?>>
                    Weekly
                </option>

                <option
                    value="monthly"
                    <?php selected($event->recurrence_type, 'monthly'); ?>>
                    Monthly
                </option>

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

</div>