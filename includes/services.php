<?php
/**
 * Service records attached to scheduled events.
 *
 * These tables intentionally do not use WordPress posts. They are a normalized
 * foundation for the public service API that can be added later.
 */

function spa_services_get_translation_choices() {
    return array('ESV', 'HCSB', 'KJV', 'NIV', 'EHV');
}

function spa_services_get_liturgy_choices() {
    return array(
        'Divine Service I',
        'Divine Service II',
        'Divine Service III',
        'Divine Service IV',
        'Divine Service V',
        'Morning Prayer',
        'Evening Prayer',
        'Matins',
        'Vespers',
        'Compline',
        'Service of Prayer and Preaching',
        'Corporate Confession and Absolution',
        'Individual Confession and Absolution',
        'Holy Baptism',
        'Confirmation',
        'Holy Matrimony',
        'Funeral Service',
    );
}

function spa_services_enqueue_reftagger($translation = '') {
    $translation = $translation ? sanitize_text_field($translation) : 'ESV';
    if ( ! in_array($translation, array('ESV', 'HCSB', 'KJV', 'NIV'), true) ) {
        $translation = 'ESV';
    }
    wp_register_script('spa-logos-reftagger', 'https://api.reftagger.com/v2/RefTagger.js', array(), null, true);
    wp_enqueue_script('spa-logos-reftagger');
    wp_add_inline_script(
        'spa-logos-reftagger',
        'var refTagger = { settings: { bibleVersion: ' . wp_json_encode($translation) . ' } };',
        'before'
    );
}

function spa_services_render_lesson_reference($reference, $translation = '') {
    $reference = sanitize_text_field($reference);
    $translation = sanitize_text_field($translation);
    if ( $reference === '' ) {
        return '';
    }
    if ( $translation === 'EHV' ) {
        $url = add_query_arg(
            array(
                'search' => $reference,
                'version' => 'EHV',
            ),
            'https://www.biblegateway.com/passage/'
        );
        return '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($reference) . '</a>';
    }
    spa_services_enqueue_reftagger($translation);
    return '<span class="rtBibleRef">' . esc_html($reference) . '</span>';
}

function spa_services_render_sermon_text($text) {
    return wpautop(wp_kses_post((string) $text));
}

function spa_services_render_video($url) {
    $url = esc_url_raw($url, array('http', 'https'));
    if ( $url === '' ) {
        return '';
    }
    if ( preg_match('/(?:youtube\.com|youtu\.be)/i', $url) ) {
        $url = add_query_arg(array('autoplay' => 0, 'controls' => 1, 'rel' => 0), $url);
    } elseif ( preg_match('/vimeo\.com/i', $url) ) {
        $url = add_query_arg(array('autoplay' => 0, 'title' => 0, 'byline' => 0), $url);
    }
    return wp_oembed_get($url, array('width' => 640));
}

function spa_services_get_details_url() {
    $details_page_id = absint(get_option('spa_sermon_details_page_id', 0));
    $details_page = $details_page_id ? get_post($details_page_id) : get_page_by_path('sermon-details');
    if ( ! $details_page || $details_page->post_type !== 'page' || $details_page->post_status !== 'publish' ) {
        $details_page = get_page_by_path('sermon-details');
    }
    return $details_page ? get_permalink($details_page) : home_url('/sermon-details/');
}

function spa_services_get_archive_url() {
    $archive_page_id = absint(get_option('spa_sermons_page_id', 0));
    $archive_page = $archive_page_id ? get_post($archive_page_id) : get_page_by_path('sermons');
    if ( ! $archive_page || $archive_page->post_type !== 'page' || $archive_page->post_status !== 'publish' ) {
        $archive_page = get_page_by_path('sermons');
    }
    return $archive_page ? get_permalink($archive_page) : home_url('/sermons/');
}

add_shortcode('spa_sermon_details', 'spa_services_sermon_details_shortcode');
add_shortcode('spa_sermons', 'spa_services_sermons_shortcode');

