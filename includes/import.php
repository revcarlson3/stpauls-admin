<?php

function spa_get_complete_backup_table_definitions() {
    return array(
        'teams' => array(
            'suffix' => 'spa_teams',
            'columns' => array('id', 'name', 'description', 'active', 'created_at'),
            'formats' => array('%d', '%s', '%s', '%d', '%s'),
            'order_by' => 'id',
        ),
        'volunteers' => array(
            'suffix' => 'spa_volunteers',
            'columns' => array('id', 'first_name', 'last_name', 'email', 'phone', 'email_enabled', 'phone_enabled', 'active', 'created_at'),
            'formats' => array('%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s'),
            'order_by' => 'id',
        ),
        'service_types' => array(
            'suffix' => 'spa_service_types',
            'columns' => array('id', 'name', 'description', 'active', 'created_at'),
            'formats' => array('%d', '%s', '%s', '%d', '%s'),
            'order_by' => 'id',
        ),
        'notification_templates' => array(
            'suffix' => 'spa_notification_templates',
            'columns' => array('id', 'name', 'type', 'subject', 'body', 'created_at', 'updated_at'),
            'formats' => array('%d', '%s', '%s', '%s', '%s', '%s', '%s'),
            'order_by' => 'id',
        ),
        'delivery_logs' => array(
            'suffix' => 'spa_notification_delivery_logs',
            'columns' => array('id', 'event_id', 'volunteer_id', 'volunteer_name', 'channel', 'provider', 'status', 'provider_message_id', 'failure_reason', 'failed_at', 'created_at', 'updated_at'),
            'formats' => array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
            'order_by' => 'id',
        ),
        'events' => array(
            'suffix' => 'spa_events',
            'columns' => array('id', 'name', 'season', 'special_day', 'event_date', 'start_time', 'end_time', 'description', 'location', 'service_builder_url', 'service_type_id', 'is_recurring', 'recurrence_type', 'recurrence_end_date', 'parent_event_id', 'notify_volunteers', 'active', 'created_at'),
            'formats' => array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%d', '%s'),
            'order_by' => 'id',
        ),
        'volunteer_teams' => array(
            'suffix' => 'spa_volunteer_teams',
            'columns' => array('volunteer_id', 'team_id'),
            'formats' => array('%d', '%d'),
            'order_by' => 'volunteer_id, team_id',
        ),
        'events_teams' => array(
            'suffix' => 'spa_events_teams',
            'columns' => array('id', 'event_id', 'team_id', 'volunteers_needed'),
            'formats' => array('%d', '%d', '%d', '%d'),
            'order_by' => 'id',
        ),
        'event_volunteers' => array(
            'suffix' => 'spa_event_volunteers',
            'columns' => array('event_id', 'team_id', 'volunteer_id', 'is_override'),
            'formats' => array('%d', '%d', '%d', '%d'),
            'order_by' => 'event_id, team_id, volunteer_id',
        ),
        'team_rotations' => array(
            'suffix' => 'spa_team_rotations',
            'columns' => array('id', 'service_type_id', 'team_id', 'volunteer_id', 'rotation_order', 'is_next', 'advance_rule', 'created_at'),
            'formats' => array('%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s'),
            'order_by' => 'id',
        ),
        'preachers' => array(
            'suffix' => 'spa_preachers',
            'columns' => array('id', 'name', 'bio', 'active', 'created_at', 'updated_at'),
            'formats' => array('%d', '%s', '%s', '%d', '%s', '%s'),
            'order_by' => 'id',
        ),
        'sermon_series' => array(
            'suffix' => 'spa_sermon_series',
            'columns' => array('id', 'name', 'description', 'active', 'created_at', 'updated_at'),
            'formats' => array('%d', '%s', '%s', '%d', '%s', '%s'),
            'order_by' => 'id',
        ),
        'services' => array(
            'suffix' => 'spa_services',
            'columns' => array('id', 'event_id', 'sermon_title', 'sermon_text', 'sermon_file_id', 'sermon_file_url', 'bible_translation', 'video_url', 'audio_file_id', 'audio_file_url', 'bulletin_file_id', 'bulletin_file_url', 'preacher_id', 'series_id', 'featured_image_id', 'active', 'created_by', 'created_at', 'updated_at'),
            'formats' => array('%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s'),
            'order_by' => 'id',
        ),
        'service_lessons' => array(
            'suffix' => 'spa_service_lessons',
            'columns' => array('id', 'service_id', 'reference', 'link_url', 'lesson_order', 'created_at'),
            'formats' => array('%d', '%d', '%s', '%s', '%d', '%s'),
            'order_by' => 'id',
        ),
        'service_tags' => array(
            'suffix' => 'spa_service_tags',
            'columns' => array('id', 'name', 'slug', 'created_at'),
            'formats' => array('%d', '%s', '%s', '%s'),
            'order_by' => 'id',
        ),
        'service_tag_relationships' => array(
            'suffix' => 'spa_service_tag_relationships',
            'columns' => array('service_id', 'tag_id'),
            'formats' => array('%d', '%d'),
            'order_by' => 'service_id, tag_id',
        ),
    );
}

function spa_complete_backup_json_supported() {
    return function_exists('json_encode')
        && function_exists('json_decode')
        && function_exists('json_last_error')
        && defined('JSON_PRETTY_PRINT')
        && defined('JSON_UNESCAPED_SLASHES')
        && defined('JSON_ERROR_NONE');
}

function spa_begin_complete_backup_export_transaction() {
    global $wpdb;

    $lock_key = 'spa_rotation_apply_undo_lock';
    add_option($lock_key, 1, '', false);

    if ( $wpdb->query('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ') === false ) {
        return false;
    }
    if ( $wpdb->query('START TRANSACTION WITH CONSISTENT SNAPSHOT') === false ) {
        return false;
    }

    $lock_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT option_id
             FROM {$wpdb->options}
             WHERE option_name = %s
             FOR UPDATE",
            $lock_key
        )
    );
    if ( ! $lock_id ) {
        $wpdb->query('ROLLBACK');
        return false;
    }

    return true;
}

