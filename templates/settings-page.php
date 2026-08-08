<?php
/**
 * Render a protected secret/token input field content.
 * If a value exists in the option, show a locked view with delete button.
 * Otherwise, show an editable password input.
 * @param string $field_id HTML id/name for the input
 * @param string $option_name WordPress option key
 * @param string $label Display label (for reference, not rendered)
 */
function spa_render_secret_field($field_id, $option_name, $label = 'API Key / Token') {
    $value = get_option($option_name, '');
    $has_value = !empty($value);
    
    if ($has_value): ?>
        <div style="padding: 0.5rem; background: #f5f5f5; border: 1px solid #ddd; border-radius: 3px; display: inline-block;">
            <span style="font-family: monospace; color: #666;">********* (saved)</span>
            <button type="button" class="spa-delete-secret-btn button button-small" data-field="<?php echo esc_attr($field_id); ?>" data-option="<?php echo esc_attr($option_name); ?>" style="margin-left: 1rem;">Delete & Re-enter</button>
        </div>
        <input type="hidden" name="<?php echo esc_attr($field_id); ?>" id="<?php echo esc_attr($field_id); ?>" value="">
    <?php else: ?>
        <input name="<?php echo esc_attr($field_id); ?>" id="<?php echo esc_attr($field_id); ?>" type="password" value="" class="regular-text">
        <p class="description">Enter a new value. This field is required to configure this provider.</p>
    <?php endif;
}

include SPA_TEMPLATE_DIR . 'header.php'; ?>

