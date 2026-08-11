<?php include SPA_TEMPLATE_DIR . 'header.php'; ?>

<div class="spa-detail-card spa-services-page">
    <h2>Services</h2>
    <?php if ( isset($_GET['service_saved']) ) : ?>
        <div class="notice notice-success inline"><p>Service saved.</p></div>
    <?php endif; ?>
    <p>One service record may be attached to each active event.</p>

    <table class="widefat striped">
        <thead><tr><th>Event</th><th>Date</th><th>Service record</th><th></th></tr></thead>
        <tbody>
        <?php if ( empty($events) ) : ?>
            <tr><td colspan="4">No active events are available.</td></tr>
        <?php else : foreach ( $events as $event ) :
            $service = isset($services[$event->id]) ? $services[$event->id] : null; ?>
            <tr>
                <td><?php echo esc_html($event->name); ?></td>
                <td><?php echo esc_html(mysql2date(get_option('date_format'), $event->event_date)); ?></td>
                <td><?php echo $service ? '<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> Saved' : 'Not yet added'; ?></td>
                <td><a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=spa-services&service_id=' . ($service ? intval($service->id) : 0) . '&event_id=' . intval($event->id))); ?>"><?php echo $service ? 'Edit service' : 'Add service'; ?></a></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <?php
    $form_event_id = $edit_service ? intval($edit_service->event_id) : (isset($_GET['event_id']) ? intval($_GET['event_id']) : 0);
    ?>
    <?php if ( $form_event_id ) : ?>
        <?php
        $selected_event = null;
        foreach ( $events as $event ) {
            if ( intval($event->id) === $form_event_id ) {
                $selected_event = $event;
                break;
            }
        }
        ?>
        <?php if ( $selected_event ) : ?>
        <hr>
        <h3><?php echo $edit_service ? 'Edit service' : 'Add service'; ?>: <?php echo esc_html($selected_event->name); ?></h3>
        <form id="spa-service-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="spa_save_service">
            <input type="hidden" name="event_id" value="<?php echo intval($form_event_id); ?>">
            <input type="hidden" name="service_id" value="<?php echo $edit_service ? intval($edit_service->id) : 0; ?>">
            <?php wp_nonce_field('spa_save_service', 'spa_service_nonce'); ?>
            <table class="form-table">
                <tr><th><label for="sermon_text">Sermon full text</label></th><td><textarea name="sermon_text" id="sermon_text" rows="8" class="large-text"><?php echo $edit_service ? esc_textarea($edit_service->sermon_text) : ''; ?></textarea></td></tr>
                <tr><th><label for="sermon_file">Sermon file</label></th><td><input type="file" name="sermon_file" id="sermon_file" accept=".doc,.docx,.pdf,.rtf"><p class="description">Word, PDF, or RTF.</p><?php if ( $edit_service && $edit_service->sermon_file_url ) : ?><p>Current: <a href="<?php echo esc_url($edit_service->sermon_file_url); ?>">View sermon file</a></p><?php endif; ?></td></tr>
                <tr><th><label for="lessons">Scripture lessons</label></th><td><textarea name="lessons" id="lessons" rows="4" class="large-text"><?php foreach ( $lessons as $lesson ) { echo esc_textarea($lesson->reference . ($lesson->link_url ? ' | ' . $lesson->link_url : '') . "\n"); } ?></textarea><p class="description">One reference per line. Optionally append a link with <code>Reference | https://...</code>. Store references, not full lesson text.</p></td></tr>
                <tr><th><label for="bible_translation">Bible translation</label></th><td><select name="bible_translation" id="bible_translation"><option value="">Select translation</option><?php foreach ( spa_services_get_translation_choices() as $translation ) : ?><option value="<?php echo esc_attr($translation); ?>" <?php selected($edit_service ? $edit_service->bible_translation : '', $translation); ?>><?php echo esc_html($translation); ?></option><?php endforeach; ?></select></td></tr>
                <tr><th><label for="video_url">Video / livestream URL</label></th><td><input type="url" name="video_url" id="video_url" class="large-text" value="<?php echo $edit_service ? esc_attr($edit_service->video_url) : ''; ?>"></td></tr>
                <tr><th><label for="audio_file">Audio MP3</label></th><td><input type="file" name="audio_file" id="audio_file" accept=".mp3"><?php if ( $edit_service && $edit_service->audio_file_url ) : ?><p>Current: <a href="<?php echo esc_url($edit_service->audio_file_url); ?>">Listen to audio</a></p><?php endif; ?></td></tr>
                <tr><th><label for="bulletin_file">Service bulletin PDF</label></th><td><input type="file" name="bulletin_file" id="bulletin_file" accept=".pdf"><?php if ( $edit_service && $edit_service->bulletin_file_url ) : ?><p>Current: <a href="<?php echo esc_url($edit_service->bulletin_file_url); ?>">View bulletin</a></p><?php endif; ?></td></tr>
                <tr><th><label for="preacher">Preacher</label></th><td><input type="text" name="preacher" id="preacher" class="regular-text" value="<?php echo esc_attr($preacher_name); ?>"></td></tr>
                <tr><th><label for="series">Sermon series</label></th><td><input type="text" name="series" id="series" class="regular-text" value="<?php echo esc_attr($series_name); ?>"></td></tr>
                <tr><th><label for="tags">Tags</label></th><td><input type="text" name="tags" id="tags" class="large-text" value="<?php echo esc_attr(implode(', ', $tags)); ?>"><p class="description">Comma-separated.</p></td></tr>
                <tr><th><label for="featured_image">Featured image</label></th><td><input type="file" name="featured_image" id="featured_image" accept="image/*"><?php if ( $edit_service && $edit_service->featured_image_id ) : ?><p>Current: <a href="<?php echo esc_url(wp_get_attachment_url($edit_service->featured_image_id)); ?>">View image</a></p><?php endif; ?></td></tr>
                <tr><th>Active</th><td><label><input type="checkbox" name="active" value="1" <?php checked($edit_service ? intval($edit_service->active) : 1, 1); ?>> Include in future public service results</label></td></tr>
            </table>
            <p class="submit"><button type="submit" class="button button-primary">Save service</button></p>
        </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