function spa_begin_complete_backup_restore_transaction() {
    global $wpdb;

    $lock_key = 'spa_rotation_apply_undo_lock';
    add_option($lock_key, 1, '', false);

    if ( $wpdb->query('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE') === false ) {
        return false;
    }
    if ( $wpdb->query('START TRANSACTION') === false ) {
        return false;
    }

    $lock_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT option_id
             FROM {$wpdb->options}
             WHERE option_name = %s
             FOR UPDATE",
            $lock_key
        )
    );
    if ( ! $lock_id ) {
        $wpdb->query('ROLLBACK');
        return false;
    }

    return true;
}

function spa_get_complete_backup_payload() {
    global $wpdb;

    $payload = array(
        'tables' => array(),
        'options' => array(),
        'user_meta' => array(),
    );

    foreach ( spa_get_complete_backup_table_definitions() as $key => $definition ) {
        $table = $wpdb->prefix . $definition['suffix'];
        $columns = implode(', ', array_map(function($column) {
            return '`' . $column . '`';
        }, $definition['columns']));
        $wpdb->last_error = '';
        $rows = $wpdb->get_results(
            "SELECT {$columns} FROM {$table} ORDER BY {$definition['order_by']}",
            ARRAY_A
        );
        if ( $wpdb->last_error !== '' ) {
            return new WP_Error('backup_read_failed', sprintf('Unable to read the %s table.', $key));
        }
        $payload['tables'][$key] = $rows;
    }

    $option_like = $wpdb->esc_like('spa_') . '%';
    $wpdb->last_error = '';
    $option_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT option_name, option_value, autoload
             FROM {$wpdb->options}
             WHERE option_name LIKE %s
             AND option_name <> %s
             ORDER BY option_name",
            $option_like,
            'spa_rotation_apply_undo_lock'
        ),
        ARRAY_A
    );
    if ( $wpdb->last_error !== '' ) {
        return new WP_Error('backup_read_failed', 'Unable to read plugin settings.');
    }
    $payload['options'] = $option_rows;
    $user_meta_like = $wpdb->esc_like('spa_') . '%';
    $wpdb->last_error = '';
    $user_meta_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_id, meta_key, meta_value
             FROM {$wpdb->usermeta}
             WHERE meta_key LIKE %s
             ORDER BY user_id, meta_key",
            $user_meta_like
        ),
        ARRAY_A
    );
    if ( $wpdb->last_error !== '' ) {
        return new WP_Error('backup_read_failed', 'Unable to read plugin user preferences.');
    }
    $payload['user_meta'] = $user_meta_rows;

    return $payload;
}

function spa_get_complete_backup_checksum($payload) {
    return hash('sha256', wp_json_encode($payload, JSON_UNESCAPED_SLASHES));
}

function spa_handle_export_complete_backup() {
    global $wpdb;

    if ( ! current_user_can('manage_options') ) {
        wp_die('Unauthorized', 'Error', array('response' => 403));
    }
    check_admin_referer('spa_export_complete_backup');
    if ( ! spa_complete_backup_json_supported() ) {
        wp_die('JSON support is required to create a complete backup.', 'Backup Error', array('response' => 500));
    }
    if ( ! spa_complete_backup_tables_support_transactions() ) {
        wp_die('Complete backup was blocked because one or more database tables cannot guarantee a consistent snapshot.', 'Backup Error', array('response' => 500));
    }
    if ( ! spa_begin_complete_backup_export_transaction() ) {
        wp_die('Unable to begin a consistent backup transaction.', 'Backup Error', array('response' => 500));
    }

    $payload = spa_get_complete_backup_payload();
    if ( is_wp_error($payload) ) {
        $wpdb->query('ROLLBACK');
        wp_die(esc_html($payload->get_error_message()), 'Backup Error', array('response' => 500));
    }
    if ( $wpdb->query('COMMIT') === false ) {
        $wpdb->query('ROLLBACK');
        wp_die('Unable to complete the consistent backup transaction.', 'Backup Error', array('response' => 500));
    }
    $backup = array(
        'format' => 'stpauls-admin-complete-backup',
        'format_version' => 7,
        'plugin_version' => defined('SPA_VERSION') ? SPA_VERSION : '',
        'exported_at' => gmdate('c'),
        'site_url' => home_url(),
        'payload' => $payload,
        'checksum' => spa_get_complete_backup_checksum($payload),
    );
    $json = wp_json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ( $json === false ) {
        wp_die('Unable to encode the complete backup.', 'Backup Error', array('response' => 500));
    }

    while ( ob_get_level() ) {
        ob_end_clean();
    }
    nocache_headers();
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="stpauls-admin-complete-backup-' . gmdate('Y-m-d-His') . '.json"');
    header('X-Content-Type-Options: nosniff');
    echo $json;
    exit;
}
add_action('admin_post_spa_export_complete_backup', 'spa_handle_export_complete_backup');

function spa_complete_backup_collect_ids($rows, $table_key) {
    $ids = array();
    foreach ( $rows as $index => $row ) {
        $id = intval($row['id']);
        if ( $id < 1 || isset($ids[$id]) ) {
            return new WP_Error('invalid_backup_id', sprintf('Invalid or duplicate ID in %s row %d.', $table_key, $index + 1));
        }
        $ids[$id] = true;
    }
    return $ids;
}

function spa_complete_backup_validate_composite_keys($rows, $table_key, $columns) {
    $seen = array();
    foreach ( $rows as $index => $row ) {
        $parts = array();
        foreach ( $columns as $column ) {
            $parts[] = (string) $row[$column];
        }
        $key = implode(':', $parts);
        if ( isset($seen[$key]) ) {
            return new WP_Error('duplicate_backup_relation', sprintf('Duplicate relationship in %s row %d.', $table_key, $index + 1));
        }
        $seen[$key] = true;
    }
    return true;
}

