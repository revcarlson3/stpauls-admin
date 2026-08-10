                                                                          <h2>Event Details</h2>

<div id="spa-event-details-container">

    Select an event.

</div>

<script type="text/template" id="spa-event-rotation-empty-template">
    <p>Select an event.</p>
</script>

<div id="spa-event-action-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:100000;">
    <div style="max-width:420px;margin:10% auto;background:#fff;border-radius:6px;padding:20px;">
        <h3 id="spa-event-action-title" style="margin-top:0;">Choose an action</h3>
        <p id="spa-event-action-message"></p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button type="button" class="button button-primary" id="spa-event-action-single">Yes</button>
            <button type="button" class="button button-primary" id="spa-event-action-delete" style="display:none;">Delete</button>
            <button type="button" class="button button-primary" id="spa-event-action-parent" style="display:none;">Delete Just This Event</button>
            <button type="button" class="button button-primary" id="spa-event-action-series" style="display:none;">Delete Entire Series</button>
            <button type="button" class="button" id="spa-event-action-cancel">Cancel</button>
        </div>
    </div>
</div>