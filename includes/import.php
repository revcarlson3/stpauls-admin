<?php
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
        while ( ($data = fgetcsv($handle, 1000, ',')) !== FALSE ) {
            $row_num++;

            if ( $row_num === 1 ) {
                $headers = array_map('trim', $data);
                continue;
            }

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
        $first_name = isset($row['first_name']) ? sanitize_text_field($row['first_name']) : '';
        $last_name = isset($row['last_name']) ? sanitize_text_field($row['last_name']) : '';
        $email = isset($row['email']) ? sanitize_email($row['email']) : '';
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
        "SELECT name, event_date, start_time, end_time, description, location,
                is_recurring, recurrence_type, recurrence_end_date, active
         FROM {$wpdb->prefix}spa_events ORDER BY event_date",
        ARRAY_A
    );

    spa_export_csv('spa-events-' . date('Y-m-d') . '.csv',
        array('name','event_date','start_time','end_time','description','location',
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
            'event_date'          => $event_date,
            'start_time'          => $start_time,
            'end_time'            => $end_time,
            'description'         => sanitize_textarea_field($row['description'] ?? ''),
            'location'            => sanitize_text_field($row['location'] ?? ''),
            'is_recurring'        => intval($row['is_recurring'] ?? 0),
            'recurrence_type'     => sanitize_text_field($row['recurrence_type'] ?? ''),
            'recurrence_end_date' => sanitize_text_field($row['recurrence_end_date'] ?? '') ?: null,
            'active'              => intval($row['active'] ?? 1),
        ), array('%s','%s','%s','%s','%s','%s','%d','%s','%s','%d'));

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

