<?php

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
