<?php

function spa_handle_settings_post() {
    // Handle saves via admin-post.php
    if ( ! isset($_POST['spa_settings_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['spa_settings_nonce']), 'spa_save_settings') ) {
        wp_die('Invalid nonce', 'Error', array('response' => 403));
    }
    if ( ! current_user_can('manage_options') ) {
        wp_die('Unauthorized', 'Error', array('response' => 403));
    }

    $posted_tab = isset($_POST['active_tab']) ? sanitize_text_field(wp_unslash($_POST['active_tab'])) : 'general';


    if ( $posted_tab === 'general' ) {
        update_option('spa_active_email_template', intval($_POST['spa_active_email_template'] ?? 0));
        update_option('spa_active_sms_template', intval($_POST['spa_active_sms_template'] ?? 0));
        update_option('spa_notifications_enabled', isset($_POST['spa_notifications_enabled']) ? 1 : 0);
        update_option('spa_notification_day_of_week', intval($_POST['spa_notification_day_of_week'] ?? 0));
        update_option('spa_notification_time', sanitize_text_field(wp_unslash($_POST['spa_notification_time'] ?? '')));
        update_option('spa_notification_reminder_24h', isset($_POST['spa_notification_reminder_24h']) ? 1 : 0);
    }

    if ( $posted_tab === 'services' ) {
        update_option('spa_sermons_page_id', isset($_POST['spa_sermons_page_id']) ? absint($_POST['spa_sermons_page_id']) : 0);
        update_option('spa_sermon_details_page_id', isset($_POST['spa_sermon_details_page_id']) ? absint($_POST['spa_sermon_details_page_id']) : 0);
    }

    if ( $posted_tab === 'email' ) {
        $notification_email = isset($_POST['spa_notification_email']) ? sanitize_email(wp_unslash($_POST['spa_notification_email'])) : '';
        $enable_email = isset($_POST['spa_enable_email']) ? 1 : 0;
        $email_provider = isset($_POST['spa_email_provider']) ? sanitize_text_field(wp_unslash($_POST['spa_email_provider'])) : 'wp_mail';

        update_option('spa_notification_email', $notification_email);
        update_option('spa_enable_email', $enable_email);
        update_option('spa_email_provider', $email_provider);

        // Provider-specific fields
        switch ( $email_provider ) {
            case 'smtp':
                $smtp_host = isset($_POST['spa_smtp_host']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_host'])) : '';
                $smtp_port = isset($_POST['spa_smtp_port']) ? intval($_POST['spa_smtp_port']) : 587;
                $smtp_user = isset($_POST['spa_smtp_user']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_user'])) : '';
                $smtp_pass = isset($_POST['spa_smtp_pass']) ? wp_unslash($_POST['spa_smtp_pass']) : '';
                $smtp_enc  = isset($_POST['spa_smtp_encryption']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_encryption'])) : 'tls';
                $smtp_from_address = isset($_POST['spa_smtp_from_address']) ? sanitize_email(wp_unslash($_POST['spa_smtp_from_address'])) : '';
                $smtp_from_name = isset($_POST['spa_smtp_from_name']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_from_name'])) : '';

                update_option('spa_smtp_host', $smtp_host);
                update_option('spa_smtp_port', $smtp_port);
                update_option('spa_smtp_user', $smtp_user);
                if ($smtp_pass !== '') update_option('spa_smtp_pass', $smtp_pass);
                update_option('spa_smtp_encryption', $smtp_enc);
                update_option('spa_smtp_from_address', $smtp_from_address);
                update_option('spa_smtp_from_name', $smtp_from_name);
                break;

            case 'sendgrid':
                $sendgrid_key = isset($_POST['spa_sendgrid_api_key']) ? sanitize_text_field(wp_unslash($_POST['spa_sendgrid_api_key'])) : '';
                $sendgrid_from_address = isset($_POST['spa_sendgrid_from']) ? sanitize_email(wp_unslash($_POST['spa_sendgrid_from'])) : '';
                $sendgrid_from_name = isset($_POST['spa_sendgrid_from_name']) ? sanitize_text_field(wp_unslash($_POST['spa_sendgrid_from_name'])) : '';
                $sendgrid_webhook_key = isset($_POST['spa_sendgrid_webhook_public_key']) ? sanitize_textarea_field(wp_unslash($_POST['spa_sendgrid_webhook_public_key'])) : '';
                if ($sendgrid_key !== '') update_option('spa_sendgrid_api_key', $sendgrid_key);
                update_option('spa_sendgrid_from', $sendgrid_from_address);
                update_option('spa_sendgrid_from_name', $sendgrid_from_name);
                update_option('spa_sendgrid_webhook_public_key', $sendgrid_webhook_key);
                break;

            case 'mailgun':
                $mailgun_key = isset($_POST['spa_mailgun_api_key']) ? sanitize_text_field(wp_unslash($_POST['spa_mailgun_api_key'])) : '';
                $mailgun_domain = isset($_POST['spa_mailgun_domain']) ? sanitize_text_field(wp_unslash($_POST['spa_mailgun_domain'])) : '';
                if ($mailgun_key !== '') update_option('spa_mailgun_api_key', $mailgun_key);
                update_option('spa_mailgun_domain', $mailgun_domain);
                break;

            case 'mailpoet':
                $mailpoet_list = isset($_POST['spa_mailpoet_list']) ? sanitize_text_field(wp_unslash($_POST['spa_mailpoet_list'])) : '';
                update_option('spa_mailpoet_list', $mailpoet_list);
                break;

            case 'ses':
                $ses_key = isset($_POST['spa_ses_key']) ? sanitize_text_field(wp_unslash($_POST['spa_ses_key'])) : '';
                $ses_secret = isset($_POST['spa_ses_secret']) ? wp_unslash($_POST['spa_ses_secret']) : '';
                $ses_region = isset($_POST['spa_ses_region']) ? sanitize_text_field(wp_unslash($_POST['spa_ses_region'])) : '';
                if ($ses_key !== '') update_option('spa_ses_key', $ses_key);
                if ($ses_secret !== '') update_option('spa_ses_secret', $ses_secret);
                update_option('spa_ses_region', $ses_region);
                break;

            case 'postmark':
                $postmark_token = isset($_POST['spa_postmark_token']) ? sanitize_text_field(wp_unslash($_POST['spa_postmark_token'])) : '';
                $postmark_from = isset($_POST['spa_postmark_from']) ? sanitize_email(wp_unslash($_POST['spa_postmark_from'])) : '';
                if ($postmark_token !== '') update_option('spa_postmark_token', $postmark_token);
                update_option('spa_postmark_from', $postmark_from);
                break;

            case 'mailersend':
                $mailersend_token = isset($_POST['spa_mailersend_token']) ? sanitize_text_field(wp_unslash($_POST['spa_mailersend_token'])) : '';
                $mailersend_from = isset($_POST['spa_mailersend_from']) ? sanitize_email(wp_unslash($_POST['spa_mailersend_from'])) : '';
                if ($mailersend_token !== '') update_option('spa_mailersend_token', $mailersend_token);
                update_option('spa_mailersend_from', $mailersend_from);
                break;

            default:
                break;
        }
    }

    if ( $posted_tab === 'sms' ) {
        $sms_provider = isset($_POST['spa_sms_provider']) ? sanitize_text_field(wp_unslash($_POST['spa_sms_provider'])) : '';
        $enable_sms = isset($_POST['spa_enable_sms']) ? 1 : 0;
            $default_country = isset($_POST['spa_sms_default_country']) ? sanitize_text_field(wp_unslash($_POST['spa_sms_default_country'])) : 'US';
            update_option('spa_sms_provider', $sms_provider);
            update_option('spa_enable_sms', $enable_sms);
            update_option('spa_sms_default_country', $default_country);

            // Provider-specific fields for SMS providers
            switch ( $sms_provider ) {
                case 'twilio':
                    $tw_sid = isset($_POST['spa_twilio_sid']) ? sanitize_text_field(wp_unslash($_POST['spa_twilio_sid'])) : '';
                    $tw_token = isset($_POST['spa_twilio_token']) ? sanitize_text_field(wp_unslash($_POST['spa_twilio_token'])) : '';
                    $tw_from = isset($_POST['spa_twilio_from']) ? sanitize_text_field(wp_unslash($_POST['spa_twilio_from'])) : '';
                    if ($tw_sid !== '') update_option('spa_twilio_sid', $tw_sid);
                    if ($tw_token !== '') update_option('spa_twilio_token', $tw_token);
                    update_option('spa_twilio_from', $tw_from);
                    break;
                case 'vonage':
                    $vn_key = isset($_POST['spa_vonage_key']) ? sanitize_text_field(wp_unslash($_POST['spa_vonage_key'])) : '';
                    $vn_secret = isset($_POST['spa_vonage_secret']) ? sanitize_text_field(wp_unslash($_POST['spa_vonage_secret'])) : '';
                    $vn_from = isset($_POST['spa_vonage_from']) ? sanitize_text_field(wp_unslash($_POST['spa_vonage_from'])) : '';
                    if ($vn_key !== '') update_option('spa_vonage_key', $vn_key);
                    if ($vn_secret !== '') update_option('spa_vonage_secret', $vn_secret);
                    update_option('spa_vonage_from', $vn_from);
                    break;
                case 'plivo':
                    $pl_id = isset($_POST['spa_plivo_auth_id']) ? sanitize_text_field(wp_unslash($_POST['spa_plivo_auth_id'])) : '';
                    $pl_token = isset($_POST['spa_plivo_auth_token']) ? sanitize_text_field(wp_unslash($_POST['spa_plivo_auth_token'])) : '';
                    $pl_from = isset($_POST['spa_plivo_from']) ? sanitize_text_field(wp_unslash($_POST['spa_plivo_from'])) : '';
                    if ($pl_id !== '') update_option('spa_plivo_auth_id', $pl_id);
                    if ($pl_token !== '') update_option('spa_plivo_auth_token', $pl_token);
                    update_option('spa_plivo_from', $pl_from);
                    break;
                case 'messagebird':
                    $mb_key = isset($_POST['spa_messagebird_key']) ? sanitize_text_field(wp_unslash($_POST['spa_messagebird_key'])) : '';
                    $mb_from = isset($_POST['spa_messagebird_from']) ? sanitize_text_field(wp_unslash($_POST['spa_messagebird_from'])) : '';
                    if ($mb_key !== '') update_option('spa_messagebird_key', $mb_key);
                    update_option('spa_messagebird_from', $mb_from);
                    break;
                case 'textmagic':
                    $tm_user = isset($_POST['spa_textmagic_username']) ? sanitize_text_field(wp_unslash($_POST['spa_textmagic_username'])) : '';
                    $tm_key = isset($_POST['spa_textmagic_api_key']) ? sanitize_text_field(wp_unslash($_POST['spa_textmagic_api_key'])) : '';
                    $tm_from = isset($_POST['spa_textmagic_from']) ? sanitize_text_field(wp_unslash($_POST['spa_textmagic_from'])) : '';
                    if ($tm_user !== '') update_option('spa_textmagic_username', $tm_user);
                    if ($tm_key !== '') update_option('spa_textmagic_api_key', $tm_key);
                    update_option('spa_textmagic_from', $tm_from);
                break;
                default:
                    break;
            }
        }

    if ( $posted_tab === 'push' ) {
        $enable_push = isset($_POST['spa_enable_push']) ? 1 : 0;
        $push_provider = isset($_POST['spa_push_provider']) ? sanitize_text_field(wp_unslash($_POST['spa_push_provider'])) : 'onesignal';

        update_option('spa_enable_push', $enable_push);
        update_option('spa_push_provider', $push_provider);

        // Provider-specific fields
        switch ($push_provider) {
            case 'onesignal':
                $app_id = isset($_POST['spa_onesignal_app_id']) ? sanitize_text_field(wp_unslash($_POST['spa_onesignal_app_id'])) : '';
                $api_key = isset($_POST['spa_onesignal_api_key']) ? sanitize_text_field(wp_unslash($_POST['spa_onesignal_api_key'])) : '';
                update_option('spa_onesignal_app_id', $app_id);
                if ($api_key !== '') update_option('spa_onesignal_api_key', $api_key);
                break;
            case 'firebase':
                $project_id = isset($_POST['spa_firebase_project_id']) ? sanitize_text_field(wp_unslash($_POST['spa_firebase_project_id'])) : '';
                $server_key = isset($_POST['spa_firebase_server_key']) ? sanitize_text_field(wp_unslash($_POST['spa_firebase_server_key'])) : '';
                update_option('spa_firebase_project_id', $project_id);
                if ($server_key !== '') update_option('spa_firebase_server_key', $server_key);
                break;
            default:
                break;
        }
    }

    if ( $posted_tab === 'templates' ) {
        $example_template = isset($_POST['spa_example_template']) ? wp_kses_post(wp_unslash($_POST['spa_example_template'])) : '';
        update_option('spa_example_template', $example_template);
    }

    // If a test send was requested, send a test email and include result in the redirect
    $test_result = '';
    if ( isset($_POST['spa_send_test']) ) {
        $test_to = isset($_POST['spa_test_recipient']) ? sanitize_email(wp_unslash($_POST['spa_test_recipient'])) : '';
        if ( empty($test_to) ) {
            $test_result = 'missing_recipient';
        } else {
            $sent = spa_send_email(
                $test_to,
                'St. Paul\'s Admin - Test Email',
                '<p>This is a test email sent from the plugin to verify provider settings.</p>'
            );
            if ( is_wp_error($sent) ) {
                $test_result = 'error:' . rawurlencode($sent->get_error_message());
            } else {
                $test_result = 'sent';
            }
        }
    }

    // Redirect back to avoid re-post on refresh
    $redirect_args = array('page' => 'spa-settings', 'tab' => $posted_tab, 'saved' => '1');
    if ( $test_result !== '' ) {
        $redirect_args['test'] = $test_result;
    }
    $redirect_url = add_query_arg($redirect_args, admin_url('admin.php'));
    wp_safe_redirect($redirect_url);
    exit;

}
add_action('admin_post_spa_save_settings', 'spa_handle_settings_post');

add_action('wp_ajax_spa_get_report', 'spa_get_report_ajax');
add_action('admin_post_spa_export_report', 'spa_export_report');

function spa_get_report_definitions() {
    return array(
        'volunteers' => array(
            'label' => 'Volunteers',
            'columns' => array('First Name', 'Last Name', 'Email', 'Phone'),
        ),
        'teams' => array(
            'label' => 'Teams',
            'columns' => array('Team Name', 'Description'),
        ),
        'teams_with_volunteers' => array(
            'label' => 'Team Assignments',
            'columns' => array('Team Name', 'Volunteers'),
        ),
        'volunteers_with_teams' => array(
            'label' => 'Volunteer Assignments',
            'columns' => array('Volunteer Name', 'Email', 'Phone', 'Teams'),
        ),
        'rotation_report' => array(
            'label' => 'Rotation Report',
            'columns' => array(),
        ),
        'schedule_report' => array(
            'label' => 'Schedule Report',
            'columns' => array(),
        ),
    );
}

function spa_get_schedule_report_date_range($start_date = '', $end_date = '') {
    $today = current_datetime();
    $default_start = $today->format('Y-m-d');
    $default_end_date = clone $today;
    $default_end = $default_end_date->modify('+2 months')->format('Y-m-d');
    $start_date = $start_date !== '' ? $start_date : $default_start;
    $end_date = $end_date !== '' ? $end_date : $default_end;

    foreach ( array($start_date, $end_date) as $date ) {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ( ! $parsed || $parsed->format('Y-m-d') !== $date ) {
            return new WP_Error('invalid_schedule_report_date', 'Enter valid start and end dates.');
        }
    }
    if ( $start_date > $end_date ) {
        return new WP_Error('invalid_schedule_report_range', 'The schedule report start date must be on or before the end date.');
    }

    return array(
        'start_date' => $start_date,
        'end_date' => $end_date,
    );
}

function spa_get_report_rows($report_key, $filters = array()) {
    global $wpdb;

    switch ($report_key) {
        case 'volunteers':
            return $wpdb->get_results(
                "SELECT first_name, last_name, email, phone
                 FROM {$wpdb->prefix}spa_volunteers
                 WHERE active = 1
                 ORDER BY last_name, first_name",
                ARRAY_A
            );

        case 'teams':
            return $wpdb->get_results(
                "SELECT name, description
                 FROM {$wpdb->prefix}spa_teams
                 WHERE active = 1
                 ORDER BY name",
                ARRAY_A
            );

        case 'teams_with_volunteers':
            $raw_rows = $wpdb->get_results(
                "SELECT
                    t.id,
                    t.name AS team_name,
                    CONCAT(v.first_name, ' ', v.last_name) AS volunteer_name,
                    v.email,
                    v.phone
                 FROM {$wpdb->prefix}spa_teams t
                 LEFT JOIN {$wpdb->prefix}spa_volunteer_teams vt
                    ON vt.team_id = t.id
                 LEFT JOIN {$wpdb->prefix}spa_volunteers v
                    ON v.id = vt.volunteer_id
                    AND v.active = 1
                 WHERE t.active = 1
                 ORDER BY t.name, v.last_name, v.first_name",
                ARRAY_A
            );

            $grouped_rows = array();
            foreach ( $raw_rows as $row ) {
                $team_id = intval($row['id']);
                if ( ! isset($grouped_rows[$team_id]) ) {
                    $grouped_rows[$team_id] = array(
                        'team_name' => $row['team_name'],
                        'volunteers' => array(),
                    );
                }
                if ( ! empty($row['volunteer_name']) ) {
                    $grouped_rows[$team_id]['volunteers'][] = $row['volunteer_name'];
                }
            }

            $formatted_rows = array();
            foreach ( $grouped_rows as $grouped_row ) {
                $formatted_rows[] = array(
                    'team_name' => $grouped_row['team_name'],
                    'volunteers' => ! empty($grouped_row['volunteers']) ? implode("\n", $grouped_row['volunteers']) : '',
                );
            }

            return $formatted_rows;

        case 'volunteers_with_teams':
            $raw_rows = $wpdb->get_results(
                "SELECT
                    v.id,
                    CONCAT(v.first_name, ' ', v.last_name) AS volunteer_name,
                    v.email,
                    v.phone,
                    t.name AS team_name
                 FROM {$wpdb->prefix}spa_volunteers v
                 LEFT JOIN {$wpdb->prefix}spa_volunteer_teams vt
                    ON vt.volunteer_id = v.id
                 LEFT JOIN {$wpdb->prefix}spa_teams t
                    ON t.id = vt.team_id
                    AND t.active = 1
                 WHERE v.active = 1
                 ORDER BY v.last_name, v.first_name, t.name",
                ARRAY_A
            );

            $grouped_rows = array();
            foreach ( $raw_rows as $row ) {
                $volunteer_id = intval($row['id']);
                if ( ! isset($grouped_rows[$volunteer_id]) ) {
                    $grouped_rows[$volunteer_id] = array(
                        'volunteer_name' => $row['volunteer_name'],
                        'email' => $row['email'],
                        'phone' => $row['phone'],
                        'teams' => array(),
                    );
                }
                if ( ! empty($row['team_name']) ) {
                    $grouped_rows[$volunteer_id]['teams'][] = $row['team_name'];
                }
            }

            $formatted_rows = array();
            foreach ( $grouped_rows as $grouped_row ) {
                $formatted_rows[] = array(
                    'volunteer_name' => $grouped_row['volunteer_name'],
                    'email' => $grouped_row['email'],
                    'phone' => $grouped_row['phone'],
                    'teams' => ! empty($grouped_row['teams']) ? implode("\n", $grouped_row['teams']) : '',
                );
            }

            return $formatted_rows;

        case 'rotation_report':
            $service_types = $wpdb->get_results(
                "SELECT id, name
                 FROM {$wpdb->prefix}spa_service_types
                 WHERE active = 1
                 ORDER BY name",
                ARRAY_A
            );

            $teams = $wpdb->get_results(
                "SELECT id, name
                 FROM {$wpdb->prefix}spa_teams
                 WHERE active = 1
                 ORDER BY name",
                ARRAY_A
            );

            $team_rotations = array();
            $max_slots = 0;

            foreach ( $teams as $team ) {
                $team_rotations[$team['id']] = array();
            }

            foreach ( $service_types as $service_type ) {
                foreach ( $teams as $team ) {
                    $rotation_rows = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT
                                r.rotation_order,
                                r.is_next,
                                r.advance_rule,
                                CONCAT(v.first_name, ' ', v.last_name) AS volunteer_name
                             FROM {$wpdb->prefix}spa_team_rotations r
                             INNER JOIN {$wpdb->prefix}spa_volunteers v
                                ON v.id = r.volunteer_id
                                AND v.active = 1
                             WHERE r.service_type_id = %d
                             AND r.team_id = %d
                             ORDER BY r.rotation_order",
                            $service_type['id'],
                            $team['id']
                        ),
                        ARRAY_A
                    );

                    if ( empty($rotation_rows) ) {
                        continue;
                    }

                    $next_position = 1;
                    foreach ( $rotation_rows as $rotation_row ) {
                        if ( intval($rotation_row['is_next']) === 1 ) {
                            $next_position = intval($rotation_row['rotation_order']);
                            break;
                        }
                    }

                    $team_rotations[$team['id']][] = array(
                        'service_type_name' => $service_type['name'],
                        'advance_rule' => $rotation_rows[0]['advance_rule'],
                        'next_position' => $next_position,
                        'slots' => $rotation_rows,
                    );

                    $max_slots = max($max_slots, count($rotation_rows));
                }
            }

            $headers = array('Rotation Slot');
            foreach ( $teams as $team ) {
                $headers[] = $team['name'];
            }

            $formatted_rows = array();
            for ( $slot = 1; $slot <= $max_slots; $slot++ ) {
                $row = array('slot' => 'Slot ' . $slot);
                foreach ( $teams as $team ) {
                    $cells = array();
                    if ( ! empty($team_rotations[$team['id']]) ) {
                        foreach ( $team_rotations[$team['id']] as $rotation_set ) {
                            if ( isset($rotation_set['slots'][$slot - 1]) ) {
                                $cell = $rotation_set['slots'][$slot - 1]['volunteer_name'];
                                if ( intval($rotation_set['slots'][$slot - 1]['rotation_order']) === intval($rotation_set['next_position']) ) {
                                    $cell .= ' ← Next';
                                }
                                $cells[] = $rotation_set['service_type_name'] . ': ' . $cell;
                            }
                        }
                    }
                    $row[$team['name']] = implode("\n", $cells);
                }
                $formatted_rows[] = $row;
            }

            return array(
                'headers' => $headers,
                'rows' => $formatted_rows,
            );

        case 'schedule_report':
            $date_range = spa_get_schedule_report_date_range(
                isset($filters['start_date']) ? $filters['start_date'] : '',
                isset($filters['end_date']) ? $filters['end_date'] : ''
            );
            if ( is_wp_error($date_range) ) {
                return $date_range;
            }

            $events = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, event_date, start_time
                     FROM {$wpdb->prefix}spa_events
                     WHERE active = 1
                     AND event_date BETWEEN %s AND %s
                     ORDER BY event_date, start_time, id",
                    $date_range['start_date'],
                    $date_range['end_date']
                ),
                ARRAY_A
            );
            $teams = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DISTINCT t.id, t.name
                     FROM {$wpdb->prefix}spa_teams t
                     INNER JOIN {$wpdb->prefix}spa_event_volunteers ev
                        ON ev.team_id = t.id
                     INNER JOIN {$wpdb->prefix}spa_volunteers v
                        ON v.id = ev.volunteer_id
                     INNER JOIN {$wpdb->prefix}spa_events e
                        ON e.id = ev.event_id
                        AND e.active = 1
                     WHERE e.event_date BETWEEN %s AND %s
                     ORDER BY t.name",
                    $date_range['start_date'],
                    $date_range['end_date']
                ),
                ARRAY_A
            );
            $assignments = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
                        ev.event_id,
                        ev.team_id,
                        CONCAT(v.first_name, ' ', v.last_name) AS volunteer_name
                     FROM {$wpdb->prefix}spa_event_volunteers ev
                     INNER JOIN {$wpdb->prefix}spa_events e
                        ON e.id = ev.event_id
                        AND e.active = 1
                     INNER JOIN {$wpdb->prefix}spa_volunteers v
                        ON v.id = ev.volunteer_id
                     WHERE e.event_date BETWEEN %s AND %s
                     ORDER BY ev.event_id, ev.team_id, v.last_name, v.first_name",
                    $date_range['start_date'],
                    $date_range['end_date']
                ),
                ARRAY_A
            );

            $assigned_by_event_team = array();
            foreach ( $assignments as $assignment ) {
                $assigned_by_event_team[intval($assignment['event_id'])][intval($assignment['team_id'])][] = $assignment['volunteer_name'];
            }

            $headers = array('Date');
            foreach ( $teams as $team ) {
                $headers[] = $team['name'];
            }

            $formatted_rows = array();
            foreach ( $events as $event ) {
                $event_date = DateTimeImmutable::createFromFormat('!Y-m-d', $event['event_date']);
                $row = array(
                    $event_date ? $event_date->format('M j, y') : $event['event_date'],
                );
                foreach ( $teams as $team ) {
                    $names = isset($assigned_by_event_team[intval($event['id'])][intval($team['id'])])
                        ? $assigned_by_event_team[intval($event['id'])][intval($team['id'])]
                        : array();
                    $row[] = implode(" /\r\n", $names);
                }
                $formatted_rows[] = $row;
            }

            return array(
                'headers' => $headers,
                'rows' => $formatted_rows,
                'filters' => $date_range,
            );
    }

    return array();
}

