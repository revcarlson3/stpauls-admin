<?php
/**
  * Admin Menu
  */
function spa_register_admin_menu() {
    $icon = plugin_dir_url(__FILE__) . '../assets/images/logo.png';

    add_menu_page(
        __('St. Paul\'s Admin', 'stpauls-admin'),
        __('St. Paul\'s Admin', 'stpauls-admin'),
        'edit_posts',
        'spa-dashboard',
        'spa_dashboard_page',
        $icon,
        25
    );

    add_submenu_page(
        'spa-dashboard',
        __('Dashboard', 'stpauls-admin'),
        __('Dashboard', 'stpauls-admin'),
        'manage_options',
        'spa-dashboard',
        'spa_dashboard_page'
    );

    add_submenu_page(
        'spa-dashboard',
        __('Events', 'stpauls-admin'),
        __('Events', 'stpauls-admin'),
        'manage_options',
        'spa-events',
        'spa_events_page'
    );

    add_submenu_page(
        'spa-dashboard',
        __('Services', 'stpauls-admin'),
        __('Services', 'stpauls-admin'),
        'edit_posts',
        'spa-services',
        'spa_services_page'
    );

    add_submenu_page(
        'spa-dashboard',
        __('Teams', 'stpauls-admin'),
        __('Teams', 'stpauls-admin'),
        'manage_options',
        'spa-teams',
        'spa_teams_page'
    );

    add_submenu_page(
        'spa-dashboard',
        __('Volunteers', 'stpauls-admin'),
        __('Volunteers', 'stpauls-admin'),
        'manage_options',
        'spa-volunteers',
        'spa_volunteers_page'
    );

    add_submenu_page(
        'spa-dashboard',
        __('Scheduling', 'stpauls-admin'),
        __('Scheduling', 'stpauls-admin'),
        'manage_options',
        'spa-scheduling',
        'spa_scheduling_page'
    );

    add_submenu_page(
        'spa-dashboard',
        __('Reports', 'stpauls-admin'),
        __('Reports', 'stpauls-admin'),
        'manage_options',
        'spa-reports',
        'spa_reports_page'
    );

    add_submenu_page(
        'spa-dashboard',
        __('Settings', 'stpauls-admin'),
        __('Settings', 'stpauls-admin'),
        'manage_options',
        'spa-settings',
        'spa_settings_page'
    );
}

add_action('admin_menu', 'spa_register_admin_menu');
?>