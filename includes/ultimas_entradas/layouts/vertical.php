
<div class="<?php echo esc_attr($classes); ?>" style="<?php echo esc_attr($style); ?>" role="list">

<?php while ($posts->have_posts()) : $posts->the_post(); ?>

    <?php
    $post_id = get_the_ID();
    $title   = get_the_title();
    $url     = get_permalink();
    $thumb   = esquina_mf_ultimas_entradas_thumb_url($post_id, $img_w, $img_h);

    if (!$thumb) {
        $thumb = sprintf(
            'https://via.placeholder.com/%1$dx%2$d?text=%3$s',
            $img_w,
            $img_h,
            rawurlencode(wp_trim_words($title, 3, '…'))
        );
    }
    ?>

    <div class="esquina-ultimas__item" role="listitem">
        <a class="esquina-ultimas__link" href="<?php echo esc_url($url); ?>">

            <span class="esquina-ultimas__thumb">
                <img
                    src="<?php echo esc_url($thumb); ?>"
                    alt="<?php echo esc_attr($title); ?>"
                    width="<?php echo esc_attr($img_w); ?>"
                    height="<?php echo esc_attr($img_h); ?>"
                    loading="lazy"
                    decoding="async"
                />
            </span>

            <span
                class="esquina-ultimas__title"
                <?php echo $title_color ? 'style="color:' . esc_attr($title_color) . ';"' : ''; ?>
            >
                <?php echo esc_html($title); ?>
            </span>

        </a>
    </div>

<?php endwhile; ?>

</div>

