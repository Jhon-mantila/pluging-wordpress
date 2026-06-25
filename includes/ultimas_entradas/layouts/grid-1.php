<div class="esquina-grid-1" style="--columns:<?php echo esc_attr($columns); ?>">

<?php while ($posts->have_posts()) : $posts->the_post(); ?>

    <article class="esquina-grid1-card">

        <a href="<?php the_permalink(); ?>" class="esquina-grid1-image">

            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('large', ['loading' => 'lazy']); ?>
            <?php endif; ?>

            <span class="esquina-grid1-category">
                <?php
                $cats = get_the_category();
                echo !empty($cats) ? esc_html($cats[0]->name) : '';
                ?>
            </span>

        </a>

        <div class="esquina-grid1-content">

            <h3>
                <a href="<?php the_permalink(); ?>">
                    <?php the_title(); ?>
                </a>
            </h3>

            <p class="esquina-grid1-excerpt">
                <?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?>
            </p>

            <div class="esquina-grid1-meta">
                <?php
                $cats = get_the_category();
                if ($cats) {
                    foreach ($cats as $cat) {
                        echo '<span class="esquina-grid1-tag">' . esc_html($cat->name) . '</span>';
                    }
                }
                ?>
            </div>

            <a href="<?php the_permalink(); ?>" class="esquina-grid1-button">
                Leer más
            </a>

        </div>

    </article>

<?php endwhile; ?>

</div>