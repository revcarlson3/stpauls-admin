<section class="spa-sermons-archive">
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
