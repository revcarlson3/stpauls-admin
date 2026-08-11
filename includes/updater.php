<?php

define('SPA_UPDATE_REPOSITORY', 'revcarlson3/stpauls-admin');

function spa_get_github_release() {
    $cached_release = get_transient('spa_github_release');
    if ( false !== $cached_release ) {
        return $cached_release;
    }

    $response = wp_remote_get(
        'https://api.github.com/repos/' . SPA_UPDATE_REPOSITORY . '/releases/latest',
        array(
            'headers' => array(
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => 'St-Pauls-Admin/' . SPA_VERSION,
            ),
            'timeout' => 10,
        )
    );

    if ( is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response) ) {
        set_transient('spa_github_release', array(), HOUR_IN_SECONDS);
        return array();
    }

    $release = json_decode(wp_remote_retrieve_body($response), true);
    if ( ! is_array($release) || empty($release['tag_name']) || empty($release['assets']) || ! is_array($release['assets']) ) {
        set_transient('spa_github_release', array(), HOUR_IN_SECONDS);
        return array();
    }

    foreach ( $release['assets'] as $asset ) {
        if ( isset($asset['name'], $asset['browser_download_url']) && 'stpauls-admin.zip' === $asset['name'] ) {
            $release['package'] = esc_url_raw($asset['browser_download_url']);
            break;
        }
    }

    if ( empty($release['package']) ) {
        set_transient('spa_github_release', array(), HOUR_IN_SECONDS);
        return array();
    }

    set_transient('spa_github_release', $release, 12 * HOUR_IN_SECONDS);
    return $release;
}

function spa_github_update_plugins($transient) {
    if ( ! is_object($transient) ) {
        $transient = new stdClass();
    }

    if ( ! isset($transient->response) || ! is_array($transient->response) ) {
        $transient->response = array();
    }

    $plugin_file = plugin_basename(SPA_PLUGIN_DIR . 'stpauls-admin.php');
    $release = spa_get_github_release();
    $version = isset($release['tag_name']) ? ltrim((string) $release['tag_name'], 'vV') : '';

    if ( empty($release['package']) || ! $version || ! version_compare($version, SPA_VERSION, '>') ) {
        return $transient;
    }

    $transient->response[$plugin_file] = (object) array(
        'id' => 'https://github.com/' . SPA_UPDATE_REPOSITORY,
        'slug' => dirname($plugin_file),
        'plugin' => $plugin_file,
        'new_version' => $version,
        'url' => 'https://github.com/' . SPA_UPDATE_REPOSITORY,
        'package' => $release['package'],
        'icons' => array(),
        'tested' => '',
        'requires_php' => '8.0',
    );

    return $transient;
}
add_filter('site_transient_update_plugins', 'spa_github_update_plugins');
add_filter('pre_set_site_transient_update_plugins', 'spa_github_update_plugins');
add_filter('pre_site_transient_update_plugins', 'spa_github_update_plugins');

function spa_github_plugin_information($result, $action, $args) {
    if ( 'plugin_information' !== $action || empty($args->slug) || 'stpauls-admin' !== $args->slug ) {
        return $result;
    }

    $release = spa_get_github_release();
    $version = isset($release['tag_name']) ? ltrim((string) $release['tag_name'], 'vV') : '';
    if ( ! $version ) {
        return $result;
    }

    return (object) array(
        'name' => "St. Paul's Admin",
        'slug' => 'stpauls-admin',
        'version' => $version,
        'author' => '<a href="https://github.com/revcarlson3">Rev. Daniel Carlson</a>',
        'homepage' => 'https://github.com/' . SPA_UPDATE_REPOSITORY,
        'download_link' => isset($release['package']) ? $release['package'] : '',
        'sections' => array(
            'description' => 'St. Paul\'s Admin manages events, teams, volunteers, services, and scheduling.',
            'changelog' => ! empty($release['body']) ? wpautop(wp_kses_post($release['body'])) : 'See the GitHub release notes for this version.',
        ),
    );
}
add_filter('plugins_api', 'spa_github_plugin_information', 10, 3);
