<div id="spa-event-modal" class="spa-modal" style="display:none;">
    <div class="spa-modal-overlay"></div>
    <div class="spa-modal-content">
        <div class="spa-modal-header">
            <h2 id="spa-event-modal-title">Add Event</h2>
            <button type="button" class="spa-modal-close" id="spa-event-modal-close">×</button>
        </div>
        <div class="spa-modal-body">
            <div class="spa-event-form">
                <input type="hidden" id="spa-event-modal-id" value="">

                <div class="spa-form-field">
                    <label for="spa-event-modal-name">Event Name</label>
                    <input type="text" id="spa-event-modal-name" class="spa-event-field" value="">
                </div>

                <div class="spa-form-field">
                    <label for="spa-event-modal-season">Season</label>
                    <select id="spa-event-modal-season" class="spa-event-field">
                        <option value="">Select</option>
                        <?php foreach ( spa_get_church_year_seasons() as $season ) : ?>
                            <option value="<?php echo esc_attr($season); ?>"><?php echo esc_html($season); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="spa-form-field">
                    <label for="spa-event-modal-service-type">Service Type</label>
                    <select id="spa-event-modal-service-type" class="spa-event-field">
                        <option value="">Select</option>
                        <?php
                        global $wpdb;
                        $modal_service_types = $wpdb->get_results(
                            "SELECT id, name
                             FROM {$wpdb->prefix}spa_service_types
                             WHERE active = 1
                             ORDER BY name"
                        );
                        foreach ( $modal_service_types as $service_type ) :
                        ?>
                            <option value="<?php echo intval($service_type->id); ?>">
                                <?php echo esc_html($service_type->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="spa-form-field">
                    <label for="spa-event-modal-location">Location</label>
                    <input type="text" id="spa-event-modal-location" class="spa-event-field" value="">
                </div>

                <div class="spa-form-field">
                    <label for="spa-event-modal-service-builder-url">Lutheran Service Builder Day URL</label>
                    <input
                        type="url"
                        id="spa-event-modal-service-builder-url"
                        class="spa-event-field"
                        value=""
                        placeholder="https://app.lutheranservicebuilder.com/holiday/...">
                    <p class="description">
                        Optional. Recurring copies are left blank because each date has a different public day link.
                    </p>
                </div>

                <div class="spa-form-field">
                    <label for="spa-event-modal-description">Description</label>
                    <textarea id="spa-event-modal-description" class="spa-event-field" rows="5"></textarea>
                </div>

                <div class="spa-event-datetime-row">
                    <div class="spa-form-field">
                        <label for="spa-event-modal-date">Date</label>
                        <input type="date" id="spa-event-modal-date" class="spa-event-field" value="">
                    </div>

                    <div class="spa-form-field">
                        <label for="spa-event-modal-start-time">Start Time</label>
                        <input type="time" id="spa-event-modal-start-time" class="spa-event-field" value="">
                    </div>

                    <div class="spa-form-field">
                        <label for="spa-event-modal-end-time">End Time</label>
                        <input type="time" id="spa-event-modal-end-time" class="spa-event-field" value="">
                    </div>
                </div>

                <hr class="hr" />

                <div class="spa-checkbox-field" style="margin-top:10px;">
                    <label>
                        <input type="checkbox" id="spa-event-modal-notify-volunteers" class="spa-event-field">
                        Notify Volunteers
                    </label>
                </div>

                <div class="spa-checkbox-field">
                    <label>
                        <input type="checkbox" id="spa-event-modal-recurring" class="spa-event-field">
                        Recurring Event
                    </label>
                </div>

                <div class="spa-event-recurrence-row">
                    <div class="spa-form-field">
                        <label for="spa-event-modal-recurrence-type">Recurrence Type</label>
                        <select id="spa-event-modal-recurrence-type" class="spa-event-field">
                            <option value="">Select</option>
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>

                    <div class="spa-form-field">
                        <label for="spa-event-modal-recurrence-end">Repeat Until</label>
                        <input type="date" id="spa-event-modal-recurrence-end" class="spa-event-field" value="">
                    </div>
                </div>

                <div id="spa-event-modal-status"></div>
            </div>
        </div>
        <div class="spa-modal-footer">
            <button type="button" class="button" id="spa-event-modal-cancel">Cancel</button>
            <button type="button" class="button button-primary" id="spa-event-modal-save">Save Event</button>
        </div>
    </div>
</div>

<style>
.spa-form-field {
    margin-bottom: 15px;
}

.spa-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.spa-form-field input,
.spa-form-field textarea,
.spa-form-field select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-family: inherit;
}

.spa-form-field textarea {
    resize: vertical;
}

.spa-event-datetime-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
}

.spa-event-recurrence-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.spa-event-recurrence-row select,
.spa-event-recurrence-row input[type="date"] {
    width: 100%;
    min-height: 34px;
    box-sizing: border-box;
}

.spa-event-recurrence-row select {
    padding: 6px 10px;
    line-height: 1.4;
}

.spa-checkbox-field {
    margin-bottom: 15px;
}

.spa-checkbox-field input {
    width: auto;
    margin-right: 8px;
}

@media (max-width: 768px) {
    .spa-event-datetime-row,
    .spa-event-recurrence-row {
        grid-template-columns: 1fr;
    }
}
</style>
