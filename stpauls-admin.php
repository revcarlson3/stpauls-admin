<?php
/**
  * Plugin Name: St. Paul's Admin
  * Plugin URI: https://stpaulsmilaca.org
  * Description: A plugin written specifically for St. Paul's Lutheran Church to handle scheduling
  * Version: 0.1.25
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
define('SPA_VERSION', '0.1.25');
define('SPA_DB_VERSION', '11');

// Optional Composer autoloader for libraries (libphonenumber)
if ( file_exists(plugin_dir_path(__FILE__) . 'vendor/autoload.php') ) {
    require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
}

require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';
require_once plugin_dir_path(__FILE__) . 'includes/database.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-assets.php';

require_once plugin_dir_path(__FILE__) . 'includes/dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/teams.php';
require_once plugin_dir_path(__FILE__) . 'includes/volunteers.php';
require_once plugin_dir_path(__FILE__) . 'includes/events.php';
require_once plugin_dir_path(__FILE__) . 'includes/services.php';
require_once plugin_dir_path(__FILE__) . 'includes/scheduling.php';
require_once plugin_dir_path(__FILE__) . 'includes/email.php';
require_once plugin_dir_path(__FILE__) . 'includes/sms.php';
require_once plugin_dir_path(__FILE__) . 'includes/push.php';
require_once plugin_dir_path(__FILE__) . 'includes/delivery-status.php';
require_once plugin_dir_path(__FILE__) . 'includes/import.php';
require_once plugin_dir_path(__FILE__) . 'includes/templates.php';
require_once plugin_dir_path(__FILE__) . 'includes/notifications.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/public-calendar.php';
require_once plugin_dir_path(__FILE__) . 'includes/updater.php';

register_activation_hook(__FILE__, 'spa_activate_plugin');

// Run dbDelta on version upgrades without requiring deactivation/reactivation
add_action('plugins_loaded', function() {
    if ( get_option('spa_db_version') !== SPA_DB_VERSION ) {
        spa_activate_plugin();
        update_option('spa_db_version', SPA_DB_VERSION);
    }
});
?>