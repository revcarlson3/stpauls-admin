<?php include SPA_TEMPLATE_DIR .'header.php'; ?> 

<div class="spa-settings">

    <nav class="nav-tab-wrapper">

        spa-settings&tab=general"
            class="nav-tab <?php echo ($active_tab === 'general') ? 'nav-tab-active' : ''; ?>">
            General
        </a>

        ?page=spa-settings&tab=email="nav-tab <?php echo ($active_tab === 'email') ? 'nav-tab-active' : ''; ?>">
            Email
        </a>

        <a
            href="?page=

        ?page=spa-settings&tab=push-tab-active' : ''; ?>">
            Push
        </a>

        page=spa-settings&tab=templates"
            class="nav-tab <?php echo ($active_tab === 'templates') ? 'nav-tab-active' : ''; ?>">
            Templates
        </a>

    </nav>

    <div class="spa-settings-content">

        <?php

        switch ($active_tab) {

            case 'email':
                echo '<h2>Email Settings</h2>';
                break;

            case 'sms':
                echo '<h2>SMS Settings</h2>';
                break;

            case 'push':
                echo '<h2>Push Notification Settings</h2>';
                break;

            case 'templates':
                echo '<h2>Notification Templates</h2>';
                break;

            default:
                echo '<h2>General Settings</h2>';
                break;

        }

        ?>

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