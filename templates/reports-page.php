<?php include SPA_TEMPLATE_DIR . 'header.php'; ?>

<div class="wrap spa-reports-page">
    <h1>Reports</h1>
    <p>Open a report in a modal, then export it to Excel, CSV, or PDF, or print it.</p>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>Report</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( spa_get_report_definitions() as $report_key => $report ) : ?>
                <tr>
                    <td><?php echo esc_html($report['label']); ?></td>
                    <td><button type="button" class="button button-primary spa-open-report" data-report-key="<?php echo esc_attr($report_key); ?>">Open Report</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div id="spa-report-modal" class="spa-modal" style="display:none;">
        <div class="spa-modal-overlay"></div>
        <div class="spa-modal-content" style="max-width:1000px;">
            <div class="spa-modal-header">
                <h2>Report</h2>
                <button type="button" class="spa-modal-close" id="spa-report-modal-close">×</button>
            </div>
            <div class="spa-modal-body" id="spa-report-modal-body"></div>
        </div>
    </div>
</div>
