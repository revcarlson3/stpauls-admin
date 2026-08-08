<?php include SPA_TEMPLATE_DIR . 'header.php'; ?>

<div class="spa-settings">

    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=general')); ?>" class="nav-tab <?php echo ($active_tab === 'general') ? 'nav-tab-active' : ''; ?>">General</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=email')); ?>" class="nav-tab <?php echo ($active_tab === 'email') ? 'nav-tab-active' : ''; ?>">Email</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=sms')); ?>" class="nav-tab <?php echo ($active_tab === 'sms') ? 'nav-tab-active' : ''; ?>">SMS</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=push')); ?>" class="nav-tab <?php echo ($active_tab === 'push') ? 'nav-tab-active' : ''; ?>">Push Notifications</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=templates')); ?>" class="nav-tab <?php echo ($active_tab === 'templates') ? 'nav-tab-active' : ''; ?>">Templates</a>
    </nav>

    <div class="spa-settings-content" style="margin-top:1rem;">

        <?php if ( isset($_GET['saved']) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="spa_save_settings">
                    <?php wp_nonce_field('spa_save_settings', 'spa_settings_nonce'); ?>
                    <input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>">

            <?php
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
                                    <th scope="row"><label for="spa_smtp_pass">Password</label></th>
                                    <td><input name="spa_smtp_pass" id="spa_smtp_pass" type="password" value="" class="regular-text"><p class="description">Leave blank to keep existing password.</p></td>
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
                                    <td><input name="spa_sendgrid_api_key" type="text" value="" class="regular-text"><p class="description">Store your SendGrid API key here.</p></td>
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
                                    <td><input name="spa_mailgun_api_key" type="text" value="" class="regular-text"></td>
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
                                    <td><input name="spa_ses_key" type="text" value="" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th scope="row">Secret Access Key</th>
                                    <td><input name="spa_ses_secret" type="password" value="" class="regular-text"></td>
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
                                    <td><input name="spa_postmark_token" type="text" value="" class="regular-text"></td>
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
                                    <td><input name="spa_mailersend_token" type="text" value="" class="regular-text"></td>
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
                                    <option value="US" data-dial="1" <?php selected(get_option('spa_sms_default_country','US'),'US'); ?>>United States (+1)</option>
                                    <option value="CA" data-dial="1" <?php selected(get_option('spa_sms_default_country','US'),'CA'); ?>>Canada (+1)</option>
                                    <option value="GB" data-dial="44" <?php selected(get_option('spa_sms_default_country','US'),'GB'); ?>>United Kingdom (+44)</option>
                                    <option value="AU" data-dial="61" <?php selected(get_option('spa_sms_default_country','US'),'AU'); ?>>Australia (+61)</option>
                                    <option value="DE" data-dial="49" <?php selected(get_option('spa_sms_default_country','US'),'DE'); ?>>Germany (+49)</option>
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
                                    <td><input name="spa_twilio_token" type="password" value="" class="regular-text"><p class="description">Leave blank to keep existing token.</p></td>
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
                                    <td><input name="spa_vonage_secret" type="password" value="" class="regular-text"><p class="description">Leave blank to keep existing secret.</p></td>
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
                                    <td><input name="spa_plivo_auth_token" type="password" value="" class="regular-text"><p class="description">Leave blank to keep existing token.</p></td>
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
                                    <td><input name="spa_messagebird_key" type="text" value="<?php echo esc_attr(get_option('spa_messagebird_key','')); ?>" class="regular-text"></td>
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
                                    <td><input name="spa_textmagic_api_key" type="password" value="" class="regular-text"><p class="description">Leave blank to keep existing key.</p></td>
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
                                <p class="description">Enter a phone number to receive a test SMS. Use E.164 format (e.g. +15551234567) for best compatibility.</p>
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
                    ?>
                    <h2>Push Notifications</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Push</th>
                            <td><input name="spa_enable_push" type="checkbox" value="1" <?php checked(1, $enable_push); ?>></td>
                        </tr>
                    </table>
                    <?php
                    break;

                case 'templates':
                    $example_template = esc_textarea( get_option('spa_example_template', '') );
                    ?>
                    <h2>Notification Templates</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="spa_example_template">Example Template</label></th>
                            <td><textarea name="spa_example_template" id="spa_example_template" rows="8" cols="60"><?php echo $example_template; ?></textarea></td>
                        </tr>
                    </table>
                    <?php
                    break;

                default:
                    $org_name = esc_attr( get_option('spa_org_name', '') );
                    ?>
                    <h2>General Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="spa_org_name">Organization Name</label></th>
                            <td><input name="spa_org_name" id="spa_org_name" type="text" value="<?php echo $org_name; ?>" class="regular-text"></td>
                        </tr>
                    </table>
                    <?php
                    break;
            }
            ?>

            <p class="submit"><button type="submit" class="button button-primary">Save Changes</button></p>
        </form>

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