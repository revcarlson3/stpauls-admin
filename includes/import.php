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
?>