function spa_upgrade_complete_backup($backup) {
    if ( ! is_array($backup) || ! in_array(intval($backup['format_version'] ?? 0), array(1, 2, 3, 4, 5, 6), true) ) {
        return $backup;
    }
    if (
        ! isset($backup['payload'], $backup['checksum'])
        || ! is_array($backup['payload'])
        || ! is_string($backup['checksum'])
        || ! hash_equals($backup['checksum'], spa_get_complete_backup_checksum($backup['payload']))
    ) {
        return new WP_Error('invalid_backup_checksum', 'The backup integrity check failed. The file may be incomplete or altered.');
    }

    if ( intval($backup['format_version']) === 1 ) {
        $old_event_columns = array('id', 'name', 'event_date', 'start_time', 'end_time', 'description', 'location', 'service_type_id', 'is_recurring', 'recurrence_type', 'recurrence_end_date', 'parent_event_id', 'notify_volunteers', 'active', 'created_at');
        $event_rows = $backup['payload']['tables']['events'] ?? null;
        if ( ! is_array($event_rows) ) {
            return new WP_Error('invalid_backup_table', 'The events table data is invalid.');
        }

        foreach ( $event_rows as $index => $row ) {
            if ( ! is_array($row) || array_keys($row) !== $old_event_columns ) {
                return new WP_Error('invalid_backup_columns', sprintf('Unexpected columns in events row %d.', $index + 1));
            }
            $upgraded_row = array();
            foreach ( $row as $column => $value ) {
                $upgraded_row[$column] = $value;
                if ( $column === 'location' ) {
                    $upgraded_row['service_builder_url'] = null;
                }
            }
            $backup['payload']['tables']['events'][$index] = $upgraded_row;
        }
        $backup['format_version'] = 2;
    }

    $upgraded_tables = array();
    foreach ( $backup['payload']['tables'] as $table_key => $rows ) {
        $upgraded_tables[$table_key] = $rows;
        if ( $table_key === 'notification_templates' && ! isset($upgraded_tables['delivery_logs']) ) {
            $upgraded_tables['delivery_logs'] = array();
        }
    }
    $backup['payload']['tables'] = $upgraded_tables;

    if ( intval($backup['format_version']) < 4 ) {
        foreach ( $backup['payload']['tables']['events'] as $index => $row ) {
            if ( ! array_key_exists('season', $row) ) {
                $upgraded_row = array();
                foreach ( $row as $column => $value ) {
                    $upgraded_row[$column] = $value;
                    if ( $column === 'name' ) {
                        $upgraded_row['season'] = null;
                    }
                }
                $backup['payload']['tables']['events'][$index] = $upgraded_row;
            }
        }
        $backup['format_version'] = 4;
    }
    if ( intval($backup['format_version']) < 5 ) {
        foreach ( $backup['payload']['tables']['events'] as $index => $row ) {
            if ( ! array_key_exists('special_day', $row) ) {
                $upgraded_row = array();
                foreach ( $row as $column => $value ) {
                    $upgraded_row[$column] = $value;
                    if ( $column === 'season' ) {
                        $upgraded_row['special_day'] = null;
                    }
                }
                $backup['payload']['tables']['events'][$index] = $upgraded_row;
            }
        }
        $backup['format_version'] = 5;
    }
    if ( intval($backup['format_version']) < 6 ) {
        $new_table_keys = array('preachers', 'sermon_series', 'services', 'service_lessons', 'service_tags', 'service_tag_relationships');
        foreach ( $new_table_keys as $table_key ) {
            if ( ! isset($backup['payload']['tables'][$table_key]) ) {
                $backup['payload']['tables'][$table_key] = array();
            }
        }
        $ordered_tables = array();
        foreach ( spa_get_complete_backup_table_definitions() as $table_key => $definition ) {
            $ordered_tables[$table_key] = isset($backup['payload']['tables'][$table_key])
                ? $backup['payload']['tables'][$table_key]
                : array();
        }
        $backup['payload']['tables'] = $ordered_tables;
        $backup['format_version'] = 6;
    }
    if ( intval($backup['format_version']) < 7 ) {
        foreach ( $backup['payload']['tables']['services'] as $index => $row ) {
            $upgraded_row = array();
            foreach ( $row as $column => $value ) {
                if ( $column === 'sermon_text' ) {
                    $upgraded_row['sermon_title'] = '';
                }
                $upgraded_row[$column] = $value;
            }
            $backup['payload']['tables']['services'][$index] = $upgraded_row;
        }
        $backup['format_version'] = 7;
    }
    $backup['checksum'] = spa_get_complete_backup_checksum($backup['payload']);
    return $backup;
}