function spa_get_report_ajax() {
    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'), 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }

    $report_key = isset($_POST['report_key']) ? sanitize_text_field(wp_unslash($_POST['report_key'])) : '';
    $definitions = spa_get_report_definitions();
    if ( empty($definitions[$report_key]) ) {
        wp_send_json_error(array('message' => 'Invalid report.'));
    }

    $report_filters = array();
    if ( $report_key === 'schedule_report' ) {
        $date_range = spa_get_schedule_report_date_range(
            isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : '',
            isset($_POST['end_date']) ? sanitize_text_field(wp_unslash($_POST['end_date'])) : ''
        );
        if ( is_wp_error($date_range) ) {
            wp_send_json_error(array('message' => $date_range->get_error_message()));
        }
        $report_filters = $date_range;
    }

    $rows = spa_get_report_rows($report_key, $report_filters);
    if ( is_wp_error($rows) ) {
        wp_send_json_error(array('message' => $rows->get_error_message()));
    }
    $custom_headers = array();
    if ( in_array($report_key, array('rotation_report', 'schedule_report'), true) && isset($rows['headers'], $rows['rows']) ) {
        $custom_headers = $rows['headers'];
        if ( isset($rows['filters']) ) {
            $report_filters = $rows['filters'];
        }
        $rows = $rows['rows'];
    }

    $export_url = function($format) use ($report_key, $report_filters) {
        $args = array(
            'action' => 'spa_export_report',
            'report_key' => $report_key,
            'format' => $format,
        );
        if ( $report_key === 'schedule_report' ) {
            $args['start_date'] = $report_filters['start_date'];
            $args['end_date'] = $report_filters['end_date'];
        }
        return wp_nonce_url(
            add_query_arg($args, admin_url('admin-post.php')),
            'spa_export_report_' . $report_key
        );
    };

    ob_start();
    ?>
    <div class="spa-report-modal-content">
        <h2><?php echo esc_html($definitions[$report_key]['label']); ?></h2>
        <?php if ( $report_key === 'schedule_report' ) : ?>
            <div class="spa-schedule-report-filters" style="margin:0 0 12px;display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
                <label>
                    <span style="display:block;font-weight:600;margin-bottom:3px;">Start date</span>
                    <input type="date" id="spa-schedule-report-start" value="<?php echo esc_attr($report_filters['start_date']); ?>" required>
                </label>
                <label>
                    <span style="display:block;font-weight:600;margin-bottom:3px;">End date</span>
                    <input type="date" id="spa-schedule-report-end" value="<?php echo esc_attr($report_filters['end_date']); ?>" required>
                </label>
                <button type="button" class="button spa-refresh-schedule-report">Update Report</button>
            </div>
        <?php endif; ?>
        <div style="margin:0 0 12px;display:flex;gap:8px;flex-wrap:wrap;">
            <a class="button button-primary" href="<?php echo esc_url($export_url('xlsx')); ?>">Export Excel</a>
            <a class="button" href="<?php echo esc_url($export_url('csv')); ?>">Export CSV</a>
            <a class="button" href="<?php echo esc_url($export_url('pdf')); ?>" target="_blank">Export PDF</a>
            <button type="button" class="button spa-print-report">Print</button>
        </div>
        <div class="spa-report-table-wrap" style="max-height:58vh;overflow:auto;padding-bottom:24px;">
            <table class="widefat striped" style="border-collapse:collapse;">
                <thead>
                    <tr>
                        <?php if ( in_array($report_key, array('rotation_report', 'schedule_report'), true) ) : ?>
                            <?php foreach ( $custom_headers as $column ) : ?>
                                <th style="break-inside:avoid;page-break-inside:avoid;"><?php echo esc_html($column); ?></th>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <?php foreach ( $definitions[$report_key]['columns'] as $column ) : ?>
                                <th style="break-inside:avoid;page-break-inside:avoid;"><?php echo esc_html($column); ?></th>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty($rows) ) : ?>
                        <tr><td colspan="<?php echo intval(in_array($report_key, array('rotation_report', 'schedule_report'), true) ? count($custom_headers) : count($definitions[$report_key]['columns'])); ?>" style="break-inside:avoid;page-break-inside:avoid;">No data found.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $rows as $row ) : ?>
                            <tr style="break-inside:avoid;page-break-inside:avoid;">
                                <?php foreach ( $row as $value ) : ?>
                                    <td style="vertical-align:top;break-inside:avoid;page-break-inside:avoid;"><?php echo nl2br(esc_html((string) $value)); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    wp_send_json_success(array('html' => ob_get_clean()));
}

