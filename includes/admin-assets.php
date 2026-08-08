<?php
// Import Styles from the plugin stylesheet
function spa_admin_assets($hook) {
    if(!isset($_GET['page']) || strpos($_GET['page'], 'spa-') !== 0) {
        return;
    }
    wp_enqueue_script('spa-admin-scripts', SPA_PLUGIN_URL .'js/spa_admin.js', array('jquery'), time(), true);
    wp_enqueue_style('spa-admin-styles', SPA_PLUGIN_URL .'css/spa_style.css', array(), time());
    wp_enqueue_script('jquery-ui-sortable');
    wp_localize_script('spa-admin-scripts', 'spaAdmin', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'page' => isset($_GET['page'])
            ? sanitize_text_field($_GET['page'])
            : '',
        'nonce' => wp_create_nonce('spa_admin_nonce')
        ));
}
add_action('admin_enqueue_scripts', 'spa_admin_assets');

// Make the plugin icon work.
function spa_admin_icon_fix() {
    ?>
    <style>
        #adminmenu .toplevel_page_spa-dashboard .wp-menu-image img {
            width: 20px !important;
            height: 20px !important;
            padding-top: 7px !important;
            opacity: 1 !important;
        }
    </style>
    <?php
}
add_action('admin_head', 'spa_admin_icon_fix');
?>