function spa_validate_complete_backup(&$backup, $discard_orphaned_relationships = false) {
    global $wpdb;

    $definitions = spa_get_complete_backup_table_definitions();

    if (
        ! is_array($backup)
        || ! isset($backup['format'], $backup['format_version'], $backup['payload'], $backup['checksum'])
        || $backup['format'] !== 'stpauls-admin-complete-backup'
        || intval($backup['format_version']) !== 7
        || ! is_array($backup['payload'])
        || ! isset($backup['payload']['tables'], $backup['payload']['options'], $backup['payload']['user_meta'])
        || ! is_array($backup['payload']['tables'])
        || ! is_array($backup['payload']['options'])
        || ! is_array($backup['payload']['user_meta'])
    ) {
        return new WP_Error('invalid_backup_format', 'This is not a supported St. Paul\'s Admin complete backup.');
    }
    if (
        ! is_string($backup['checksum'])
        || ! preg_match('/^[a-f0-9]{64}$/', $backup['checksum'])
        || ! hash_equals($backup['checksum'], spa_get_complete_backup_checksum($backup['payload']))
    ) {
        return new WP_Error('invalid_backup_checksum', 'The backup integrity check failed. The file may be incomplete or altered.');
    }
    if ( array_keys($backup['payload']['tables']) !== array_keys($definitions) ) {
        return new WP_Error('incomplete_backup_tables', 'The backup does not contain the complete required table set.');
    }

    foreach ( $definitions as $table_key => $definition ) {
        $rows = $backup['payload']['tables'][$table_key];
        if ( ! is_array($rows) ) {
            return new WP_Error('invalid_backup_table', sprintf('The %s table data is invalid.', $table_key));
        }
        foreach ( $rows as $index => $row ) {
            if ( ! is_array($row) || array_keys($row) !== $definition['columns'] ) {
                return new WP_Error('invalid_backup_columns', sprintf('Unexpected columns in %s row %d.', $table_key, $index + 1));
            }
            foreach ( $definition['columns'] as $column_index => $column ) {
                $value = $row[$column];
                if ( ! is_scalar($value) && $value !== null ) {
                    return new WP_Error('invalid_backup_value', sprintf('Invalid value in %s row %d.', $table_key, $index + 1));
                }
                if (
                    $value !== null
                    && $definition['formats'][$column_index] === '%d'
                    && ! preg_match('/^-?\d+$/', (string) $value)
                ) {
                    return new WP_Error('invalid_backup_number', sprintf('Invalid numeric value in %s row %d.', $table_key, $index + 1));
                }
            }
        }
    }

    foreach ( $backup['payload']['options'] as $option_row ) {
        if (
            ! is_array($option_row)
            || array_keys($option_row) !== array('option_name', 'option_value', 'autoload')
            || ! is_string($option_row['option_name'])
            || strpos($option_row['option_name'], 'spa_') !== 0
            || $option_row['option_name'] === 'spa_rotation_apply_undo_lock'
            || ! is_string($option_row['option_value'])
            || ! is_string($option_row['autoload'])
        ) {
            return new WP_Error('invalid_backup_option', 'The backup contains an invalid plugin option.');
        }
    }
    foreach ( $backup['payload']['user_meta'] as $meta_row ) {
        if (
            ! is_array($meta_row)
            || array_keys($meta_row) !== array('user_id', 'meta_key', 'meta_value')
            || ! is_scalar($meta_row['user_id'])
            || ! preg_match('/^[1-9]\d*$/', (string) $meta_row['user_id'])
            || ! is_string($meta_row['meta_key'])
            || strpos($meta_row['meta_key'], 'spa_') !== 0
            || ! is_string($meta_row['meta_value'])
        ) {
            return new WP_Error('invalid_backup_user_meta', 'The backup contains invalid plugin user preferences.');
        }
        if (
            ! $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->users} WHERE ID = %d",
                    intval($meta_row['user_id'])
                )
            )
        ) {
            return new WP_Error('missing_backup_user', 'The backup references a WordPress user that does not exist on this site.');
        }
    }

    $tables = $backup['payload']['tables'];
    $team_ids = spa_complete_backup_collect_ids($tables['teams'], 'teams');
    $volunteer_ids = spa_complete_backup_collect_ids($tables['volunteers'], 'volunteers');
    $service_type_ids = spa_complete_backup_collect_ids($tables['service_types'], 'service_types');
    $event_ids = spa_complete_backup_collect_ids($tables['events'], 'events');
    $preacher_ids = spa_complete_backup_collect_ids($tables['preachers'], 'preachers');
    $series_ids = spa_complete_backup_collect_ids($tables['sermon_series'], 'sermon_series');
    $service_ids = spa_complete_backup_collect_ids($tables['services'], 'services');
    $tag_ids = spa_complete_backup_collect_ids($tables['service_tags'], 'service_tags');
    foreach ( array($team_ids, $volunteer_ids, $service_type_ids, $event_ids, $preacher_ids, $series_ids, $service_ids, $tag_ids) as $id_result ) {
        if ( is_wp_error($id_result) ) {
            return $id_result;
        }
    }
    foreach ( array('notification_templates', 'delivery_logs', 'events_teams', 'team_rotations') as $table_key ) {
        $id_result = spa_complete_backup_collect_ids($tables[$table_key], $table_key);
        if ( is_wp_error($id_result) ) {
            return $id_result;
        }
    }

    foreach ( $tables['events'] as $row ) {
        if ( $row['service_type_id'] !== null && intval($row['service_type_id']) > 0 && ! isset($service_type_ids[intval($row['service_type_id'])]) ) {
            return new WP_Error('broken_backup_reference', 'An event references a missing service type.');
        }
        if ( $row['parent_event_id'] !== null && intval($row['parent_event_id']) > 0 && ! isset($event_ids[intval($row['parent_event_id'])]) ) {
            return new WP_Error('broken_backup_reference', 'A recurring event references a missing parent event.');
        }
    }
    foreach ( $tables['services'] as $row ) {
        if ( ! isset($event_ids[intval($row['event_id'])]) ) {
            return new WP_Error('broken_backup_reference', 'A service references a missing event.');
        }
        if ( $row['preacher_id'] !== null && intval($row['preacher_id']) > 0 && ! isset($preacher_ids[intval($row['preacher_id'])]) ) {
            return new WP_Error('broken_backup_reference', 'A service references a missing preacher.');
        }
        if ( $row['series_id'] !== null && intval($row['series_id']) > 0 && ! isset($series_ids[intval($row['series_id'])]) ) {
            return new WP_Error('broken_backup_reference', 'A service references a missing sermon series.');
        }
    }
    foreach ( $tables['service_lessons'] as $row ) {
        if ( ! isset($service_ids[intval($row['service_id'])]) ) {
            return new WP_Error('broken_backup_reference', 'A lesson references a missing service.');
        }
    }
    foreach ( $tables['service_tag_relationships'] as $row ) {
        if ( ! isset($service_ids[intval($row['service_id'])], $tag_ids[intval($row['tag_id'])]) ) {
            return new WP_Error('broken_backup_reference', 'A service tag relationship references a missing record.');
        }
    }
    $discarded_relationships = array(
        'volunteer_teams' => 0,
        'events_teams' => 0,
        'event_volunteers' => 0,
        'team_rotations' => 0,
    );

    $valid_rows = array();
    foreach ( $tables['volunteer_teams'] as $row ) {
        if ( ! isset($volunteer_ids[intval($row['volunteer_id'])], $team_ids[intval($row['team_id'])]) ) {
            if ( $discard_orphaned_relationships ) {
                $discarded_relationships['volunteer_teams']++;
                continue;
            }
            return new WP_Error('broken_backup_reference', 'A volunteer-team relationship references a missing record.');
        }
        $valid_rows[] = $row;
    }
    $tables['volunteer_teams'] = $valid_rows;

    $valid_rows = array();
    foreach ( $tables['events_teams'] as $row ) {
        if ( ! isset($event_ids[intval($row['event_id'])], $team_ids[intval($row['team_id'])]) ) {
            if ( $discard_orphaned_relationships ) {
                $discarded_relationships['events_teams']++;
                continue;
            }
            return new WP_Error('broken_backup_reference', 'An event-team relationship references a missing record.');
        }
        $valid_rows[] = $row;
    }
    $tables['events_teams'] = $valid_rows;

    $valid_rows = array();
    foreach ( $tables['event_volunteers'] as $row ) {
        if (
            ! isset($event_ids[intval($row['event_id'])])
            || ! isset($team_ids[intval($row['team_id'])])
            || ! isset($volunteer_ids[intval($row['volunteer_id'])])
        ) {
            if ( $discard_orphaned_relationships ) {
                $discarded_relationships['event_volunteers']++;
                continue;
            }
            return new WP_Error('broken_backup_reference', 'An event assignment references a missing record.');
        }
        $valid_rows[] = $row;
    }
    $tables['event_volunteers'] = $valid_rows;

    $valid_rows = array();
    foreach ( $tables['team_rotations'] as $row ) {
        if (
            ! isset($service_type_ids[intval($row['service_type_id'])])
            || ! isset($team_ids[intval($row['team_id'])])
            || ! isset($volunteer_ids[intval($row['volunteer_id'])])
        ) {
            if ( $discard_orphaned_relationships ) {
                $discarded_relationships['team_rotations']++;
                continue;
            }
            return new WP_Error('broken_backup_reference', 'A team rotation references a missing record.');
        }
        $valid_rows[] = $row;
    }
    $tables['team_rotations'] = $valid_rows;

    $composite_checks = array(
        array('volunteer_teams', array('volunteer_id', 'team_id')),
        array('event_volunteers', array('event_id', 'team_id', 'volunteer_id')),
        array('services', array('event_id')),
        array('service_tag_relationships', array('service_id', 'tag_id')),
    );
    foreach ( $composite_checks as $check ) {
        $result = spa_complete_backup_validate_composite_keys($tables[$check[0]], $check[0], $check[1]);
        if ( is_wp_error($result) ) {
            return $result;
        }
    }

    if ( $discard_orphaned_relationships ) {
        $backup['payload']['tables'] = $tables;
        $backup['checksum'] = spa_get_complete_backup_checksum($backup['payload']);
    }

    return $discarded_relationships;
}