function spa_services_sermons_shortcode() {
    global $wpdb;

    $services = $wpdb->get_results(
        "SELECT s.*, e.name AS event_name, e.event_date, p.name AS preacher_name
         FROM {$wpdb->prefix}spa_services s
         INNER JOIN {$wpdb->prefix}spa_events e ON e.id = s.event_id
         LEFT JOIN {$wpdb->prefix}spa_preachers p ON p.id = s.preacher_id
         WHERE s.active = 1
           AND e.active = 1
         ORDER BY e.event_date DESC, e.start_time DESC, s.id DESC"
    );
    wp_enqueue_style('spa-public-services', SPA_PLUGIN_URL . 'css/spa_services.css', array(), SPA_VERSION);

    if ( ! $services ) {
        return '<p class="spa-sermon-empty">No sermons are currently available.</p>';
    }

    $details_url = spa_services_get_details_url();
    ob_start();
    ?>
    <section class="spa-sermons-archive" aria-labelledby="spa-sermons-heading">
        <h2 id="spa-sermons-heading">Sermons</h2>
        <div class="spa-related-sermon-grid">
            <?php foreach ( $services as $service ) :
                $title = $service->sermon_title ? $service->sermon_title : $service->event_name;
                $excerpt = trim(wp_strip_all_tags($service->sermon_text));
                if ( function_exists('mb_substr') ) {
                    $excerpt = mb_substr($excerpt, 0, 350);
                } else {
                    $excerpt = substr($excerpt, 0, 350);
                }
                if ( strlen(wp_strip_all_tags($service->sermon_text)) > 350 ) {
                    $excerpt .= '...';
                }
                ?>
                <a class="spa-related-sermon-card" href="<?php echo esc_url(add_query_arg('service_id', intval($service->id), $details_url)); ?>">
                    <?php if ( $service->featured_image_id ) : ?>
                        <div class="spa-related-sermon-image"><?php echo wp_get_attachment_image($service->featured_image_id, 'medium_large', false, array('loading' => 'lazy')); ?></div>
                    <?php endif; ?>
                    <div class="spa-related-sermon-card-content">
                        <h3><?php echo esc_html($title); ?></h3>
                        <time datetime="<?php echo esc_attr($service->event_date); ?>"><?php echo esc_html(mysql2date(get_option('date_format'), $service->event_date)); ?></time>
                        <?php if ( $excerpt ) : ?><p><?php echo esc_html($excerpt); ?></p><?php endif; ?>
                        <?php if ( $service->preacher_name ) : ?><span>Preacher: <?php echo esc_html($service->preacher_name); ?></span><?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function spa_services_sermon_details_shortcode($atts) {
    global $wpdb;

    $atts = shortcode_atts(array('service_id' => 0), $atts, 'spa_sermon_details');
    $service_id = intval($atts['service_id']);
    if ( ! $service_id && isset($_GET['service_id']) ) {
        $service_id = absint($_GET['service_id']);
    }
    $service_filter = $service_id ? $wpdb->prepare('AND s.id = %d', $service_id) : '';
    $service = $wpdb->get_row(
        "SELECT s.*, e.name AS event_name, e.event_date, e.start_time,
                p.name AS preacher_name, ss.name AS series_name
         FROM {$wpdb->prefix}spa_services s
         INNER JOIN {$wpdb->prefix}spa_events e ON e.id = s.event_id
         LEFT JOIN {$wpdb->prefix}spa_preachers p ON p.id = s.preacher_id
         LEFT JOIN {$wpdb->prefix}spa_sermon_series ss ON ss.id = s.series_id
         WHERE s.active = 1
           AND e.active = 1
           {$service_filter}
         ORDER BY e.event_date DESC, e.start_time DESC, s.id DESC
         LIMIT 1"
    );

    if ( ! $service ) {
        return '<p class="spa-sermon-empty">No sermon is currently available.</p>';
    }

    wp_enqueue_style('spa-public-services', SPA_PLUGIN_URL . 'css/spa_services.css', array(), SPA_VERSION);

    $lessons = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT reference FROM {$wpdb->prefix}spa_service_lessons
             WHERE service_id = %d
             ORDER BY lesson_order, id",
            $service->id
        )
    );
    $hymns = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}spa_service_hymns WHERE service_id = %d ORDER BY hymn_order, id",
        $service->id
    ));
    $related_services = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT s.*, e.name AS event_name, e.event_date, p.name AS preacher_name,
                    COUNT(DISTINCT matching_rel.tag_id) AS matching_tags
             FROM {$wpdb->prefix}spa_services s
             INNER JOIN {$wpdb->prefix}spa_events e ON e.id = s.event_id
             LEFT JOIN {$wpdb->prefix}spa_preachers p ON p.id = s.preacher_id
             INNER JOIN {$wpdb->prefix}spa_service_tag_relationships matching_rel
                ON matching_rel.service_id = s.id
             INNER JOIN {$wpdb->prefix}spa_service_tag_relationships current_rel
                ON current_rel.tag_id = matching_rel.tag_id
               AND current_rel.service_id = %d
             WHERE s.active = 1
               AND e.active = 1
               AND s.id <> %d
             GROUP BY s.id
             ORDER BY matching_tags DESC, e.event_date DESC, s.id DESC
             LIMIT 3",
            $service->id,
            $service->id
        )
    );

    $download_links = array();
    if ( $service->sermon_file_url ) {
        $download_links[] = '<a href="' . esc_url($service->sermon_file_url) . '" download>Download sermon</a>';
    }
    if ( $service->audio_file_url ) {
        $download_links[] = '<a href="' . esc_url($service->audio_file_url) . '" download>Download audio</a>';
    }
    if ( $service->bulletin_file_url ) {
        $download_links[] = '<a href="' . esc_url($service->bulletin_file_url) . '" target="_blank" rel="noopener noreferrer">View bulletin</a>';
    }
    $video_embed = $service->video_url ? spa_services_render_video($service->video_url) : '';
    $sermons_url = spa_services_get_archive_url();

    ob_start();
    ?>
    <article class="spa-latest-sermon">
        <?php if ( $service->featured_image_id ) : ?>
            <div class="spa-sermon-image"><?php echo wp_get_attachment_image($service->featured_image_id, 'large', false, array('loading' => 'lazy')); ?></div>
        <?php endif; ?>
        <div class="spa-sermon-content">
            <div class="spa-sermon-header">
                <div class="spa-sermon-details">
                    <nav class="spa-sermon-breadcrumbs" aria-label="Breadcrumb">
                        <a href="<?php echo esc_url($sermons_url); ?>">Sermons</a>
                        <span aria-hidden="true">/</span>
                        <span><?php echo esc_html($service->sermon_title ? $service->sermon_title : $service->event_name); ?></span>
                    </nav>
                    <h2><?php echo esc_html($service->sermon_title ? $service->sermon_title : $service->event_name); ?></h2>
                    <p class="spa-sermon-date"><?php echo esc_html(mysql2date(get_option('date_format'), $service->event_date)); ?></p>
                    <?php if ( $service->preacher_name || $service->series_name ) : ?>
                        <p class="spa-sermon-meta">
                            <?php if ( $service->preacher_name ) : ?>Preacher: <?php echo esc_html($service->preacher_name); ?><br><?php endif; ?>
                            <?php if ( $service->series_name ) : ?>Series: <?php echo esc_html($service->series_name); ?><?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ( $service->liturgy ) : ?>
                        <section class="spa-sermon-liturgy">
                            <h3>Order of service</h3>
                            <?php if ( $service->bulletin_file_url ) : ?>
                                <a href="<?php echo esc_url($service->bulletin_file_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($service->liturgy); ?></a>
                            <?php else : ?>
                                <span><?php echo esc_html($service->liturgy); ?></span>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                    <?php if ( $hymns ) : ?>
                        <section class="spa-sermon-hymns">
                            <h3>Hymns</h3>
                            <ul>
                                <?php foreach ( $hymns as $hymn ) : ?>
                                    <li>
                                        <a href="<?php echo esc_url($hymn->external_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($hymn->reference); ?></a>
                                        <?php if ( $hymn->title ) : ?> &mdash; <?php echo esc_html($hymn->title); ?><?php endif; ?>
                                        <?php if ( $hymn->author ) : ?><span>Author: <?php echo esc_html($hymn->author); ?></span><?php endif; ?>
                                        <?php if ( $hymn->tune ) : ?><span>Tune: <?php echo esc_html($hymn->tune); ?></span><?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endif; ?>
                </div>
                <?php if ( $video_embed ) : ?>
                    <div class="spa-sermon-video"><?php echo $video_embed; ?></div>
                <?php endif; ?>
            </div>
            <?php if ( $lessons ) : ?>
                <section class="spa-sermon-lessons">
                    <h3>Scripture lessons</h3>
                    <ul>
                        <?php foreach ( $lessons as $lesson ) : ?>
                            <li><?php echo spa_services_render_lesson_reference($lesson->reference, $service->bible_translation); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
            <?php if ( trim(wp_strip_all_tags($service->sermon_text)) !== '' ) : ?>
                <div class="spa-sermon-text"><?php echo spa_services_render_sermon_text($service->sermon_text); ?></div>
            <?php endif; ?>
            <?php if ( $service->video_url || $download_links ) : ?>
                <div class="spa-sermon-actions">
                    <?php foreach ( $download_links as $download_link ) : ?><span><?php echo $download_link; ?></span><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ( $service->audio_file_url ) : ?>
                <audio class="spa-sermon-audio" controls preload="metadata" src="<?php echo esc_url($service->audio_file_url); ?>"></audio>
            <?php endif; ?>
        </div>
    </article>
    <?php if ( $related_services ) : ?>
        <section class="spa-related-sermons">
            <h3>Related sermons</h3>
            <div class="spa-related-sermon-grid">
                <?php foreach ( $related_services as $related ) :
                    $related_title = $related->sermon_title ? $related->sermon_title : $related->event_name;
                    $related_excerpt = trim(wp_strip_all_tags($related->sermon_text));
                    if ( function_exists('mb_substr') ) {
                        $related_excerpt = mb_substr($related_excerpt, 0, 350);
                    } else {
                        $related_excerpt = substr($related_excerpt, 0, 350);
                    }
                    if ( strlen(wp_strip_all_tags($related->sermon_text)) > 350 ) {
                        $related_excerpt .= '...';
                    }
                    $related_url = add_query_arg('service_id', intval($related->id), get_permalink());
                    ?>
                    <a class="spa-related-sermon-card" href="<?php echo esc_url($related_url); ?>">
                        <?php if ( $related->featured_image_id ) : ?>
                            <div class="spa-related-sermon-image"><?php echo wp_get_attachment_image($related->featured_image_id, 'medium_large', false, array('loading' => 'lazy')); ?></div>
                        <?php endif; ?>
                        <div class="spa-related-sermon-card-content">
                            <h4><?php echo esc_html($related_title); ?></h4>
                            <time datetime="<?php echo esc_attr($related->event_date); ?>"><?php echo esc_html(mysql2date(get_option('date_format'), $related->event_date)); ?></time>
                            <?php if ( $related_excerpt ) : ?><p><?php echo esc_html($related_excerpt); ?></p><?php endif; ?>
                            <?php if ( $related->preacher_name ) : ?><span>Preacher: <?php echo esc_html($related->preacher_name); ?></span><?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

