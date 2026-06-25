<div class="esquina-grid-2" style="--columns:<?php echo esc_attr($columns); ?>">

<?php while ($posts->have_posts()) : $posts->the_post(); ?>

<article class="esquina-grid2-card">

    <a href="<?php the_permalink(); ?>" class="esquina-grid2-image">

        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('large', ['loading' => 'lazy']); ?>
        <?php endif; ?>

    </a>

    <section class="esquina-grid2-content">

        <h3>
            <a href="<?php the_permalink(); ?>">
                <?php the_title(); ?>
            </a>
        </h3>

        <p class="esquina-grid2-excerpt">
            <?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?>
        </p>

        <div class="esquina-grid2-footer">

            <span class="esquina-grid2-date">
                <?php echo get_the_date(); ?>
            </span>

            <a href="<?php the_permalink(); ?>" class="esquina-grid2-button">
                Leer más
            </a>

        </div>

    </section>

</article>

<?php endwhile; ?>

</div>