function spa_complete_backup_tables_support_transactions() {
    global $wpdb;

    $tables = array($wpdb->options, $wpdb->usermeta);
    foreach ( spa_get_complete_backup_table_definitions() as $definition ) {
        $tables[] = $wpdb->prefix . $definition['suffix'];
    }
    foreach ( $tables as $table ) {
        $status = $wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS WHERE Name = %s', $table));
        if ( ! $status || strtoupper((string) $status->Engine) !== 'INNODB' ) {
            return false;
        }
    }
    return true;
}

function spa_redirect_complete_backup_error($message) {
    set_transient('spa_complete_backup_restore_error', sanitize_text_field($message), HOUR_IN_SECONDS);
    wp_safe_redirect(admin_url('admin.php?page=spa-settings&tab=import&backup_error=1'));
    exit;
}

function spa_handle_import_complete_backup() {
    global $wpdb;

    if ( ! current_user_can('manage_options') ) {
        wp_die('Unauthorized', 'Error', array('response' => 403));
    }
    if ( ! isset($_POST['spa_complete_backup_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['spa_complete_backup_nonce']), 'spa_import_complete_backup') ) {
        wp_die('Invalid nonce', 'Error', array('response' => 403));
    }
    if ( ! spa_complete_backup_json_supported() ) {
        spa_redirect_complete_backup_error('JSON support is required to restore a complete backup.');
    }
    if ( empty($_POST['spa_confirm_complete_restore']) ) {
        spa_redirect_complete_backup_error('Confirm that the restore may replace all current plugin data.');
    }

    $file = isset($_FILES['spa_complete_backup_file']) ? $_FILES['spa_complete_backup_file'] : null;
    if ( ! $file || $file['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($file['tmp_name']) ) {
        spa_redirect_complete_backup_error('The backup file upload failed.');
    }
    if ( intval($file['size']) < 1 || intval($file['size']) > 25 * 1024 * 1024 ) {
        spa_redirect_complete_backup_error('Complete backup files must be between 1 byte and 25 MB.');
    }
    if ( strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION)) !== 'json' ) {
        spa_redirect_complete_backup_error('Select a St. Paul\'s Admin JSON backup file.');
    }

    $json = file_get_contents($file['tmp_name']);
    if ( $json === false ) {
        spa_redirect_complete_backup_error('The uploaded backup could not be read.');
    }
    $backup = json_decode($json, true);
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        spa_redirect_complete_backup_error('The uploaded backup is not valid JSON.');
    }

    $backup = spa_upgrade_complete_backup($backup);
    if ( is_wp_error($backup) ) {
        spa_redirect_complete_backup_error($backup->get_error_message());
    }
    $validation = spa_validate_complete_backup($backup, true);
    if ( is_wp_error($validation) ) {
        spa_redirect_complete_backup_error($validation->get_error_message());
    }
    if ( ! spa_complete_backup_tables_support_transactions() ) {
        spa_redirect_complete_backup_error('Restore was blocked because one or more database tables cannot guarantee transaction rollback.');
    }
    if ( ! spa_begin_complete_backup_restore_transaction() ) {
        spa_redirect_complete_backup_error('Unable to begin a protected restore transaction.');
    }

    $definitions = spa_get_complete_backup_table_definitions();
    $delete_order = array(
        'service_tag_relationships',
        'service_lessons',
        'services',
        'service_tags',
        'sermon_series',
        'preachers',
        'event_volunteers',
        'events_teams',
        'team_rotations',
        'volunteer_teams',
        'delivery_logs',
        'events',
        'notification_templates',
        'service_types',
        'volunteers',
        'teams',
    );
    foreach ( $delete_order as $table_key ) {
        $table = $wpdb->prefix . $definitions[$table_key]['suffix'];
        if ( $wpdb->query("DELETE FROM {$table}") === false ) {
            $wpdb->query('ROLLBACK');
            spa_redirect_complete_backup_error('The restore could not clear the existing plugin tables.');
        }
    }

    foreach ( $definitions as $table_key => $definition ) {
        $table = $wpdb->prefix . $definition['suffix'];
        foreach ( $backup['payload']['tables'][$table_key] as $row ) {
            if ( $wpdb->insert($table, $row, $definition['formats']) !== 1 ) {
                $wpdb->query('ROLLBACK');
                spa_redirect_complete_backup_error(sprintf('The restore failed while writing %s data. No changes were kept.', $table_key));
            }
        }
    }

    $option_like = $wpdb->esc_like('spa_') . '%';
    $old_option_names = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT option_name
             FROM {$wpdb->options}
             WHERE option_name LIKE %s
             AND option_name <> %s",
            $option_like,
            'spa_rotation_apply_undo_lock'
        )
    );
    $deleted_options = $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE %s
             AND option_name <> %s",
            $option_like,
            'spa_rotation_apply_undo_lock'
        )
    );
    if ( $deleted_options === false ) {
        $wpdb->query('ROLLBACK');
        spa_redirect_complete_backup_error('The restore could not replace the plugin settings.');
    }
    $restored_option_names = array();
    foreach ( $backup['payload']['options'] as $option_row ) {
        $inserted = $wpdb->insert(
            $wpdb->options,
            array(
                'option_name' => $option_row['option_name'],
                'option_value' => $option_row['option_value'],
                'autoload' => $option_row['autoload'],
            ),
            array('%s', '%s', '%s')
        );
        if ( $inserted !== 1 ) {
            $wpdb->query('ROLLBACK');
            spa_redirect_complete_backup_error('The restore failed while writing plugin settings. No changes were kept.');
        }
        $restored_option_names[] = $option_row['option_name'];
    }

    $user_meta_like = $wpdb->esc_like('spa_') . '%';
    $old_user_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT DISTINCT user_id
             FROM {$wpdb->usermeta}
             WHERE meta_key LIKE %s",
            $user_meta_like
        )
    );
    if (
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->usermeta}
                 WHERE meta_key LIKE %s",
                $user_meta_like
            )
        ) === false
    ) {
        $wpdb->query('ROLLBACK');
        spa_redirect_complete_backup_error('The restore could not replace plugin user preferences.');
    }
    $restored_user_ids = array();
    foreach ( $backup['payload']['user_meta'] as $meta_row ) {
        if (
            $wpdb->insert(
                $wpdb->usermeta,
                array(
                    'user_id' => intval($meta_row['user_id']),
                    'meta_key' => $meta_row['meta_key'],
                    'meta_value' => $meta_row['meta_value'],
                ),
                array('%d', '%s', '%s')
            ) !== 1
        ) {
            $wpdb->query('ROLLBACK');
            spa_redirect_complete_backup_error('The restore failed while writing plugin user preferences. No changes were kept.');
        }
        $restored_user_ids[] = intval($meta_row['user_id']);
    }

    if ( $wpdb->query('COMMIT') === false ) {
        $wpdb->query('ROLLBACK');
        spa_redirect_complete_backup_error('The database could not commit the restore. No changes were kept.');
    }

    spa_clear_rotation_option_caches(array_merge($old_option_names, $restored_option_names));
    foreach ( array_unique(array_merge($old_user_ids, $restored_user_ids)) as $user_id ) {
        clean_user_cache(intval($user_id));
    }
    set_transient(
        'spa_complete_backup_restore_result',
        array(
            'tables' => count($definitions),
            'records' => array_sum(array_map('count', $backup['payload']['tables'])),
            'options' => count($backup['payload']['options']),
            'user_meta' => count($backup['payload']['user_meta']),
            'discarded_relationships' => array_sum($validation),
        ),
        HOUR_IN_SECONDS
    );
    wp_safe_redirect(admin_url('admin.php?page=spa-settings&tab=import&backup_restored=1'));
    exit;
}
add_action('admin_post_spa_import_complete_backup', 'spa_handle_import_complete_backup');