function spa_services_get_service($service_id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}spa_services WHERE id = %d",
        intval($service_id)
    ));
}

function spa_services_normalize_name($value) {
    return sanitize_text_field(wp_unslash((string) $value));
}

function spa_services_get_or_create_reference($table_suffix, $name) {
    global $wpdb;
    $name = spa_services_normalize_name($name);
    if ( $name === '' ) {
        return 0;
    }
    $table = $wpdb->prefix . $table_suffix;
    $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE name = %s LIMIT 1", $name));
    if ( $id ) {
        return intval($id);
    }
    $wpdb->insert(
        $table,
        array('name' => $name, 'active' => 1),
        array('%s', '%d')
    );
    return intval($wpdb->insert_id);
}

function spa_services_validate_upload($field, $allowed) {
    if ( empty($_FILES[$field]) || ! isset($_FILES[$field]['error']) || intval($_FILES[$field]['error']) === UPLOAD_ERR_NO_FILE ) {
        return 0;
    }
    $file = $_FILES[$field];
    if ( intval($file['error']) !== UPLOAD_ERR_OK || empty($file['tmp_name']) || ! is_uploaded_file($file['tmp_name']) ) {
        return new WP_Error('invalid_upload', sprintf('The %s upload failed.', $field));
    }
    $name = sanitize_file_name($file['name']);
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ( ! isset($allowed[$extension]) ) {
        return new WP_Error('invalid_upload_type', sprintf('The %s file type is not allowed.', $field));
    }
    $filetype = wp_check_filetype_and_ext($file['tmp_name'], $name, $allowed);
    if ( empty($filetype['ext']) || empty($filetype['type']) || strtolower($filetype['ext']) !== $extension ) {
        return new WP_Error('invalid_upload_type', sprintf('The %s file type could not be verified.', $field));
    }
    return 1;
}

