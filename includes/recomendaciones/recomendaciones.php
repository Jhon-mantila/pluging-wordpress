<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Assets
|--------------------------------------------------------------------------
*/

function esquina_recomendaciones_assets()
{
    wp_enqueue_style(
        'esquina-recomendaciones',
        plugin_dir_url(__FILE__) . 'assets/recomendaciones.css',
        [],
        ESQUINA_MF_VERSION
    );

    wp_enqueue_script(
        'esquina-recomendaciones',
        plugin_dir_url(__FILE__) . 'assets/recomendaciones.js',
        ['jquery'],
        ESQUINA_MF_VERSION,
        true
    );

    $settings = get_option('esquina_recomendaciones_settings', []);

    wp_localize_script(
        'esquina-recomendaciones',
        'esquinaRecSettings',
        [
            'autoplay' => !empty($settings['autoplay']),
            'speed' => intval($settings['speed'] ?? 5000),
        ]
    );
}
add_action('wp_enqueue_scripts', 'esquina_recomendaciones_assets');

/*
|--------------------------------------------------------------------------
| Mostrar recomendaciones
|--------------------------------------------------------------------------
*/

function esquina_recomendaciones_content($content)
{
    if (!is_single()) {
        return $content;
    }

    $settings = get_option('esquina_recomendaciones_settings');

    if (empty($settings['enabled'])) {
        return $content;
    }
    // Diseño
    $layout = $settings['layout'] ?? 'grid';
    $size = $settings['size'] ?? 'medium';
    $title_mode = $settings['title_mode'] ?? 'clamp';
    $title_class = $title_mode === 'wrap' ? 'esquina-title-wrap' : 'esquina-title-clamp';
    // Categoría
    $categorias = get_the_category();

    if (empty($categorias)) {
        return $content;
    }

    $categoria = $categorias[0];

    $args = [
        'post_type' => 'post',
        'posts_per_page' => intval($settings['posts_per_page'] ?? 6),
        'cat' => $categoria->term_id,
        'post__not_in' => [get_the_ID()]
    ];

    if (($settings['mode'] ?? '') === 'random') {
        $args['orderby'] = 'rand';
    } else {
        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
    }

    $query = new WP_Query($args);

    if (!$query->have_posts()) {
        return $content;
    }

    ob_start();

    ?>

    <section class="esquina-recomendaciones <?php echo esc_attr($title_class); ?> <?php echo $layout === 'carousel' ? 'esquina-size-' . esc_attr($size) : ''; ?>">

        <h2>
            <?php
            echo esc_html(
                ($settings['title'] ?? 'Recomendaciones')
                . ' de '
                . $categoria->name
            );
            ?>
        </h2>

        <?php if ($layout === 'carousel') : ?>

            <div class="esquina-carousel-wrapper">

                <div class="esquina-carousel-viewport">

                    <button type="button" class="esquina-nav esquina-prev" aria-label="Anterior">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                    </button>

                    <div class="esquina-carousel">

            <?php else : ?>

            <div class="esquina-grid">

            <?php endif; ?>

            <?php while ($query->have_posts()) : $query->the_post(); ?>

                <article class="esquina-card">

                    <a href="<?php the_permalink(); ?>">

                        <?php
                        if (!empty($settings['thumbnail']) && has_post_thumbnail())
                        {
                            the_post_thumbnail('medium');
                        }
                        ?>

                        <h3><?php the_title(); ?></h3>

                    </a>

                </article>

            <?php endwhile; ?>

            <?php if ($layout === 'carousel') : ?>

                    </div>

                    <button type="button" class="esquina-nav esquina-next" aria-label="Siguiente">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                    </button>

                </div>

            </div>

                <?php else : ?>

                </div>

                <?php endif; ?>

        <?php

        if (!empty($settings['category_link']))
        {
            ?>

            <div class="esquina-category-link">

                <a href="<?php echo get_category_link($categoria->term_id); ?>">

                    Ver más artículos de
                    <?php echo esc_html($categoria->name); ?>

                </a>

            </div>

            <?php
        }

        ?>

    </section>

    <?php

    wp_reset_postdata();

    return $content . ob_get_clean();
}

add_filter(
    'the_content',
    'esquina_recomendaciones_content'
);