function spa_handle_import_volunteers() {
    if ( ! current_user_can('manage_options') ) {
        wp_die('Unauthorized');
    }

    if ( ! isset($_POST['spa_import_nonce']) || ! wp_verify_nonce($_POST['spa_import_nonce'], 'spa_import_volunteers') ) {
        wp_die('Nonce verification failed');
    }

    if ( ! isset($_FILES['spa_import_file']) ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=1'));
        exit;
    }

    $file = $_FILES['spa_import_file'];

    // Check for upload errors
    if ( $file['error'] !== UPLOAD_ERR_OK ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=1'));
        exit;
    }

    // Check file size (max 5MB)
    if ( $file['size'] > 5 * 1024 * 1024 ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=4'));
        exit;
    }

    $file_name = basename($file['name']);
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if ( ! in_array($file_ext, ['csv', 'xlsx']) ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=2'));
        exit;
    }

    $rows = array();
    if ( $file_ext === 'csv' ) {
        $rows = spa_parse_csv($file['tmp_name']);
    } elseif ( $file_ext === 'xlsx' ) {
        $rows = spa_parse_xlsx($file['tmp_name']);
    }

    if ( ! $rows || count($rows) === 0 ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=3'));
        exit;
    }

    $result = spa_import_volunteers_data($rows);
    $result['type'] = 'volunteers';

    // Store detailed results in transient for display
    set_transient('spa_import_results', $result, HOUR_IN_SECONDS);

    wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_success=1'));
    exit;
}
add_action('admin_post_spa_import_volunteers', 'spa_handle_import_volunteers');

function spa_parse_csv($file_path) {
    $rows = array();
    $headers = array();
    $row_num = 0;

    if ( ! file_exists($file_path) ) {
        return $rows;
    }

    if ( ($handle = fopen($file_path, 'r')) !== FALSE ) {
        // Handle Mac/Windows line endings
        stream_filter_append($handle, 'convert.iconv.UTF-8/UTF-8');
        while ( ($data = fgetcsv($handle, 0, ',')) !== FALSE ) {
            $row_num++;

            if ( $row_num === 1 ) {
                $headers = array_map(function($h) {
                    // Strip BOM, carriage returns, and all non-printable chars
                    $h = ltrim($h, "\xEF\xBB\xBF");
                    $h = trim($h, "\r\n\t ");
                    return preg_replace('/[^\x20-\x7E]/', '', $h);
                }, $data);
                continue;
            }

            // Strip carriage returns from all values
            $data = array_map(function($v) {
                return trim($v, "\r\n");
            }, $data);

            // Pad data to match header count to avoid array_combine failure
            $header_count = count($headers);
            while ( count($data) < $header_count ) {
                $data[] = '';
            }
            $data = array_slice($data, 0, $header_count);

            $row = array_combine($headers, $data);
            if ( $row !== false ) {
                $rows[] = $row;
            }
        }
        fclose($handle);
    }

    return $rows;
}