function spa_services_handle_upload($field, $allowed) {
    $valid = spa_services_validate_upload($field, $allowed);
    if ( is_wp_error($valid) || ! $valid ) {
        return $valid;
    }
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attachment_id = media_handle_upload(
        $field,
        0,
        array(),
        array('test_form' => false, 'mimes' => $allowed)
    );
    return is_wp_error($attachment_id) ? $attachment_id : intval($attachment_id);
}

function spa_services_parse_lessons($raw) {
    $lessons = array();
    foreach ( preg_split('/\r\n|\r|\n/', (string) $raw) as $line ) {
        $line = trim($line);
        if ( $line === '' ) {
            continue;
        }
        $reference = sanitize_text_field($line);
        if ( $reference !== '' ) {
            $lessons[] = array('reference' => $reference, 'link_url' => '');
        }
    }
    return $lessons;
}

function spa_services_parse_hymns($raw) {
    $hymns = array();
    foreach ( preg_split('/\r\n|\r|\n/', (string) $raw) as $order => $line ) {
        $parts = array_map('trim', explode('|', $line));
        $reference = strtoupper(sanitize_text_field($parts[0]));
        if ( ! preg_match('/^([A-Z0-9]+)\s+([0-9A-Za-z-]+)$/', $reference, $matches) ) {
            continue;
        }
        $hymnal = $matches[1];
        $number = $matches[2];
        $hymns[] = array(
            'hymnal' => $hymnal,
            'hymn_number' => $number,
            'reference' => $reference,
            'title' => isset($parts[1]) ? sanitize_text_field($parts[1]) : '',
            'author' => isset($parts[2]) ? sanitize_text_field($parts[2]) : '',
            'tune' => isset($parts[3]) ? sanitize_text_field($parts[3]) : '',
            'external_url' => 'https://hymnary.org/hymn/' . rawurlencode($hymnal) . '/' . rawurlencode($number),
            'hymn_order' => $order,
        );
    }
    return $hymns;
}

