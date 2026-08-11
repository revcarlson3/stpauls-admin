<?php
include SPA_TEMPLATE_DIR .'header.php';
$initial_event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
?>

<div class="spa-events-layout" data-initial-event-id="<?php echo intval($initial_event_id); ?>">

    <div class="spa-events-column spa-events-list" id="spa-events-list-container">

        <?php include SPA_TEMPLATE_DIR . 'event-list.php'; ?>

    </div>

    <div class="spa-events-column spa-event-details">

        <?php include SPA_TEMPLATE_DIR . 'event-details.php'; ?>

    </div>

    <div class="spa-events-column spa-event-rotation">

        <?php include SPA_TEMPLATE_DIR . 'event-rotation.php'; ?>

    </div>

    <div class="spa-events-column spa-event-volunteers">

        <?php include SPA_TEMPLATE_DIR . 'event-volunteers.php'; ?>

    </div>

</div>

<?php include SPA_TEMPLATE_DIR . 'event-modal.php'; ?>