function spa_parse_xlsx($file_path) {
    $rows = array();

    if ( ! file_exists($file_path) ) {
        return $rows;
    }

    // Check if PhpSpreadsheet is available
    if ( ! class_exists('\PhpOffice\PhpSpreadsheet\IOFactory') ) {
        return $rows;
    }

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();
        $headers = array();
        $row_num = 0;

        foreach ( $worksheet->getRowIterator() as $row ) {
            $row_num++;
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            $row_data = array();
            foreach ( $cellIterator as $cell ) {
                $row_data[] = trim($cell->getValue());
            }

            if ( $row_num === 1 ) {
                $headers = $row_data;
                continue;
            }

            $row = array_combine($headers, $row_data);
            if ( $row !== false ) {
                $rows[] = $row;
            }
        }
    } catch ( Exception $e ) {
        return array();
    }

    return $rows;
}

function spa_import_volunteers_data($rows) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'spa_volunteers';

    $imported = 0;
    $skipped_list = array();
    $errors_list = array();

    foreach ( $rows as $row_index => $row ) {
        $first_name = isset($row['first_name']) ? sanitize_text_field(trim($row['first_name'])) : '';
        $last_name = isset($row['last_name']) ? sanitize_text_field(trim($row['last_name'])) : '';
        $email = isset($row['email']) ? sanitize_email(trim($row['email'])) : '';
        $phone = isset($row['phone']) ? sanitize_text_field($row['phone']) : '';

        // Validate required fields
        if ( empty($first_name) || empty($last_name) || empty($email) ) {
            $errors_list[] = array(
                'row' => $row_index + 2,
                'data' => $row,
                'reason' => 'Missing required fields (first_name, last_name, or email)'
            );
            continue;
        }

        // Validate email format
        if ( ! is_email($email) ) {
            $errors_list[] = array(
                'row' => $row_index + 2,
                'data' => $row,
                'reason' => "Invalid email format: $email"
            );
            continue;
        }

        // Check if email already exists (duplicate check)
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s",
            $email
        ));

        if ( $existing ) {
            $skipped_list[] = array(
                'row' => $row_index + 2,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'reason' => 'Email already exists in database'
            );
            continue;
        }

        // Validate phone format if provided
        if ( ! empty($phone) ) {
            if ( ! preg_match('/^\+[1-9]\d{1,14}$/', $phone) ) {
                // Try to normalize phone number using libphonenumber if available
                $normalized = spa_normalize_phone_for_import($phone);
                if ( ! $normalized ) {
                    $errors_list[] = array(
                        'row' => $row_index + 2,
                        'data' => $row,
                        'reason' => "Invalid phone format: $phone (must be E.164 format like +13209999999)"
                    );
                    continue;
                }
                $phone = $normalized;
            }
        }

        // Insert volunteer
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'email_enabled' => 1,
                'phone_enabled' => empty($phone) ? 0 : 1,
                'active' => 1,
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d', '%d')
        );

        if ( $inserted ) {
            $imported++;
        } else {
            $errors_list[] = array(
                'row' => $row_index + 2,
                'data' => $row,
                'reason' => 'Database insert failed'
            );
        }
    }

    return array(
        'imported' => $imported,
        'skipped' => count($skipped_list),
        'errors' => count($errors_list),
        'skipped_list' => $skipped_list,
        'errors_list' => $errors_list,
    );
}

function spa_normalize_phone_for_import($phone) {
    // Simple normalization: remove common formatting characters
    $normalized = preg_replace('/[\s\-\(\)\.]/', '', $phone);

    // Try to add country code if missing
    if ( ! preg_match('/^\+/', $normalized) ) {
        // Assume US number if 10 digits
        if ( preg_match('/^1?\d{10}$/', $normalized) ) {
            $normalized = preg_replace('/^1?/', '+1', $normalized);
        } else {
            return false;
        }
    }

    // Validate E.164 format
    if ( ! preg_match('/^\+[1-9]\d{1,14}$/', $normalized) ) {
        return false;
    }

    return $normalized;
}

// ── EXPORT HANDLERS ─────────────────────────────────────────────────────────

function spa_export_csv($filename, array $headers, array $rows) {
    if ( ! current_user_can('manage_options') ) wp_die('Unauthorized');

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
    fputcsv($out, $headers);
    foreach ( $rows as $row ) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

function spa_handle_export_volunteers() {
    global $wpdb;
    if ( ! check_admin_referer('spa_export_volunteers') ) wp_die('Nonce failed');

    $rows = $wpdb->get_results(
        "SELECT first_name, last_name, email, phone, email_enabled, phone_enabled, active
         FROM {$wpdb->prefix}spa_volunteers ORDER BY last_name, first_name",
        ARRAY_A
    );

    spa_export_csv('spa-volunteers-' . date('Y-m-d') . '.csv',
        array('first_name','last_name','email','phone','email_enabled','phone_enabled','active'),
        $rows
    );
}
add_action('admin_post_spa_export_volunteers', 'spa_handle_export_volunteers');

function spa_handle_export_teams() {
    global $wpdb;
    if ( ! check_admin_referer('spa_export_teams') ) wp_die('Nonce failed');

    $rows = $wpdb->get_results(
        "SELECT name, description, active FROM {$wpdb->prefix}spa_teams ORDER BY name",
        ARRAY_A
    );

    spa_export_csv('spa-teams-' . date('Y-m-d') . '.csv',
        array('name','description','active'),
        $rows
    );
}
add_action('admin_post_spa_export_teams', 'spa_handle_export_teams');

function spa_handle_export_events() {
    global $wpdb;
    if ( ! check_admin_referer('spa_export_events') ) wp_die('Nonce failed');

    $rows = $wpdb->get_results(
        "SELECT name, season, special_day, event_date, start_time, end_time, description, location, service_builder_url,
                is_recurring, recurrence_type, recurrence_end_date, active
         FROM {$wpdb->prefix}spa_events ORDER BY event_date",
        ARRAY_A
    );

    spa_export_csv('spa-events-' . date('Y-m-d') . '.csv',
        array('name','season','special_day','event_date','start_time','end_time','description','location','service_builder_url',
              'is_recurring','recurrence_type','recurrence_end_date','active'),
        $rows
    );
}
add_action('admin_post_spa_export_events', 'spa_handle_export_events');

// ── IMPORT: TEAMS ────────────────────────────────────────────────────────────

function spa_handle_import_teams() {
    if ( ! current_user_can('manage_options') ) wp_die('Unauthorized');
    if ( ! isset($_POST['spa_import_nonce']) || ! wp_verify_nonce($_POST['spa_import_nonce'], 'spa_import_teams') ) wp_die('Nonce failed');

    $file = $_FILES['spa_import_file'] ?? null;
    if ( ! $file || $file['error'] !== UPLOAD_ERR_OK ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=1&type=teams')); exit;
    }
    if ( $file['size'] > 5 * 1024 * 1024 ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=4&type=teams')); exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ( ! in_array($ext, array('csv','xlsx')) ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=2&type=teams')); exit;
    }

    $rows = $ext === 'csv' ? spa_parse_csv($file['tmp_name']) : spa_parse_xlsx($file['tmp_name']);
    if ( ! $rows ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=3&type=teams')); exit;
    }

    global $wpdb;
    $imported = $skipped = $errors = 0;
    $skipped_list = array();
    $errors_list  = array();
    $table = $wpdb->prefix . 'spa_teams';

    foreach ( $rows as $i => $row ) {
        $name = sanitize_text_field($row['name'] ?? '');
        $desc = sanitize_textarea_field($row['description'] ?? '');
        $active = isset($row['active']) ? intval($row['active']) : 1;

        if ( empty($name) ) {
            $errors_list[] = array('row' => $i + 2, 'reason' => 'Missing team name');
            $errors++;
            continue;
        }

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE name = %s", $name));
        if ( $existing ) {
            $skipped_list[] = array('row' => $i + 2, 'name' => $name, 'reason' => 'Team name already exists');
            $skipped++;
            continue;
        }

        $ok = $wpdb->insert($table, array('name' => $name, 'description' => $desc, 'active' => $active), array('%s','%s','%d'));
        if ( $ok ) { $imported++; } else { $errors_list[] = array('row' => $i + 2, 'reason' => 'Database insert failed'); $errors++; }
    }

    set_transient('spa_import_results', array(
        'type'         => 'teams',
        'imported'     => $imported,
        'skipped'      => $skipped,
        'errors'       => $errors,
        'skipped_list' => $skipped_list,
        'errors_list'  => $errors_list,
    ), HOUR_IN_SECONDS);

    wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_success=1'));
    exit;
}
add_action('admin_post_spa_import_teams', 'spa_handle_import_teams');

