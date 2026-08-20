<?php

// Shared by notification delivery and the admin template screen.
function spa_template_tags() {
    return array(
        '{first_name}'     => 'Recipient First Name',
        '{last_name}'      => 'Recipient Last Name',
        '{full_name}'      => 'Recipient Full Name',
        '{event_name}'     => 'Event Name',
        '{event_date}'     => 'Event Date',
        '{event_time}'     => 'Event Start Time',
        '{event_location}' => 'Event Location',
        '{team_name}'      => 'Team Name',
        '{readings}'       => 'Readers: Service Builder Readings Link',
        '{org_name}'       => 'Organization Name',
    );
}

function spa_process_template($body, $data = array()) {
    $defaults = array(
        'first_name'     => '',
        'last_name'      => '',
        'full_name'      => '',
        'event_name'     => '',
        'event_date'     => '',
        'event_time'     => '',
        'event_location' => '',
        'team_name'      => '',
        'readings'       => '',
        'org_name'       => get_option('spa_org_name', ''),
    );
    $data = wp_parse_args($data, $defaults);

    foreach ( $data as $key => $value ) {
        $body = str_replace('{' . $key . '}', $value, $body);
    }
    return $body;
}

function spa_team_receives_readings_link($team_id, $team_name) {
    $configured_team_ids = get_option('spa_readings_team_ids', null);
    if ( is_array($configured_team_ids) ) {
        return in_array((string) intval($team_id), array_map('strval', $configured_team_ids), true);
    }

    return strcasecmp(trim((string) $team_name), 'Readers') === 0;
}

function spa_get_readings_tag_value($team_name, $service_builder_url, $html = true, $team_id = 0) {
    if ( ! spa_team_receives_readings_link($team_id, $team_name) ) {
        return '';
    }

    $url = spa_sanitize_service_builder_url($service_builder_url);
    if ( $url === '' ) {
        return '';
    }

    if ( ! $html ) {
        return 'View the readings and propers for this service: ' . $url;
    }

    return sprintf(
        '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
        esc_url($url),
        esc_html('View the readings and propers for this service')
    );
}