function spa_services_parse_tags($raw) {
    $tags = array();
    foreach ( explode(',', (string) $raw) as $tag ) {
        $tag = sanitize_text_field(trim($tag));
        if ( $tag !== '' && strlen($tag) <= 100 && ! in_array($tag, $tags, true) ) {
            $tags[] = $tag;
        }
    }
    return $tags;
}

function spa_services_save_record() {
    global $wpdb;
    if ( ! current_user_can('edit_posts') ) {
        wp_die('Unauthorized', 'Error', array('response' => 403));
    }
    if ( ! isset($_POST['spa_service_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['spa_service_nonce']), 'spa_save_service') ) {
        wp_die('Invalid nonce', 'Error', array('response' => 403));
    }

    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $event = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}spa_events WHERE id = %d AND active = 1",
        $event_id
    ));
    if ( ! $event ) {
        wp_die('Select a valid active event.', 'Service Error', array('response' => 400));
    }

    $existing = $service_id ? spa_services_get_service($service_id) : null;
    if ( $service_id && ( ! $existing || intval($existing->event_id) !== $event_id ) ) {
        wp_die('The service record could not be found.', 'Service Error', array('response' => 404));
    }
    $duplicate = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}spa_services WHERE event_id = %d AND id <> %d",
        $event_id,
        $service_id
    ));
    if ( $duplicate ) {
        wp_die('This event already has a service record.', 'Service Error', array('response' => 409));
    }

    $sermon_text = isset($_POST['sermon_text']) ? wp_kses_post(wp_unslash($_POST['sermon_text'])) : '';
    $sermon_title = isset($_POST['sermon_title']) ? sanitize_text_field(wp_unslash($_POST['sermon_title'])) : '';
    $liturgy = isset($_POST['liturgy']) ? sanitize_text_field(wp_unslash($_POST['liturgy'])) : '';
    if ( ! in_array($liturgy, spa_services_get_liturgy_choices(), true) ) {
        $liturgy = '';
    }
    $translation = isset($_POST['bible_translation']) ? sanitize_text_field(wp_unslash($_POST['bible_translation'])) : '';
    if ( ! in_array($translation, spa_services_get_translation_choices(), true) ) {
        $translation = '';
    }
    $video_url = isset($_POST['video_url']) ? esc_url_raw(wp_unslash($_POST['video_url']), array('http', 'https')) : '';
    $preacher_id = spa_services_get_or_create_reference('spa_preachers', $_POST['preacher'] ?? '');
    $series_id = spa_services_get_or_create_reference('spa_sermon_series', $_POST['series'] ?? '');
    if ( ! $preacher_id && $existing ) {
        $preacher_id = intval($existing->preacher_id);
    }
    if ( ! $series_id && $existing ) {
        $series_id = intval($existing->series_id);
    }

    $allowed_sermon = array(
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'pdf' => 'application/pdf',
        'rtf' => 'application/rtf',
    );
    $allowed_audio = array('mp3' => 'audio/mpeg');
    $allowed_bulletin = array('pdf' => 'application/pdf');
    $upload_rules = array(
        'sermon_file' => $allowed_sermon,
        'audio_file' => $allowed_audio,
        'bulletin_file' => $allowed_bulletin,
    );
    foreach ( $upload_rules as $field => $allowed ) {
        $validation = spa_services_validate_upload($field, $allowed);
        if ( is_wp_error($validation) ) {
            wp_die(esc_html($validation->get_error_message()), 'Upload Error', array('response' => 400));
        }
    }
    $uploads = array();
    foreach ( $upload_rules as $field => $allowed ) {
        $uploads[$field] = spa_services_handle_upload($field, $allowed);
        if ( is_wp_error($uploads[$field]) ) {
            wp_die(esc_html($uploads[$field]->get_error_message()), 'Upload Error', array('response' => 400));
        }
    }

    $sermon_file_id = $uploads['sermon_file'] ? intval($uploads['sermon_file']) : ($existing ? intval($existing->sermon_file_id) : 0);
    $audio_file_id = $uploads['audio_file'] ? intval($uploads['audio_file']) : ($existing ? intval($existing->audio_file_id) : 0);
    $bulletin_file_id = $uploads['bulletin_file'] ? intval($uploads['bulletin_file']) : ($existing ? intval($existing->bulletin_file_id) : 0);
    if ( trim(wp_strip_all_tags($sermon_text)) === '' && ! $sermon_file_id ) {
        wp_die('Add sermon text or upload a sermon file.', 'Service Error', array('response' => 400));
    }
    $data = array(
        'event_id' => $event_id,
        'sermon_title' => $sermon_title,
        'liturgy' => $liturgy,
        'sermon_text' => $sermon_text,
        'sermon_file_id' => $sermon_file_id ?: null,
        'sermon_file_url' => $sermon_file_id ? wp_get_attachment_url($sermon_file_id) : '',
        'bible_translation' => $translation,
        'video_url' => $video_url,
        'audio_file_id' => $audio_file_id ?: null,
        'audio_file_url' => $audio_file_id ? wp_get_attachment_url($audio_file_id) : '',
        'bulletin_file_id' => $bulletin_file_id ?: null,
        'bulletin_file_url' => $bulletin_file_id ? wp_get_attachment_url($bulletin_file_id) : '',
        'preacher_id' => $preacher_id ?: null,
        'series_id' => $series_id ?: null,
        'featured_image_id' => $existing ? intval($existing->featured_image_id) : 0,
        'active' => isset($_POST['active']) ? 1 : 0,
        'created_by' => $existing ? intval($existing->created_by) : get_current_user_id(),
    );
    $formats = array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%d', '%d', '%d', '%d', '%d');
    if ( ! empty($_FILES['featured_image']['name']) ) {
        $image_id = spa_services_handle_upload('featured_image', array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpe' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ));
        if ( is_wp_error($image_id) ) {
            wp_die(esc_html($image_id->get_error_message()), 'Upload Error', array('response' => 400));
        }
        $data['featured_image_id'] = intval($image_id);
        $formats[14] = '%d';
    }

    if ( $existing ) {
        $wpdb->update($wpdb->prefix . 'spa_services', $data, array('id' => $service_id), $formats, array('%d'));
    } else {
        $wpdb->insert($wpdb->prefix . 'spa_services', $data, $formats);
        $service_id = intval($wpdb->insert_id);
    }
    if ( ! $service_id || $wpdb->last_error ) {
        wp_die('The service could not be saved.', 'Service Error', array('response' => 500));
    }

    $lessons_table = $wpdb->prefix . 'spa_service_lessons';
    $wpdb->delete($lessons_table, array('service_id' => $service_id), array('%d'));
    $lessons = spa_services_parse_lessons($_POST['lessons'] ?? '');
    foreach ( $lessons as $order => $lesson ) {
        $wpdb->insert($lessons_table, array(
            'service_id' => $service_id,
            'reference' => $lesson['reference'],
            'link_url' => $lesson['link_url'],
            'lesson_order' => $order,
        ), array('%d', '%s', '%s', '%d'));
    }

    $hymns_table = $wpdb->prefix . 'spa_service_hymns';
    $wpdb->delete($hymns_table, array('service_id' => $service_id), array('%d'));
    foreach ( spa_services_parse_hymns($_POST['hymns'] ?? '') as $hymn ) {
        $wpdb->insert($hymns_table, array(
            'service_id' => $service_id,
            'hymnal' => $hymn['hymnal'],
            'hymn_number' => $hymn['hymn_number'],
            'reference' => $hymn['reference'],
            'title' => $hymn['title'],
            'author' => $hymn['author'],
            'tune' => $hymn['tune'],
            'external_url' => $hymn['external_url'],
            'hymn_order' => $hymn['hymn_order'],
        ), array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d'));
    }

    $rel_table = $wpdb->prefix . 'spa_service_tag_relationships';
    $tags_table = $wpdb->prefix . 'spa_service_tags';
    $wpdb->delete($rel_table, array('service_id' => $service_id), array('%d'));
    foreach ( spa_services_parse_tags($_POST['tags'] ?? '') as $tag_name ) {
        $slug = sanitize_title($tag_name);
        if ( $slug === '' ) {
            continue;
        }
        $tag_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$tags_table} WHERE slug = %s", $slug));
        if ( ! $tag_id ) {
            $wpdb->insert($tags_table, array('name' => $tag_name, 'slug' => $slug), array('%s', '%s'));
            $tag_id = $wpdb->insert_id;
        }
        if ( $tag_id ) {
            $wpdb->insert($rel_table, array('service_id' => $service_id, 'tag_id' => intval($tag_id)), array('%d', '%d'));
        }
    }
    wp_safe_redirect(admin_url('admin.php?page=spa-services&service_saved=1'));
    exit;
}
add_action('admin_post_spa_save_service', 'spa_services_save_record');

