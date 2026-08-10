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
.spa-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.spa-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
}

.spa-modal-content {
    position: relative;
    background: white;
    border-radius: 5px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.spa-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
}

.spa-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.spa-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.spa-modal-close:hover {
    color: #000;
}

.spa-modal-body {
    padding: 20px;
    flex: 1;
    overflow-y: auto;
}

.spa-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 20px;
    border-top: 1px solid #ddd;
    background: #f9f9f9;
}

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