function spa_export_report() {
    if ( ! current_user_can('manage_options') ) {
        wp_die('Unauthorized');
    }

    $report_key = isset($_GET['report_key']) ? sanitize_text_field(wp_unslash($_GET['report_key'])) : '';
    $format = isset($_GET['format']) ? sanitize_text_field(wp_unslash($_GET['format'])) : 'csv';
    $definitions = spa_get_report_definitions();
    if ( empty($definitions[$report_key]) ) {
        wp_die('Invalid report');
    }
    if ( ! check_admin_referer('spa_export_report_' . $report_key) ) {
        wp_die('Nonce failed');
    }

    $report_filters = array();
    if ( $report_key === 'schedule_report' ) {
        $date_range = spa_get_schedule_report_date_range(
            isset($_GET['start_date']) ? sanitize_text_field(wp_unslash($_GET['start_date'])) : '',
            isset($_GET['end_date']) ? sanitize_text_field(wp_unslash($_GET['end_date'])) : ''
        );
        if ( is_wp_error($date_range) ) {
            wp_die(esc_html($date_range->get_error_message()));
        }
        $report_filters = $date_range;
    }

    $rows = spa_get_report_rows($report_key, $report_filters);
    if ( is_wp_error($rows) ) {
        wp_die(esc_html($rows->get_error_message()));
    }
    $headers = $definitions[$report_key]['columns'];
    if ( in_array($report_key, array('rotation_report', 'schedule_report'), true) && isset($rows['headers'], $rows['rows']) ) {
        $headers = $rows['headers'];
        $rows = $rows['rows'];
    }
    $filename_base = 'spa-report-' . $report_key . '-' . date('Y-m-d');

    if ( $format === 'xlsx' && class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet') && class_exists('\PhpOffice\PhpSpreadsheet\Writer\Xlsx') ) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $row_index = 2;
        foreach ( $rows as $row ) {
            $sheet->fromArray(array_values($row), null, 'A' . $row_index);
            $row_index++;
        }
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow())
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical('top');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename_base . '.xlsx"');
        header('Pragma: no-cache');
        header('Expires: 0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    if ( $format === 'pdf' ) {
        header('Content-Type: text/html; charset=UTF-8');
        $printed_at = current_time('F j, Y g:i A');
        $is_schedule_report = $report_key === 'schedule_report';
        $schedule_font_size = count($headers) > 10 ? 7 : (count($headers) > 7 ? 8 : 9);
        $schedule_zoom = count($headers) > 14 ? 0.7 : (count($headers) > 10 ? 0.8 : (count($headers) > 7 ? 0.9 : 1));
        ?>
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <title><?php echo esc_html($definitions[$report_key]['label']); ?></title>
            <style>
                @page { size: landscape; margin: <?php echo $is_schedule_report ? '7mm' : '18px 18px 88px 18px'; ?>; }
                body {
                    font-family: Arial, sans-serif;
                    padding: <?php echo $is_schedule_report ? '0 0 28px' : '24px 24px 88px 24px'; ?>;
                    font-size: <?php echo $is_schedule_report ? intval($schedule_font_size) . 'px' : 'initial'; ?>;
                    zoom: <?php echo $is_schedule_report ? esc_attr($schedule_zoom) : '1'; ?>;
                }
                h1 { margin-bottom: 16px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
                th { background: #f5f5f5; }
                tr, td, th { break-inside: avoid; page-break-inside: avoid; }
                <?php if ( $is_schedule_report ) : ?>
                    h1 { margin: 0 0 7px; font-size: 15px; }
                    table { font-size: <?php echo intval($schedule_font_size); ?>px; line-height: 1.15; }
                    th, td { padding: 2px 3px; }
                    th { font-size: <?php echo max(7, intval($schedule_font_size) - 1); ?>px; }
                    td { white-space: nowrap; }
                    th:first-child, td:first-child { width: 58px; }
                <?php endif; ?>
                .spa-report-footer {
                    position: fixed;
                    left: <?php echo $is_schedule_report ? '0' : '24px'; ?>;
                    right: <?php echo $is_schedule_report ? '0' : '24px'; ?>;
                    bottom: -4px;
                    display: flex;
                    justify-content: space-between;
                    font-size: <?php echo $is_schedule_report ? '8px' : '12px'; ?>;
                    line-height: 1.4;
                    color: #555;
                }
            </style>
        </head>
        <body onload="window.print();">
            <h1><?php echo esc_html($definitions[$report_key]['label']); ?></h1>
            <table>
                <thead>
                    <tr>
                        <?php foreach ( $headers as $header ) : ?>
                            <th><?php echo esc_html($header); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $row ) : ?>
                        <tr>
                            <?php foreach ( $row as $value ) : ?>
                                <td><?php echo nl2br(esc_html(str_replace("\r\n", "\n", (string) $value))); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="spa-report-footer">
                <div>Report export</div>
                <div>Printed: <?php echo esc_html($printed_at); ?></div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    if ( function_exists('spa_export_csv') ) {
        spa_export_csv($filename_base . '.csv', $headers, $rows);
    }

    wp_die('Unable to export report.');
}


function spa_settings_admin_notices() {
    // Only show on the plugin settings page
    if ( ! isset($_GET['page']) || $_GET['page'] !== 'spa-settings' ) {
        return;
    }

    if ( isset($_GET['test']) ) {
        $test = wp_unslash($_GET['test']);
        if ( strpos($test, 'error:') === 0 ) {
            $msg = rawurldecode(substr($test, 6));
            echo '<div class="notice notice-error"><p>Test email failed: ' . esc_html($msg) . '</p></div>';
        } elseif ( $test === 'missing_recipient' ) {
            echo '<div class="notice notice-warning"><p>Test email not sent: recipient email missing.</p></div>';
        } elseif ( $test === 'sent' ) {
            echo '<div class="notice notice-success is-dismissible"><p>Test email sent successfully.</p></div>';
        } else {
            echo '<div class="notice notice-info"><p>Test result: ' . esc_html($test) . '</p></div>';
        }
    }
    if (
        get_option('spa_email_provider', 'wp_mail') === 'sendgrid'
        && get_option('spa_sendgrid_webhook_public_key', '') === ''
    ) {
        echo '<div class="notice notice-warning"><p><strong>SendGrid delivery failure tracking is not active.</strong> Configure the signed Event Webhook and paste its verification key on the Email tab.</p></div>';
    }
}
add_action('admin_notices', 'spa_settings_admin_notices');


/**
 * AJAX handler to save email settings and send test email without page reload.
 */
function spa_ajax_send_test_email() {
    check_ajax_referer('spa_admin_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error('Unauthorized', 403);
    }

    // Save basic email settings from POST
    $notification_email = isset($_POST['spa_notification_email']) ? sanitize_email(wp_unslash($_POST['spa_notification_email'])) : '';
    $enable_email = isset($_POST['spa_enable_email']) ? 1 : 0;
    $email_provider = isset($_POST['spa_email_provider']) ? sanitize_text_field(wp_unslash($_POST['spa_email_provider'])) : 'wp_mail';

    update_option('spa_notification_email', $notification_email);
    update_option('spa_enable_email', $enable_email);
    update_option('spa_email_provider', $email_provider);

    // Provider-specific saves (same rules as admin_post handler)
    switch ( $email_provider ) {
        case 'smtp':
            $smtp_host = isset($_POST['spa_smtp_host']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_host'])) : '';
            $smtp_port = isset($_POST['spa_smtp_port']) ? intval($_POST['spa_smtp_port']) : 587;
            $smtp_user = isset($_POST['spa_smtp_user']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_user'])) : '';
            $smtp_pass = isset($_POST['spa_smtp_pass']) ? wp_unslash($_POST['spa_smtp_pass']) : '';
            $smtp_enc  = isset($_POST['spa_smtp_encryption']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_encryption'])) : 'tls';
            $smtp_from_address = isset($_POST['spa_smtp_from_address']) ? sanitize_email(wp_unslash($_POST['spa_smtp_from_address'])) : '';
            $smtp_from_name = isset($_POST['spa_smtp_from_name']) ? sanitize_text_field(wp_unslash($_POST['spa_smtp_from_name'])) : '';

            update_option('spa_smtp_host', $smtp_host);
            update_option('spa_smtp_port', $smtp_port);
            update_option('spa_smtp_user', $smtp_user);
            if ($smtp_pass !== '') update_option('spa_smtp_pass', $smtp_pass);
            update_option('spa_smtp_encryption', $smtp_enc);
            update_option('spa_smtp_from_address', $smtp_from_address);
            update_option('spa_smtp_from_name', $smtp_from_name);
            break;

        case 'sendgrid':
            $sendgrid_key = isset($_POST['spa_sendgrid_api_key']) ? sanitize_text_field(wp_unslash($_POST['spa_sendgrid_api_key'])) : '';
            $sendgrid_from_address = isset($_POST['spa_sendgrid_from']) ? sanitize_email(wp_unslash($_POST['spa_sendgrid_from'])) : '';
            $sendgrid_from_name = isset($_POST['spa_sendgrid_from_name']) ? sanitize_text_field(wp_unslash($_POST['spa_sendgrid_from_name'])) : '';
            $sendgrid_webhook_key = isset($_POST['spa_sendgrid_webhook_public_key']) ? sanitize_textarea_field(wp_unslash($_POST['spa_sendgrid_webhook_public_key'])) : '';
            if ($sendgrid_key !== '') update_option('spa_sendgrid_api_key', $sendgrid_key);
            update_option('spa_sendgrid_from', $sendgrid_from_address);
            update_option('spa_sendgrid_from_name', $sendgrid_from_name);
            update_option('spa_sendgrid_webhook_public_key', $sendgrid_webhook_key);
            break;

        case 'mailgun':
            $mailgun_key = isset($_POST['spa_mailgun_api_key']) ? sanitize_text_field(wp_unslash($_POST['spa_mailgun_api_key'])) : '';
            $mailgun_domain = isset($_POST['spa_mailgun_domain']) ? sanitize_text_field(wp_unslash($_POST['spa_mailgun_domain'])) : '';
            if ($mailgun_key !== '') update_option('spa_mailgun_api_key', $mailgun_key);
            update_option('spa_mailgun_domain', $mailgun_domain);
            break;

        case 'mailpoet':
            $mailpoet_list = isset($_POST['spa_mailpoet_list']) ? sanitize_text_field(wp_unslash($_POST['spa_mailpoet_list'])) : '';
            update_option('spa_mailpoet_list', $mailpoet_list);
            break;

        case 'ses':
            $ses_key = isset($_POST['spa_ses_key']) ? sanitize_text_field(wp_unslash($_POST['spa_ses_key'])) : '';
            $ses_secret = isset($_POST['spa_ses_secret']) ? wp_unslash($_POST['spa_ses_secret']) : '';
            $ses_region = isset($_POST['spa_ses_region']) ? sanitize_text_field(wp_unslash($_POST['spa_ses_region'])) : '';
            if ($ses_key !== '') update_option('spa_ses_key', $ses_key);
            if ($ses_secret !== '') update_option('spa_ses_secret', $ses_secret);
            update_option('spa_ses_region', $ses_region);
            break;

        case 'postmark':
            $postmark_token = isset($_POST['spa_postmark_token']) ? sanitize_text_field(wp_unslash($_POST['spa_postmark_token'])) : '';
            $postmark_from = isset($_POST['spa_postmark_from']) ? sanitize_email(wp_unslash($_POST['spa_postmark_from'])) : '';
            if ($postmark_token !== '') update_option('spa_postmark_token', $postmark_token);
            update_option('spa_postmark_from', $postmark_from);
            break;

        case 'mailersend':
            $mailersend_token = isset($_POST['spa_mailersend_token']) ? sanitize_text_field(wp_unslash($_POST['spa_mailersend_token'])) : '';
            $mailersend_from = isset($_POST['spa_mailersend_from']) ? sanitize_email(wp_unslash($_POST['spa_mailersend_from'])) : '';
            if ($mailersend_token !== '') update_option('spa_mailersend_token', $mailersend_token);
            update_option('spa_mailersend_from', $mailersend_from);
            break;

        default:
            break;
    }

    // Perform the test send
    $test_to = isset($_POST['spa_test_recipient']) ? sanitize_email(wp_unslash($_POST['spa_test_recipient'])) : '';
    if ( empty($test_to) ) {
        wp_send_json_error('missing_recipient');
    }
    $test_event = (object) array();
    $test_volunteer = (object) array(
        'first_name' => $test_to,
        'last_name' => '(test)',
    );
    $test_log_id = spa_create_delivery_log($test_event, $test_volunteer, 'email', $email_provider);
    $sent = spa_send_email($test_to, 'St. Paul\'s Admin - Test Email', '<p>This is a test email sent from the plugin to verify provider settings.</p>');
    if ( is_wp_error($sent) ) {
    if ( $test_log_id ) {
        spa_mark_delivery_failed($test_log_id, $sent->get_error_message());
    }
    wp_send_json_error($sent->get_error_message());
}
if ( $test_log_id ) {
    spa_mark_delivery_sent($test_log_id, $sent, false);
}
    wp_send_json_success('sent');
}
add_action('wp_ajax_spa_send_test_email', 'spa_ajax_send_test_email');


function spa_ajax_send_test_sms() {
    check_ajax_referer('spa_admin_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error('Unauthorized', 403);
    }

    $sms_provider = isset($_POST['spa_sms_provider']) ? sanitize_text_field(wp_unslash($_POST['spa_sms_provider'])) : get_option('spa_sms_provider', 'twilio');
    $to = isset($_POST['spa_test_sms_recipient']) ? sanitize_text_field(wp_unslash($_POST['spa_test_sms_recipient'])) : '';
    if ( empty($to) ) {
        wp_send_json_error('missing_recipient');
    }

    // Include helpers and attempt server-side normalization to E.164 when possible
    if ( ! function_exists('spa_is_e164') || ! function_exists('spa_normalize_phone') ) {
        if ( file_exists(plugin_dir_path(__FILE__) . 'helpers.php') ) {
            include_once plugin_dir_path(__FILE__) . 'helpers.php';
        }
    }

    // Try to normalize using saved default country (falls back to US)
    $default_country = get_option('spa_sms_default_country', 'US');
    if ( function_exists('spa_normalize_phone') ) {
        $normalized = spa_normalize_phone($to, $default_country);
        if ( $normalized !== false ) {
            $to = $normalized;
        }
    }

    // Validate phone number format for providers that require E.164
    $requires_e164 = array('twilio','vonage','plivo','messagebird','textmagic');
    if ( in_array($sms_provider, $requires_e164, true) ) {
        if ( ! function_exists('spa_is_e164') || ! spa_is_e164($to) ) {
            wp_send_json_error('invalid_phone_format:Phone number must be in E.164 format (e.g. +15551234567) for the selected provider.');
        }
    }

    // Test the values currently shown in the form without requiring a separate save.
    $enable_sms = isset($_POST['spa_enable_sms']) ? 1 : 0;
    update_option('spa_sms_provider', $sms_provider);
    update_option('spa_enable_sms', $enable_sms);
    if ( isset($_POST['spa_sms_default_country']) ) {
        update_option('spa_sms_default_country', sanitize_text_field(wp_unslash($_POST['spa_sms_default_country'])));
    }

    // Only update fields that were submitted so a test cannot erase saved settings.
    switch ($sms_provider) {
        case 'twilio':
            if ( isset($_POST['spa_twilio_sid']) ) {
                update_option('spa_twilio_sid', sanitize_text_field(wp_unslash($_POST['spa_twilio_sid'])));
            }
            if ( ! empty($_POST['spa_twilio_token']) ) {
                update_option('spa_twilio_token', sanitize_text_field(wp_unslash($_POST['spa_twilio_token'])));
            }
            if ( isset($_POST['spa_twilio_from']) ) {
                update_option('spa_twilio_from', sanitize_text_field(wp_unslash($_POST['spa_twilio_from'])));
            }
            break;
        case 'vonage':
            if ( isset($_POST['spa_vonage_key']) ) {
                update_option('spa_vonage_key', sanitize_text_field(wp_unslash($_POST['spa_vonage_key'])));
            }
            if ( ! empty($_POST['spa_vonage_secret']) ) {
                update_option('spa_vonage_secret', sanitize_text_field(wp_unslash($_POST['spa_vonage_secret'])));
            }
            if ( isset($_POST['spa_vonage_from']) ) {
                update_option('spa_vonage_from', sanitize_text_field(wp_unslash($_POST['spa_vonage_from'])));
            }
            break;
        case 'plivo':
            if ( isset($_POST['spa_plivo_auth_id']) ) {
                update_option('spa_plivo_auth_id', sanitize_text_field(wp_unslash($_POST['spa_plivo_auth_id'])));
            }
            if ( ! empty($_POST['spa_plivo_auth_token']) ) {
                update_option('spa_plivo_auth_token', sanitize_text_field(wp_unslash($_POST['spa_plivo_auth_token'])));
            }
            if ( isset($_POST['spa_plivo_from']) ) {
                update_option('spa_plivo_from', sanitize_text_field(wp_unslash($_POST['spa_plivo_from'])));
            }
            break;
        case 'messagebird':
            if ( ! empty($_POST['spa_messagebird_key']) ) {
                update_option('spa_messagebird_key', sanitize_text_field(wp_unslash($_POST['spa_messagebird_key'])));
            }
            if ( isset($_POST['spa_messagebird_from']) ) {
                update_option('spa_messagebird_from', sanitize_text_field(wp_unslash($_POST['spa_messagebird_from'])));
            }
            break;
        case 'textmagic':
            if ( isset($_POST['spa_textmagic_username']) ) {
                update_option('spa_textmagic_username', sanitize_text_field(wp_unslash($_POST['spa_textmagic_username'])));
            }
            if ( ! empty($_POST['spa_textmagic_api_key']) ) {
                update_option('spa_textmagic_api_key', sanitize_text_field(wp_unslash($_POST['spa_textmagic_api_key'])));
            }
            if ( isset($_POST['spa_textmagic_from']) ) {
                update_option('spa_textmagic_from', sanitize_text_field(wp_unslash($_POST['spa_textmagic_from'])));
            }
            break;
        default:
            break;
    }

    $test_event = (object) array();
    $test_volunteer = (object) array(
        'first_name' => $to,
        'last_name' => '(test)',
    );
    $test_log_id = spa_create_delivery_log($test_event, $test_volunteer, 'sms', $sms_provider);
    $sent = spa_send_sms($to, "Test message from St. Paul's Admin plugin", $sms_provider);
    if ( is_wp_error($sent) ) {
        if ( $test_log_id ) {
            spa_mark_delivery_failed($test_log_id, $sent->get_error_message());
        }
        wp_send_json_error($sent->get_error_message());
    }
    if ( $test_log_id ) {
        spa_mark_delivery_sent($test_log_id, $sent, false);
    }
    wp_send_json_success('sent');
}
add_action('wp_ajax_spa_send_test_sms', 'spa_ajax_send_test_sms');

function spa_ajax_send_test_notification() {
    global $wpdb;

    check_ajax_referer('spa_admin_nonce', 'nonce');
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error('Unauthorized', 403);
    }

    $email_to = isset($_POST['spa_test_notification_email']) ? sanitize_email(wp_unslash($_POST['spa_test_notification_email'])) : '';
    $phone_to = isset($_POST['spa_test_notification_phone']) ? sanitize_text_field(wp_unslash($_POST['spa_test_notification_phone'])) : '';
    if ( empty($email_to) && empty($phone_to) ) {
        wp_send_json_error('missing_recipient');
    }

    $team = $wpdb->get_row($wpdb->prepare(
        "SELECT id, name
         FROM {$wpdb->prefix}spa_teams
         WHERE name = %s
         AND active = 1
         LIMIT 1",
        'Clergy'
    ));

    $volunteer = null;
    if ( $team ) {
        $volunteer = $wpdb->get_row($wpdb->prepare(
            "SELECT v.first_name, v.last_name, v.email, v.phone
             FROM {$wpdb->prefix}spa_volunteers v
             INNER JOIN {$wpdb->prefix}spa_volunteer_teams vt ON vt.volunteer_id = v.id
             WHERE vt.team_id = %d
             AND v.active = 1
             ORDER BY v.last_name, v.first_name
             LIMIT 1",
            $team->id
        ));
    }

    $event = $wpdb->get_row(
        "SELECT id, name, event_date, start_time, location, service_builder_url
         FROM {$wpdb->prefix}spa_events
         ORDER BY event_date DESC, start_time DESC
         LIMIT 1"
    );

    if ( ! $event ) {
        wp_send_json_error('no_events');
    }

    $sample_first = $volunteer ? $volunteer->first_name : 'Test';
    $sample_last = $volunteer ? $volunteer->last_name : 'Volunteer';
    $sample_full = trim($sample_first . ' ' . $sample_last);
    $sample_phone = $volunteer && ! empty($volunteer->phone) ? $volunteer->phone : $phone_to;
    $sample_email = $volunteer && ! empty($volunteer->email) ? $volunteer->email : $email_to;
    $team_name = $team ? $team->name : 'Clergy';
    $email_readings = spa_get_readings_tag_value($team_name, $event->service_builder_url ?? '', true);
    $sms_readings = spa_get_readings_tag_value($team_name, $event->service_builder_url ?? '', false);

    $template_id = intval(get_option('spa_active_email_template', 0));
    $sms_template_id = intval(get_option('spa_active_sms_template', 0));
    $email_tpl = $template_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}spa_notification_templates WHERE id = %d", $template_id)) : null;
    $sms_tpl = $sms_template_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}spa_notification_templates WHERE id = %d", $sms_template_id)) : null;

    if ( $email_to && $email_tpl ) {
        $subject = spa_process_template($email_tpl->subject ?: 'Test Notification', array(
            'first_name' => $sample_first,
            'last_name' => $sample_last,
            'full_name' => $sample_full,
            'event_name' => $event->name,
            'event_date' => $event->event_date,
            'event_time' => $event->start_time,
            'event_location' => $event->location,
            'team_name' => $team_name,
            'readings' => '',
        ));
        $body = spa_process_template($email_tpl->body, array(
            'first_name' => $sample_first,
            'last_name' => $sample_last,
            'full_name' => $sample_full,
            'event_name' => $event->name,
            'event_date' => $event->event_date,
            'event_time' => $event->start_time,
            'event_location' => $event->location,
            'team_name' => $team_name,
            'readings' => $email_readings,
        ));
        $sent = spa_send_email($email_to, $subject, $body);
        if ( is_wp_error($sent) ) {
            wp_send_json_error($sent->get_error_message());
        }
    }

    if ( $phone_to && $sms_tpl ) {
        $sms_body = spa_process_template($sms_tpl->body, array(
            'first_name' => $sample_first,
            'last_name' => $sample_last,
            'full_name' => $sample_full,
            'event_name' => $event->name,
            'event_date' => $event->event_date,
            'event_time' => $event->start_time,
            'event_location' => $event->location,
            'team_name' => $team_name,
            'readings' => $sms_readings,
        ));
        $sent = spa_send_sms($phone_to, $sms_body);
        if ( is_wp_error($sent) ) {
            wp_send_json_error($sent->get_error_message());
        }
    }

    wp_send_json_success(array('message' => 'Test notification sent.'));
}
add_action('wp_ajax_spa_send_test_notification', 'spa_ajax_send_test_notification');


function spa_ajax_delete_secret() {
    if ( ! check_ajax_referer('spa_admin_nonce', 'nonce', false) ) {
        wp_send_json_error('Invalid nonce', 403);
    }
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error('Unauthorized', 403);
    }

    $option_name = isset($_POST['option']) ? sanitize_text_field(wp_unslash($_POST['option'])) : '';
    $allowed_options = array(
        'spa_smtp_pass',
        'spa_sendgrid_api_key',
        'spa_mailgun_api_key',
        'spa_ses_key',
        'spa_ses_secret',
        'spa_postmark_token',
        'spa_mailersend_token',
        'spa_twilio_token',
        'spa_vonage_secret',
        'spa_plivo_auth_token',
        'spa_messagebird_key',
        'spa_textmagic_api_key',
        'spa_onesignal_api_key',
        'spa_firebase_server_key',
    );
    if ( ! in_array($option_name, $allowed_options, true) ) {
        wp_send_json_error('Invalid credential option');
    }

    delete_option($option_name);
    wp_send_json_success('deleted');
}
add_action('wp_ajax_spa_delete_secret', 'spa_ajax_delete_secret');


function spa_settings_page() {

    $active_tab = isset($_REQUEST['tab'])
        ? sanitize_text_field(wp_unslash($_REQUEST['tab']))
        : 'general';

    $page_title = "Settings";
    include SPA_TEMPLATE_DIR . 'settings-page.php';

}