function spa_services_page() {
    if ( ! current_user_can('edit_posts') ) {
        wp_die('You do not have permission to manage services.', 'Unauthorized', array('response' => 403));
    }
    global $wpdb;
    $page_title = 'Services';
    $events = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT e.* FROM {$wpdb->prefix}spa_events e
             WHERE e.active = 1
             AND e.event_date >= %s
             ORDER BY e.event_date, e.start_time, e.id",
            current_time('Y-m-d')
        )
    );
    $service_rows = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}spa_services ORDER BY event_id");
    $services = array();
    foreach ( $service_rows as $service_row ) {
        $services[intval($service_row->event_id)] = $service_row;
    }
    $edit_service = isset($_GET['service_id']) ? spa_services_get_service(intval($_GET['service_id'])) : null;
    $preacher_name = '';
    $series_name = '';
    $lessons = array();
    $hymns = array();
    $tags = array();
    if ( $edit_service ) {
        $preacher_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}spa_preachers WHERE id = %d", $edit_service->preacher_id));
        $series_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}spa_sermon_series WHERE id = %d", $edit_service->series_id));
        $lessons = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}spa_service_lessons WHERE service_id = %d ORDER BY lesson_order", $edit_service->id));
        $hymns = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}spa_service_hymns WHERE service_id = %d ORDER BY hymn_order, id", $edit_service->id));
        $tags = $wpdb->get_col($wpdb->prepare(
            "SELECT t.name FROM {$wpdb->prefix}spa_service_tags t
             INNER JOIN {$wpdb->prefix}spa_service_tag_relationships r ON r.tag_id = t.id
             WHERE r.service_id = %d ORDER BY t.name", $edit_service->id
        ));
    }
    include SPA_TEMPLATE_DIR . 'services-page.php';
}
