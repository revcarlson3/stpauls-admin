<?php


function spa_settings_page() {

    $active_tab = isset($_GET['tab'])
        ? sanitize_text_field($_GET['tab'])
        : 'general';
    $page_title = "Settings";
    include SPA_TEMPLATE_DIR . 'settings-page.php';

}