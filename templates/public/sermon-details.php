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
                <?php if ( current_user_can('edit_posts') ) : ?>
                    <a class="spa-sermon-edit-link" href="<?php echo esc_url(add_query_arg(array('page' => 'spa-services', 'service_id' => intval($service->id), 'event_id' => intval($service->event_id), 'spa_return_url' => esc_url_raw(home_url(wp_unslash($_SERVER['REQUEST_URI'])))), admin_url('admin.php'))); ?>">Edit</a>
                <?php endif; ?>
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
                                    <span class="spa-sermon-item-icon" aria-hidden="true">&#9835;</span>
                                    <span class="spa-sermon-item-content">
                                        <?php $hymn_video_url = spa_services_get_hymn_video_url($hymn); ?>
                                        <a href="<?php echo esc_url($hymn_video_url ?: $hymn->external_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($hymn->reference); ?></a>
                                        <?php if ( $hymn->title ) : ?> &mdash; <?php echo esc_html($hymn->title); ?><?php endif; ?>
                                        <?php if ( $hymn->author ) : ?><span>Author: <?php echo esc_html($hymn->author); ?></span><?php endif; ?>
                                        <?php if ( $hymn->tune ) : ?><span>Tune: <?php echo esc_html($hymn->tune); ?></span><?php endif; ?>
                                    </span>
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
                    <li>
                        <span class="spa-sermon-item-icon" aria-hidden="true">&#128214;</span>
                        <span class="spa-sermon-item-content"><?php echo spa_services_render_lesson_reference($lesson->reference, $service->bible_translation); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
        <?php if ( trim(wp_strip_all_tags($service->sermon_text)) !== '' ) : ?>
            <details class="spa-sermon-text">
                <summary>Read sermon text</summary>
                <div class="spa-sermon-text-content"><?php echo spa_services_render_sermon_text($service->sermon_text); ?></div>
            </details>
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
