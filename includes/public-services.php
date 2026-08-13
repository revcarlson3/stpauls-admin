<?php

add_shortcode('spa_sermon_details', 'spa_services_sermon_details_shortcode');
add_shortcode('spa_sermons', 'spa_services_sermons_shortcode');

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

function spa_services_sermons_shortcode() {
    $services = spa_public_get_sermons();
    wp_enqueue_style('spa-public-services', SPA_PLUGIN_URL . 'css/spa_services.css', array(), SPA_VERSION);

    if ( ! $services ) {
        return '<p class="spa-sermon-empty">No sermons are currently available.</p>';
    }

    $details_url = spa_services_get_details_url();
    ob_start();
    include SPA_TEMPLATE_DIR . 'public/sermons.php';
    return ob_get_clean();
}

function spa_services_sermon_details_shortcode($atts) {
    $atts = shortcode_atts(array('service_id' => 0), $atts, 'spa_sermon_details');
    $service_id = intval($atts['service_id']);
    if ( ! $service_id && isset($_GET['service_id']) ) {
        $service_id = absint($_GET['service_id']);
    }

    $service = spa_public_get_sermon($service_id);
    if ( ! $service ) {
        return '<p class="spa-sermon-empty">No sermon is currently available.</p>';
    }

    wp_enqueue_style('spa-public-services', SPA_PLUGIN_URL . 'css/spa_services.css', array(), SPA_VERSION);

    $lessons = spa_public_get_sermon_lessons($service->id);
    $hymns = spa_public_get_sermon_hymns($service->id);
    $related_services = spa_public_get_related_sermons($service->id);
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
    include SPA_TEMPLATE_DIR . 'public/sermon-details.php';
    return ob_get_clean();
}

function spa_services_get_hymn_video_url($hymn) {
    $api_key = trim((string) get_option('spa_youtube_api_key', ''));
    if ( $api_key === '' ) {
        return '';
    }

    $query = trim(implode(' ', array_filter(array(
        $hymn->reference,
        $hymn->title,
        'hymn',
    ))));
    if ( $query === '' ) {
        return '';
    }
    $preferred_channel = sanitize_text_field(get_option('spa_youtube_preferred_channel_id', ''));
    $secondary_channel = sanitize_text_field(get_option('spa_youtube_secondary_channel_id', ''));
    $cache_version = get_option('spa_youtube_cache_version', '1');
    $cache_key = 'spa_hymn_video_v5_' . md5(strtolower($query) . '|' . $api_key . '|' . $preferred_channel . '|' . $secondary_channel . '|' . $cache_version);
    $cached = get_transient($cache_key);
    if ( $cached !== false ) {
        return is_string($cached) ? $cached : '';
    }

    $channels = array_values(array_unique(array($preferred_channel, $secondary_channel, '')));
    foreach ( $channels as $channel_id ) {
        $request_args = array(
            'part' => 'snippet',
            'maxResults' => 1,
            'type' => 'video',
            'q' => $query,
            'key' => $api_key,
        );
        if ( $channel_id !== '' ) {
            $request_args['channelId'] = $channel_id;
        }
        $response = wp_remote_get(add_query_arg($request_args, 'https://www.googleapis.com/youtube/v3/search'), array('timeout' => 5));
        if ( is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200 ) {
            $error_message = is_wp_error($response) ? $response->get_error_message() : 'YouTube API returned HTTP ' . wp_remote_retrieve_response_code($response) . '.';
            if ( ! is_wp_error($response) ) {
                $error_body = json_decode(wp_remote_retrieve_body($response), true);
                if ( ! empty($error_body['error']['message']) ) {
                    $error_message = sanitize_text_field($error_body['error']['message']);
                }
            }
            set_transient('spa_youtube_last_error', $error_message, HOUR_IN_SECONDS);
            continue;
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $video_id = isset($body['items'][0]['id']['videoId']) ? sanitize_text_field($body['items'][0]['id']['videoId']) : '';
        if ( $video_id !== '' ) {
            delete_transient('spa_youtube_last_error');
            $url = 'https://www.youtube.com/watch?v=' . rawurlencode($video_id);
            set_transient($cache_key, $url, 30 * DAY_IN_SECONDS);
            return $url;
        }
    }
    set_transient($cache_key, '', MINUTE_IN_SECONDS * 5);
    return '';
}