<div class="spa-settings">

    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=general')); ?>" class="nav-tab <?php echo ($active_tab === 'general') ? 'nav-tab-active' : ''; ?>">General</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=email')); ?>" class="nav-tab <?php echo ($active_tab === 'email') ? 'nav-tab-active' : ''; ?>">Email</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=sms')); ?>" class="nav-tab <?php echo ($active_tab === 'sms') ? 'nav-tab-active' : ''; ?>">SMS</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=push')); ?>" class="nav-tab <?php echo ($active_tab === 'push') ? 'nav-tab-active' : ''; ?>">Push Notifications</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=import')); ?>" class="nav-tab <?php echo ($active_tab === 'import') ? 'nav-tab-active' : ''; ?>">Import / Export</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=templates')); ?>" class="nav-tab <?php echo ($active_tab === 'templates') ? 'nav-tab-active' : ''; ?>">Templates</a>
    </nav>

    <div class="spa-settings-content" style="margin-top:1rem;">

        <?php if ( isset($_GET['saved']) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
        <?php endif; ?>

        <?php if ( isset($_GET['import_success']) ) : ?>
            <?php 
            $import_results = get_transient('spa_import_results');
            if ($import_results) :
                delete_transient('spa_import_results');
                $import_type = ucfirst($import_results['type'] ?? 'records');
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php echo esc_html($import_type); ?> import completed!</strong><br>
                    Imported: <strong><?php echo intval($import_results['imported']); ?></strong> | 
                    Skipped: <strong><?php echo intval($import_results['skipped']); ?></strong> | 
                    Errors: <strong><?php echo intval($import_results['errors']); ?></strong>
                </p>
            </div>

            <?php if ( ! empty($import_results['skipped_list']) ) : ?>
            <div style="background:#fff8e5;border-left:4px solid #ffb900;padding:1rem;margin-bottom:1rem;">
                <h4 style="margin-top:0;">Skipped Entries (<?php echo count($import_results['skipped_list']); ?>)</h4>
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f5f5f5;">
                            <th style="border:1px solid #ddd;padding:0.5rem;text-align:left;">Row</th>
                            <th style="border:1px solid #ddd;padding:0.5rem;text-align:left;">Identifier</th>
                            <th style="border:1px solid #ddd;padding:0.5rem;text-align:left;">Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $import_results['skipped_list'] as $skipped ) : 
                            // Build a readable identifier from whatever keys are present
                            $ident = $skipped['email'] ?? $skipped['name'] ?? ($skipped['first_name'] . ' ' . $skipped['last_name']);
                        ?>
                        <tr>
                            <td style="border:1px solid #ddd;padding:0.5rem;"><?php echo intval($skipped['row']); ?></td>
                            <td style="border:1px solid #ddd;padding:0.5rem;"><?php echo esc_html(trim($ident)); ?></td>
                            <td style="border:1px solid #ddd;padding:0.5rem;"><?php echo esc_html($skipped['reason']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if ( ! empty($import_results['errors_list']) ) : ?>
            <div style="background:#fee;border-left:4px solid #dc3545;padding:1rem;margin-bottom:1rem;">
                <h4 style="margin-top:0;color:#dc3545;">Errors (<?php echo count($import_results['errors_list']); ?>)</h4>
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f5f5f5;">
                            <th style="border:1px solid #ddd;padding:0.5rem;text-align:left;">Row</th>
                            <th style="border:1px solid #ddd;padding:0.5rem;text-align:left;">Error Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $import_results['errors_list'] as $error ) : ?>
                        <tr>
                            <td style="border:1px solid #ddd;padding:0.5rem;"><?php echo intval($error['row']); ?></td>
                            <td style="border:1px solid #ddd;padding:0.5rem;"><strong><?php echo esc_html($error['reason']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ( isset($_GET['import_error']) ) : ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <?php 
                    $error_code = intval($_GET['import_error']);
                    switch($error_code) {
                        case 1:
                            echo 'File upload failed. Please make sure a file was selected and try again.';
                            break;
                        case 2:
                            echo 'Invalid file format. Please upload a CSV or XLSX file.';
                            break;
                        case 3:
                            echo 'No data found in file. Make sure the file has headers and at least one row of data.';
                            break;
                        case 4:
                            echo 'File is too large. Maximum file size is 5MB.';
                            break;
                        default:
                            echo 'An unknown error occurred during import.';
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>

        <?php
        // Close the main settings form if not on import tab
        if ($active_tab !== 'import') : ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="spa_save_settings">
                    <?php wp_nonce_field('spa_save_settings', 'spa_settings_nonce'); ?>
                    <input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>">

            <?php
        endif;
         
        switch ($active_tab) {
            case 'email':
                $notification_email = esc_attr( get_option('spa_notification_email', '') );
                $enable_email = get_option('spa_enable_email', 0 );
                $email_provider = get_option('spa_email_provider', 'wp_mail');
                    ?>
                    <h2>Email Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="spa_notification_email">Notification Email</label></th>
                            <td><input name="spa_notification_email" id="spa_notification_email" type="email" value="<?php echo $notification_email; ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row">Enable Email</th>
                            <td><input name="spa_enable_email" type="checkbox" value="1" <?php checked(1, $enable_email); ?>></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="spa_email_provider">Email Provider</label></th>
                            <td>
                                <select name="spa_email_provider" id="spa_email_provider">
                                    <option value="wp_mail" <?php selected($email_provider, 'wp_mail'); ?>>WordPress Mail (wp_mail)</option>
                                    <option value="smtp" <?php selected($email_provider, 'smtp'); ?>>SMTP (custom)</option>
                                    <option value="sendgrid" <?php selected($email_provider, 'sendgrid'); ?>>SendGrid (API)</option>
                                    <option value="mailgun" <?php selected($email_provider, 'mailgun'); ?>>Mailgun (API)</option>
                                    <option value="mailpoet" <?php selected($email_provider, 'mailpoet'); ?>>MailPoet (plugin)</option>
                                    <option value="ses" <?php selected($email_provider, 'ses'); ?>>Amazon SES</option>
                                    <option value="postmark" <?php selected($email_provider, 'postmark'); ?>>Postmark</option>
                                    <option value="mailersend" <?php selected($email_provider, 'mailersend'); ?>>Mailersend</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <!-- Provider specific fields -->
                    <div class="spa-email-provider-fields">

                        <div class="spa-provider wp_mail" data-provider="wp_mail" style="display:none;">
                            <p>Using WordPress's built-in wp_mail. No provider settings are required. If you need reliable delivery, consider SMTP or an API-based provider listed below.</p>
                        </div>

                        <div class="spa-provider smtp" data-provider="smtp" style="display:none;">
                            <h3>SMTP Settings</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="spa_smtp_host">SMTP Host</label></th>
                                    <td><input name="spa_smtp_host" id="spa_smtp_host" type="text" value="<?php echo esc_attr(get_option('spa_smtp_host', '')); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="spa_smtp_port">SMTP Port</label></th>
                                    <td><input name="spa_smtp_port" id="spa_smtp_port" type="number" value="<?php echo esc_attr(get_option('spa_smtp_port', 587)); ?>" class="small-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="spa_smtp_user">Username</label></th>
                                    <td><input name="spa_smtp_user" id="spa_smtp_user" type="text" value="<?php echo esc_attr(get_option('spa_smtp_user', '')); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label>Password</label></th>
                                    <td><?php spa_render_secret_field('spa_smtp_pass', 'spa_smtp_pass'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="spa_smtp_encryption">Encryption</label></th>
                                    <td>
                                        <select name="spa_smtp_encryption" id="spa_smtp_encryption">
                                            <option value="none" <?php selected(get_option('spa_smtp_encryption', 'tls'), 'none'); ?>>None</option>
                                            <option value="ssl" <?php selected(get_option('spa_smtp_encryption', 'tls'), 'ssl'); ?>>SSL</option>
                                            <option value="tls" <?php selected(get_option('spa_smtp_encryption', 'tls'), 'tls'); ?>>TLS</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="spa_smtp_from_address">From Address</label></th>
                                    <td><input name="spa_smtp_from_address" id="spa_smtp_from_address" type="email" value="<?php echo esc_attr(get_option('spa_smtp_from_address', '')); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="spa_smtp_from_name">From Name</label></th>
                                    <td><input name="spa_smtp_from_name" id="spa_smtp_from_name" type="text" value="<?php echo esc_attr(get_option('spa_smtp_from_name', '')); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                            <p class="description">Instructions: Enter SMTP credentials from your provider (e.g., Sendinblue, Gmail SMTP, or your hosting provider). Ensure the From address is verified with some providers.</p>
                        </div>

                        <div class="spa-provider sendgrid" data-provider="sendgrid" style="display:none;">
                            <h3>SendGrid</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">API Key</th>
                                    <td><?php spa_render_secret_field('spa_sendgrid_api_key', 'spa_sendgrid_api_key'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">From Address</th>
                                    <td><input name="spa_sendgrid_from" type="email" value="<?php echo esc_attr(get_option('spa_sendgrid_from', '')); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row">From Name</th>
                                    <td><input name="spa_sendgrid_from_name" type="text" value="<?php echo esc_attr(get_option('spa_sendgrid_from_name', '')); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                            <p class="description">Instructions: Create an API key in SendGrid (Full Access or Mail Send), verify your sender identity, then paste the key here. See https://sendgrid.com/docs/ for setup help.</p>
                        </div>

                        <div class="spa-provider mailgun" data-provider="mailgun" style="display:none;">
                            <h3>Mailgun</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">API Key</th>
                                    <td><?php spa_render_secret_field('spa_mailgun_api_key', 'spa_mailgun_api_key'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Domain</th>
                                    <td><input name="spa_mailgun_domain" type="text" value="<?php echo esc_attr(get_option('spa_mailgun_domain', '')); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                            <p class="description">Instructions: Create a Mailgun account, add and verify your sending domain, and paste the API key and domain here. See https://www.mailgun.com/ for docs.</p>
                        </div>

                        <div class="spa-provider mailpoet" data-provider="mailpoet" style="display:none;">
                            <h3>MailPoet</h3>
                            <p>If you use the MailPoet plugin, select it here. MailPoet manages its own settings; this selection tells the SPA plugin to use MailPoet for sending.</p>
                            <p class="description">Instructions: Install & configure MailPoet via its plugin settings. Then select the list to use for notifications below (optional):</p>
                            <p><label>MailPoet list ID (optional): <input name="spa_mailpoet_list" type="text" value="<?php echo esc_attr(get_option('spa_mailpoet_list', '')); ?>" class="regular-text"></label></p>
                        </div>

                        <div class="spa-provider ses" data-provider="ses" style="display:none;">
                            <h3>Amazon SES</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Access Key ID</th>
                                    <td><?php spa_render_secret_field('spa_ses_key', 'spa_ses_key'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Secret Access Key</th>
                                    <td><?php spa_render_secret_field('spa_ses_secret', 'spa_ses_secret'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Region</th>
                                    <td><input name="spa_ses_region" type="text" value="<?php echo esc_attr(get_option('spa_ses_region', 'us-east-1')); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                            <p class="description">Instructions: Create IAM credentials with SES SendEmail permission and verify your sending domain. See https://docs.aws.amazon.com/ses/latest/ for details.</p>
                        </div>

                        <div class="spa-provider postmark" data-provider="postmark" style="display:none;">
                            <h3>Postmark</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Server Token</th>
                                    <td><?php spa_render_secret_field('spa_postmark_token', 'spa_postmark_token'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">From Address</th>
                                    <td><input name="spa_postmark_from" type="email" value="<?php echo esc_attr(get_option('spa_postmark_from', '')); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                            <p class="description">Instructions: Create a server token in Postmark and verify sender signatures. See https://postmarkapp.com/ for docs.</p>
                        </div>

                        <div class="spa-provider mailersend" data-provider="mailersend" style="display:none;">
                            <h3>Mailersend</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">API Token</th>
                                    <td><?php spa_render_secret_field('spa_mailersend_token', 'spa_mailersend_token'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">From Address</th>
                                    <td><input name="spa_mailersend_from" type="email" value="<?php echo esc_attr(get_option('spa_mailersend_from', '')); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                            <p class="description">Instructions: Create an API token at Mailersend, verify your domain or sender signature, and paste the token here. See https://www.mailersend.com/ for docs.</p>
                        </div>

                    </div>

                    <?php
                    // Test send form
                    ?>
                    <h3>Send a test email</h3>
                    <p>Use this to verify your chosen provider and settings. A test message will be sent using the configured From address.</p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="spa_test_recipient">Recipient Email</label></th>
                            <td>
                                <input name="spa_test_recipient" id="spa_test_recipient" type="email" value="" class="regular-text">
                                <p class="description">Enter an email address to receive a test notification. When you click "Send Test Email", the settings on this page will be saved and a test message sent immediately.</p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
    <button type="button" id="spa-send-test-btn" class="button">Send Test Email</button>
    <span id="spa-test-result" style="margin-left:1rem;vertical-align:middle;"></span>
</p>

                    <?php
                    break;

                case 'sms':
                    $sms_provider = esc_attr( get_option('spa_sms_provider', 'twilio') );
                    $enable_sms = get_option('spa_enable_sms', 0 );
                    ?>
                    <h2>SMS Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="spa_sms_provider">SMS Provider</label></th>
                            <td>
                                <select name="spa_sms_provider" id="spa_sms_provider">
                                    <option value="twilio" <?php selected($sms_provider, 'twilio'); ?>>Twilio</option>
                                    <option value="vonage" <?php selected($sms_provider, 'vonage'); ?>>Vonage (Nexmo)</option>
                                    <option value="plivo" <?php selected($sms_provider, 'plivo'); ?>>Plivo</option>
                                    <option value="messagebird" <?php selected($sms_provider, 'messagebird'); ?>>MessageBird</option>
                                    <option value="textmagic" <?php selected($sms_provider, 'textmagic'); ?>>TextMagic</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Enable SMS</th>
                            <td><input name="spa_enable_sms" type="checkbox" value="1" <?php checked(1, $enable_sms); ?>></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="spa_sms_default_country">Default Country</label></th>
                            <td>
                                <select name="spa_sms_default_country" id="spa_sms_default_country">
                                    <?php
                                    $countries = array('US','CA','GB','AU','DE');
                                    foreach ( $countries as $c ) {
                                        $example = function_exists('spa_get_example_number') ? spa_get_example_number($c) : '';
                                        $dial = spa_country_to_dial($c);
                                        $label = $c;
                                        switch ($c) { case 'US': $label = 'United States (+1)'; break; case 'CA': $label = 'Canada (+1)'; break; case 'GB': $label = 'United Kingdom (+44)'; break; case 'AU': $label = 'Australia (+61)'; break; case 'DE': $label = 'Germany (+49)'; break; }
                                        printf('<option value="%1$s" data-dial="%2$s" data-example="%3$s" %4$s>%5$s</option>', esc_attr($c), esc_attr($dial), esc_attr($example), selected(get_option('spa_sms_default_country','US'), $c, false), esc_html($label));
                                    }
                                    ?>
                                </select>
                                <p class="description">Select a default country to help normalize local phone numbers for test sends.</p>
                            </td>
                        </tr>
                    </table>

                    <div class="spa-sms-provider-fields">
                        <div class="spa-sms-provider twilio" data-provider="twilio" style="display:none;">
                            <h3>Twilio Settings</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Account SID</th>
                                    <td><input name="spa_twilio_sid" type="text" value="<?php echo esc_attr(get_option('spa_twilio_sid','')); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Auth Token</th>
                                    <td><?php spa_render_secret_field('spa_twilio_token', 'spa_twilio_token'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">From Number</th>
                                    <td><input name="spa_twilio_from" type="text" value="<?php echo esc_attr(get_option('spa_twilio_from','')); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="spa-sms-provider vonage" data-provider="vonage" style="display:none;">
                            <h3>Vonage (Nexmo) Settings</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">API Key</th>
                                    <td><input name="spa_vonage_key" type="text" value="<?php echo esc_attr(get_option('spa_vonage_key','')); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row">API Secret</th>
                                    <td><?php spa_render_secret_field('spa_vonage_secret', 'spa_vonage_secret'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">From</th>
                                    <td><input name="spa_vonage_from" type="text" value="<?php echo esc_attr(get_option('spa_vonage_from','')); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="spa-sms-provider plivo" data-provider="plivo" style="display:none;">
                            <h3>Plivo Settings</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Auth ID</th>
                                    <td><input name="spa_plivo_auth_id" type="text" value="<?php echo esc_attr(get_option('spa_plivo_auth_id','')); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Auth Token</th>
                                    <td><?php spa_render_secret_field('spa_plivo_auth_token', 'spa_plivo_auth_token'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">From</th>
                                    <td><input name="spa_plivo_from" type="text" value="<?php echo esc_attr(get_option('spa_plivo_from','')); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="spa-sms-provider messagebird" data-provider="messagebird" style="display:none;">
                            <h3>MessageBird Settings</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">API Key</th>
                                    <td><?php spa_render_secret_field('spa_messagebird_key', 'spa_messagebird_key'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">From</th>
                                    <td><input name="spa_messagebird_from" type="text" value="<?php echo esc_attr(get_option('spa_messagebird_from','')); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="spa-sms-provider textmagic" data-provider="textmagic" style="display:none;">
                            <h3>TextMagic Settings</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Username</th>
                                    <td><input name="spa_textmagic_username" type="text" value="<?php echo esc_attr(get_option('spa_textmagic_username','')); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row">API Key</th>
                                    <td><?php spa_render_secret_field('spa_textmagic_api_key', 'spa_textmagic_api_key'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">From</th>
                                    <td><input name="spa_textmagic_from" type="text" value="<?php echo esc_attr(get_option('spa_textmagic_from','')); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h3>Send a test SMS</h3>
                    <p>Use this to verify your chosen provider and settings. A test message will be sent using the configured From number.</p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="spa_test_recipient">Recipient Phone</label></th>
                            <td>
                                <input name="spa_test_sms_recipient" id="spa_test_sms_recipient" type="text" value="" class="regular-text">
                                <p class="description">Enter a phone number to receive a test SMS. Use E.164 format (e.g. +15551234567) for best compatibility. Example: <span id="spa-sms-example" style="font-weight:600;"></span></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="button" id="spa-send-test-sms-btn" class="button">Send Test SMS</button>
                        <span id="spa-test-sms-result" style="margin-left:1rem;vertical-align:middle;"></span>
                    </p>
                    <?php
                    break;

                case 'push':
                    $enable_push = get_option('spa_enable_push', 0 );
                    $push_provider = esc_attr( get_option('spa_push_provider', 'onesignal') );
                    ?>
                    <h2>Push Notifications</h2>
                    <p>Push notifications allow you to send messages directly to users' browsers and devices. Note: Not supported on all browsers or devices.</p>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Push Notifications</th>
                            <td><input name="spa_enable_push" type="checkbox" value="1" <?php checked(1, $enable_push); ?>></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="spa_push_provider">Push Provider</label></th>
                            <td>
                                <select name="spa_push_provider" id="spa_push_provider">
                                    <option value="onesignal" <?php selected($push_provider, 'onesignal'); ?>>OneSignal</option>
                                    <option value="firebase" <?php selected($push_provider, 'firebase'); ?>>Firebase Cloud Messaging</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <div class="spa-push-provider-fields">
                        <div class="spa-push-provider onesignal" data-provider="onesignal" style="display:none;">
                            <h3>OneSignal Settings</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">App ID</th>
                                    <td><input name="spa_onesignal_app_id" type="text" value="<?php echo esc_attr(get_option('spa_onesignal_app_id','')); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row">API Key</th>
                                    <td><?php spa_render_secret_field('spa_onesignal_api_key', 'spa_onesignal_api_key'); ?></td>
                                </tr>
                            </table>
                            <p class="description">Instructions: Create a OneSignal account, create an app, and copy your App ID and REST API Key. See https://onesignal.com/documentation for setup help.</p>
                        </div>
                        <div class="spa-push-provider firebase" data-provider="firebase" style="display:none;">
                            <h3>Firebase Cloud Messaging Settings</h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">Project ID</th>
                                    <td><input name="spa_firebase_project_id" type="text" value="<?php echo esc_attr(get_option('spa_firebase_project_id','')); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Server Key</th>
                                    <td><?php spa_render_secret_field('spa_firebase_server_key', 'spa_firebase_server_key'); ?></td>
                                </tr>
                            </table>
                            <p class="description">Instructions: Create a Firebase project, enable Cloud Messaging, and copy your Project ID and Server API Key. See https://firebase.google.com/docs for setup help.</p>
                        </div>
                    </div>
                    <?php
                    break;

                case 'import':
                    // Helper to render an import form
                    $render_import_form = function($action, $nonce_action, $nonce_field, $btn_label) { ?>
                        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:8px;">
                            <?php wp_nonce_field($nonce_action, $nonce_field); ?>
                            <input type="hidden" name="action" value="<?php echo esc_attr($action); ?>">
                            <input type="file" name="spa_import_file" accept=".csv" required style="margin-right:8px;">
                            <button type="submit" class="button"><?php echo esc_html($btn_label); ?></button>
                        </form>
                    <?php };
                    ?>

                    <h2>Import / Export</h2>

                    <div style="display:flex;gap:2rem;flex-wrap:wrap;align-items:flex-start;">

                        <!-- EXPORT -->
                        <div style="flex:1;min-width:280px;">
                            <h3 style="border-bottom:1px solid #ddd;padding-bottom:6px;">Export</h3>
                            <p>Download your data as CSV files that can be reimported or opened in Excel/Sheets.</p>

                            <table class="widefat" style="margin-bottom:1.5rem;">
                                <thead><tr><th>Data</th><th>Records</th><th></th></tr></thead>
                                <tbody>
                                <?php
                                global $wpdb;
                                $counts = array(
                                    'Volunteers' => array('table' => 'spa_volunteers', 'action' => 'spa_export_volunteers', 'nonce' => 'spa_export_volunteers'),
                                    'Teams'      => array('table' => 'spa_teams',      'action' => 'spa_export_teams',      'nonce' => 'spa_export_teams'),
                                    'Events'     => array('table' => 'spa_events',     'action' => 'spa_export_events',     'nonce' => 'spa_export_events'),
                                );
                                foreach ( $counts as $label => $cfg ) :
                                    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$cfg['table']}");
                                ?>
                                <tr>
                                    <td><?php echo esc_html($label); ?></td>
                                    <td><?php echo intval($count); ?></td>
                                    <td>
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                            <?php wp_nonce_field($cfg['nonce']); ?>
                                            <input type="hidden" name="action" value="<?php echo esc_attr($cfg['action']); ?>">
                                            <button type="submit" class="button button-small">Download CSV</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- IMPORT -->
                        <div style="flex:1;min-width:320px;">
                            <h3 style="border-bottom:1px solid #ddd;padding-bottom:6px;">Import</h3>
                            <p>Upload a CSV file to bulk-import data. Duplicates are skipped and detailed results shown after import.</p>

                            <h4 style="margin-bottom:4px;">Import Volunteers</h4>
                            <p style="margin:0 0 4px;font-size:0.85em;color:#555;">Required columns: <code>first_name</code>, <code>last_name</code>, <code>email</code> &nbsp;Optional: <code>phone</code> (e.g. +13209999999)</p>
                            <?php $render_import_form('spa_import_volunteers', 'spa_import_volunteers', 'spa_import_nonce', 'Import Volunteers'); ?>

                            <h4 style="margin:1.5rem 0 4px;">Import Teams</h4>
                            <p style="margin:0 0 4px;font-size:0.85em;color:#555;">Required columns: <code>name</code> &nbsp;Optional: <code>description</code>, <code>active</code> (1 or 0)</p>
                            <?php $render_import_form('spa_import_teams', 'spa_import_teams', 'spa_import_nonce', 'Import Teams'); ?>

                            <h4 style="margin:1.5rem 0 4px;">Import Events</h4>
                            <p style="margin:0 0 4px;font-size:0.85em;color:#555;">Required columns: <code>name</code>, <code>event_date</code> (YYYY-MM-DD), <code>start_time</code> (HH:MM:SS), <code>end_time</code> (HH:MM:SS)<br>Optional: <code>description</code>, <code>location</code>, <code>is_recurring</code>, <code>recurrence_type</code>, <code>recurrence_end_date</code>, <code>active</code></p>
                            <?php $render_import_form('spa_import_events', 'spa_import_events', 'spa_import_nonce', 'Import Events'); ?>
                        </div>

                    </div>
                    <?php
                    break;

                case 'templates':
                    $all_templates = $wpdb->get_results(
                        "SELECT id, name, type, subject FROM {$wpdb->prefix}spa_notification_templates ORDER BY type, name"
                    );
                    $email_templates = array_filter($all_templates, fn($t) => $t->type === 'email');
                    $sms_templates   = array_filter($all_templates, fn($t) => $t->type === 'sms');
                    $tags = spa_template_tags();
                    ?>
                    <h2>Notification Templates</h2>
                    <p>Create reusable templates for email and SMS notifications. Use smart tags to insert dynamic content.</p>

                    <div style="display:flex;gap:2rem;align-items:flex-start;flex-wrap:wrap;">

                        <!-- Template List Sidebar -->
                        <div style="min-width:220px;flex:0 0 220px;">
                            <h3 style="margin-top:0;">Email Templates</h3>
                            <ul id="spa-email-template-list" style="margin:0 0 1rem;padding:0;list-style:none;">
                                <?php foreach ($email_templates as $t) : ?>
                                <li style="margin-bottom:4px;">
                                    <a href="#" class="spa-load-template" data-id="<?php echo intval($t->id); ?>" style="text-decoration:none;">
                                        📧 <?php echo esc_html($t->name); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="button" id="spa-new-email-template">+ New Email Template</button>

                            <h3>SMS Templates</h3>
                            <ul id="spa-sms-template-list" style="margin:0 0 1rem;padding:0;list-style:none;">
                                <?php foreach ($sms_templates as $t) : ?>
                                <li style="margin-bottom:4px;">
                                    <a href="#" class="spa-load-template" data-id="<?php echo intval($t->id); ?>" style="text-decoration:none;">
                                        💬 <?php echo esc_html($t->name); ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="button" id="spa-new-sms-template">+ New SMS Template</button>
                        </div>

                        <!-- Template Editor -->
                        <div style="flex:1;min-width:400px;" id="spa-template-editor">
                            <div id="spa-template-placeholder" style="color:#999;font-style:italic;padding:2rem 0;">
                                Select a template from the list or click &ldquo;+ New&rdquo; to create one.
                            </div>

                            <div id="spa-template-form" style="display:none;">
                                <input type="hidden" id="spa-template-id" value="">
                                <input type="hidden" id="spa-template-type" value="">

                                <table class="form-table" style="margin-bottom:0;">
                                    <tr>
                                        <th><label for="spa-template-name">Template Name</label></th>
                                        <td><input type="text" id="spa-template-name" class="regular-text" placeholder="e.g. Volunteer Reminder"></td>
                                    </tr>
                                    <tr id="spa-template-subject-row">
                                        <th><label for="spa-template-subject">Email Subject</label></th>
                                        <td><input type="text" id="spa-template-subject" class="regular-text" placeholder="e.g. Volunteer Schedule for {event_name}"></td>
                                    </tr>
                                </table>

                                <!-- Smart Tags -->
                                <div style="margin:12px 0;">
                                    <strong>Insert Smart Tag:</strong>
                                    <select id="spa-tag-picker" style="margin-left:8px;">
                                        <option value="">— choose tag —</option>
                                        <?php foreach ($tags as $tag => $label) : ?>
                                        <option value="<?php echo esc_attr($tag); ?>"><?php echo esc_html($label); ?> &rarr; <?php echo esc_html($tag); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="button" id="spa-insert-tag">Insert</button>
                                </div>

                                <!-- Email WYSIWYG -->
                                <div id="spa-email-editor-wrap">
                                    <?php
                                    wp_editor('', 'spa_template_body_email', array(
                                        'textarea_name' => 'spa_template_body_email',
                                        'textarea_rows' => 14,
                                        'media_buttons' => false,
                                        'teeny'         => false,
                                        'tinymce'       => array(
                                            'toolbar1' => 'bold italic underline | bullist numlist | link | forecolor | removeformat',
                                            'toolbar2' => '',
                                        ),
                                    ));
                                    ?>
                                </div>

                                <!-- SMS Textarea -->
                                <div id="spa-sms-editor-wrap" style="display:none;">
                                    <textarea id="spa-template-body-sms" rows="6" style="width:100%;font-size:14px;" placeholder="Write your SMS message here. Keep it under 75 words."></textarea>
                                    <p style="margin:4px 0 0;font-size:0.85em;color:#666;">
                                        Word count: <strong id="spa-sms-word-count">0</strong> / 75
                                    </p>
                                </div>

                                <p style="margin-top:1rem;display:flex;gap:8px;align-items:center;">
                                    <button type="button" class="button button-primary" id="spa-save-template-btn">Save Template</button>
                                    <button type="button" class="button button-link-delete" id="spa-delete-template-btn" style="display:none;">Delete Template</button>
                                    <span id="spa-template-status" style="font-style:italic;color:#666;"></span>
                                </p>
                            </div>
                        </div>

                    </div>
                    <?php
                    break;

                default:
                    global $wpdb;
                    $org_name = esc_attr( get_option('spa_org_name', '') );
                    $active_email_tpl = get_option('spa_active_email_template', '');
                    $active_sms_tpl   = get_option('spa_active_sms_template', '');
                    $email_templates_gen = $wpdb->get_results(
                        "SELECT id, name FROM {$wpdb->prefix}spa_notification_templates WHERE type = 'email' ORDER BY name"
                    );
                    $sms_templates_gen = $wpdb->get_results(
                        "SELECT id, name FROM {$wpdb->prefix}spa_notification_templates WHERE type = 'sms' ORDER BY name"
                    );
                    ?>
                    <h2>General Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="spa_org_name">Organization Name</label></th>
                            <td><input name="spa_org_name" id="spa_org_name" type="text" value="<?php echo $org_name; ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="spa_active_email_template">Active Email Template</label></th>
                            <td>
                                <select name="spa_active_email_template" id="spa_active_email_template">
                                    <option value="">— None selected —</option>
                                    <?php foreach ( $email_templates_gen as $t ) : ?>
                                        <option value="<?php echo intval($t->id); ?>" <?php selected($active_email_tpl, $t->id); ?>><?php echo esc_html($t->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ( empty($email_templates_gen) ) : ?>
                                    <p class="description">No email templates yet. <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=templates')); ?>">Create one</a>.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="spa_active_sms_template">Active SMS Template</label></th>
                            <td>
                                <select name="spa_active_sms_template" id="spa_active_sms_template">
                                    <option value="">— None selected —</option>
                                    <?php foreach ( $sms_templates_gen as $t ) : ?>
                                        <option value="<?php echo intval($t->id); ?>" <?php selected($active_sms_tpl, $t->id); ?>><?php echo esc_html($t->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ( empty($sms_templates_gen) ) : ?>
                                    <p class="description">No SMS templates yet. <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=templates')); ?>">Create one</a>.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <?php
                    break;
            }
            ?>

           <?php if ($active_tab !== 'import') : ?>
               <p class="submit"><button type="submit" class="button button-primary">Save Changes</button></p>
           </form>
           <?php endif; ?>

       </div>

    <div class="spa-danger-zone" style="margin-top:2rem;border-top:1px solid #e5e5e5;padding-top:1rem;">
        <h3 style="color:#b71c1c;">Danger Zone</h3>
        <p><strong>Warning:</strong> Uninstalling this plugin will permanently delete all plugin data (tables, user meta, and transients). To uninstall, deactivate and delete the plugin from the Plugins screen. This action is irreversible.</p>
        <p>To proceed, type <code>stpauls-admin</code> (the plugin folder name) into the confirmation box below. Once the text matches exactly, the uninstall button will be enabled. Then deactivate &amp; delete the plugin from the Plugins page to trigger data removal.</p>
        <div style="margin-top:.5rem;">
            <input type="text" id="spa-uninstall-confirm" placeholder="Type stpauls-admin to confirm" style="margin-right:.5rem;padding:.25rem .5rem;">
            <a href="<?php echo esc_url(admin_url('plugins.php')); ?>"
               class="button button-danger spa-uninstall-btn disabled"
               data-slug="stpauls-admin"
               aria-disabled="true"
               style="background:#b71c1c;border-color:#7a1414;color:#fff;padding:.5rem 1rem;display:inline-block;pointer-events:none;opacity:.6;">Go to Plugins page to uninstall</a>
        </div>
    </div>

</div>