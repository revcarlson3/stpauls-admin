<?php
/**
  * Plugin Name: St. Paul's Admin
  * Plugin URI: https://stpaulsmilaca.org
  * Description: A plugin written specifically for St. Paul's Lutheran Church to handle scheduling
  * Version: 0.1.28-beta18
  * Update URI: https://github.com/revcarlson3/stpauls-admin/
  * Author: Rev. Daniel Carlson
  * License: GPL2
  */
if(!defined('ABSPATH')) {
    exit;
}

define('SPA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPA_TEMPLATE_DIR', plugin_dir_path(__FILE__) . 'templates/');
define('SPA_VERSION', '0.1.28-beta18');
define('SPA_DB_VERSION', '15');

// Optional Composer autoloader for libraries (libphonenumber)
if ( file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php') ) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
}

require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/database.php';
require_once plugin_dir_path(__FILE__) . 'includes/data-access.php';
require_once plugin_dir_path(__FILE__) . 'includes/rotation-service.php';

// Notification delivery, webhooks, and update checks must remain available to
// cron, REST, AJAX, and normal WordPress requests.
require_once plugin_dir_path(__FILE__) . 'includes/email.php';
require_once plugin_dir_path(__FILE__) . 'includes/sms.php';
require_once plugin_dir_path(__FILE__) . 'includes/push.php';
require_once plugin_dir_path(__FILE__) . 'includes/delivery-status.php';
require_once plugin_dir_path(__FILE__) . 'includes/notification-service.php';
require_once plugin_dir_path(__FILE__) . 'includes/notifications.php';
require_once plugin_dir_path(__FILE__) . 'includes/public-calendar.php';
require_once plugin_dir_path(__FILE__) . 'includes/updater.php';

$spa_is_ajax = function_exists('wp_doing_ajax') && wp_doing_ajax();
$spa_ajax_action = $spa_is_ajax && isset($_REQUEST['action'])
    ? sanitize_key(wp_unslash($_REQUEST['action']))
    : '';
$spa_is_public_ajax = $spa_ajax_action === 'spa_calendar_event_details';

if ( is_admin() && ! $spa_is_public_ajax ) {
    require_once SPA_PLUGIN_DIR . 'includes/admin-menu.php';
    require_once SPA_PLUGIN_DIR . 'includes/admin-assets.php';

    $spa_admin_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $spa_admin_page_files = array(
        'spa-dashboard'  => array('dashboard.php'),
        'spa-events'     => array('events.php'),
        'spa-services'   => array('services.php'),
        'spa-teams'      => array('teams.php'),
        'spa-volunteers' => array('volunteers.php'),
        'spa-scheduling' => array('scheduling.php'),
        'spa-reports'    => array('settings.php'),
        'spa-settings'   => array('settings.php'),
    );

    // Requests without a page are admin-post or admin-AJAX requests; retain
    // the broad load there so registered handlers remain available.
    $spa_admin_files = isset($spa_admin_page_files[$spa_admin_page])
        ? $spa_admin_page_files[$spa_admin_page]
        : array(
            'dashboard.php',
            'teams.php',
            'volunteers.php',
            'events.php',
            'services.php',
            'scheduling.php',
            'import.php',
            'templates.php',
            'settings.php',
        );

    foreach ( $spa_admin_files as $admin_file ) {
        require_once SPA_PLUGIN_DIR . 'includes/' . $admin_file;
    }
}

register_activation_hook(__FILE__, 'spa_activate_plugin');

// Run dbDelta on version upgrades without requiring deactivation/reactivation
add_action('plugins_loaded', function() {
    if ( get_option('spa_db_version') !== SPA_DB_VERSION ) {
        spa_activate_plugin();
        update_option('spa_db_version', SPA_DB_VERSION);
    }
});
?>