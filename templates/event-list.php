<h2>Events</h2>

<p>
    <button type="button" id="spa-add-event-button" class="button button-primary">Add Event</button>

</p>

<div class="spa-events-pagination">

    <?php if ($current_page > 1) : ?>

        <button
            type="button"
            class="button spa-page-button"
            data-page="<?php echo ($current_page - 1); ?>">

            Previous

        </button>

    <?php else : ?>

        <span></span>

    <?php endif; ?>

    <span class="spa-page-number">

        Page <?php echo intval($current_page); ?>
        of
        <?php echo intval($total_pages); ?>

    </span>

    <?php if ($current_page < $total_pages) : ?>

        <button
            type="button"
            class="button spa-page-button"
            data-page="<?php echo ($current_page + 1); ?>">

            Next

        </button>

    <?php else : ?>

        <span></span>

    <?php endif; ?>

</div>

<table class="spa-events-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Event</th>
        </tr>
    </thead>
    <tbody>

<?php foreach($events AS $event) : ?>

    <tr data-event-id="<?php echo intval($event->id); ?>">
        <td class="spa-event-date"><?php echo date('F j Y', strtotime($event->event_date)) ?></td>
        <td class="spa-event-name"><a href="#" class="spa-event-link" data-event-id="<?php echo intval($event->id) ?>"><?php echo esc_html(wp_unslash($event->name)) ?></a></td>
    </tr>

    <?php endforeach; ?>

    </tbody>
</table>