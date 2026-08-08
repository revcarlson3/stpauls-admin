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

        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=spa-settings&tab=' . $active_tab)); ?>">
            <?php wp_nonce_field('spa_save_settings', 'spa_settings_nonce'); ?>
            <input type="hidden" name="active_tab" value="<?php echo esc_attr($active_tab); ?>">

            <?php
            switch ($active_tab) {
                case 'email':
                    $notification_email = esc_attr( get_option('spa_notification_email', '') );
                    $enable_email = get_option('spa_enable_email', 0 );
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
                    </table>
                    <?php
                    break;

                case 'sms':
                    $sms_provider = esc_attr( get_option('spa_sms_provider', '') );
                    $enable_sms = get_option('spa_enable_sms', 0 );
                    ?>
                    <h2>SMS Settings</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="spa_sms_provider">SMS Provider</label></th>
                            <td><input name="spa_sms_provider" id="spa_sms_provider" type="text" value="<?php echo $sms_provider; ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row">Enable SMS</th>
                            <td><input name="spa_enable_sms" type="checkbox" value="1" <?php checked(1, $enable_sms); ?>></td>
                        </tr>
                    </table>
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