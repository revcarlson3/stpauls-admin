<?php include SPA_TEMPLATE_DIR .'header.php'; ?>

<div class="spa-events-layout">

    <div class="spa-events-column spa-events-list" id="spa-events-list-container">

        <?php include SPA_TEMPLATE_DIR . 'event-list.php'; ?>

    </div>

    <div class="spa-events-column spa-event-details">

        <?php include SPA_TEMPLATE_DIR . 'event-details.php'; ?>

    </div>

    <div class="spa-events-column spa-event-volunteers">

        <?php include SPA_TEMPLATE_DIR . 'event-volunteers.php'; ?>

    </div>

</div>

<?php include SPA_TEMPLATE_DIR . 'event-modal.php'; ?>