// ── IMPORT: EVENTS ───────────────────────────────────────────────────────────

function spa_handle_import_events() {
    if ( ! current_user_can('manage_options') ) wp_die('Unauthorized');
    if ( ! isset($_POST['spa_import_nonce']) || ! wp_verify_nonce($_POST['spa_import_nonce'], 'spa_import_events') ) wp_die('Nonce failed');

    $file = $_FILES['spa_import_file'] ?? null;
    if ( ! $file || $file['error'] !== UPLOAD_ERR_OK ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=1&type=events')); exit;
    }
    if ( $file['size'] > 5 * 1024 * 1024 ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=4&type=events')); exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ( ! in_array($ext, array('csv','xlsx')) ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=2&type=events')); exit;
    }

    $rows = $ext === 'csv' ? spa_parse_csv($file['tmp_name']) : spa_parse_xlsx($file['tmp_name']);
    if ( ! $rows ) {
        wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_error=3&type=events')); exit;
    }

    global $wpdb;
    $imported = $skipped = $errors = 0;
    $skipped_list = array();
    $errors_list  = array();
    $table = $wpdb->prefix . 'spa_events';

    foreach ( $rows as $i => $row ) {
        $name       = sanitize_text_field($row['name'] ?? '');
        $event_date = sanitize_text_field($row['event_date'] ?? '');
        $start_time = sanitize_text_field($row['start_time'] ?? '');
        $end_time   = sanitize_text_field($row['end_time'] ?? '');
        $raw_service_builder_url = trim((string) ($row['service_builder_url'] ?? ''));
        $service_builder_url = spa_sanitize_service_builder_url($raw_service_builder_url);

        if ( empty($name) || empty($event_date) || empty($start_time) || empty($end_time) ) {
            $errors_list[] = array('row' => $i + 2, 'reason' => 'Missing required fields (name, event_date, start_time, end_time)');
            $errors++;
            continue;
        }

        // Validate date format
        if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $event_date) ) {
            $errors_list[] = array('row' => $i + 2, 'reason' => "Invalid date format: $event_date (use YYYY-MM-DD)");
            $errors++;
            continue;
        }
        if ( $raw_service_builder_url !== '' && $service_builder_url === '' ) {
            $errors_list[] = array('row' => $i + 2, 'reason' => 'Invalid Lutheran Service Builder day URL');
            $errors++;
            continue;
        }

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE name = %s AND event_date = %s", $name, $event_date
        ));
        if ( $existing ) {
            $skipped_list[] = array('row' => $i + 2, 'name' => $name, 'reason' => 'Event with same name and date already exists');
            $skipped++;
            continue;
        }

        $ok = $wpdb->insert($table, array(
            'name'                => $name,
            'season'              => in_array(sanitize_text_field($row['season'] ?? ''), spa_get_church_year_seasons(), true)
                ? sanitize_text_field($row['season'])
                : null,
            'special_day'        => in_array(sanitize_text_field($row['special_day'] ?? ''), spa_get_church_year_special_days(), true)
                ? sanitize_text_field($row['special_day'])
                : null,
            'event_date'          => $event_date,
            'start_time'          => $start_time,
            'end_time'            => $end_time,
            'description'         => sanitize_textarea_field($row['description'] ?? ''),
            'location'            => sanitize_text_field($row['location'] ?? ''),
            'service_builder_url' => $service_builder_url ?: null,
            'is_recurring'        => intval($row['is_recurring'] ?? 0),
            'recurrence_type'     => sanitize_text_field($row['recurrence_type'] ?? ''),
            'recurrence_end_date' => sanitize_text_field($row['recurrence_end_date'] ?? '') ?: null,
            'active'              => intval($row['active'] ?? 1),
        ), array('%s','%s','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s','%d'));

        if ( $ok ) { $imported++; } else { $errors_list[] = array('row' => $i + 2, 'reason' => 'Database insert failed'); $errors++; }
    }

    set_transient('spa_import_results', array(
        'type'         => 'events',
        'imported'     => $imported,
        'skipped'      => $skipped,
        'errors'       => $errors,
        'skipped_list' => $skipped_list,
        'errors_list'  => $errors_list,
    ), HOUR_IN_SECONDS);

    wp_redirect(admin_url('admin.php?page=spa-settings&tab=import&import_success=1'));
    exit;
}
add_action('admin_post_spa_import_events', 'spa_handle_import_events');
