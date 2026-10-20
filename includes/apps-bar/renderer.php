<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/

function esquina_ab_get_settings()
{
    $defaults = [
        'enabled' => 0,
        'title'   => 'Nuestras aplicaciones',
        'style'   => 'panel',
        'color_1' => '#2563eb',
        'color_2' => '#0ea5e9',
        'apps'    => [],
    ];

    $options = get_option('esquina_apps_bar_settings', []);
    if (!is_array($options)) {
        $options = [];
    }

    $settings = wp_parse_args($options, $defaults);

    $allowed_styles = ['panel', 'banda', 'chips'];
    if (!in_array($settings['style'], $allowed_styles, true)) {
        $settings['style'] = 'panel';
    }

    if (!function_exists('esquina_ab_sanitize_hex_color')) {
        require_once __DIR__ . '/admin-settings.php';
    }

    $settings['color_1'] = esquina_ab_sanitize_hex_color($settings['color_1'] ?? '', '#2563eb');
    $settings['color_2'] = esquina_ab_sanitize_hex_color($settings['color_2'] ?? '', '#0ea5e9');

    $apps = is_array($settings['apps']) ? $settings['apps'] : [];
    $apps = array_filter($apps, function ($app) {
        if (!is_array($app) || empty($app['name']) || empty($app['url'])) {
            return false;
        }

        return !empty($app['svg']) || !empty($app['icon']);
    });

    $settings['apps']    = array_slice(array_values($apps), 0, 12);
    $settings['enabled'] = !empty($settings['enabled']);
    $settings['title']   = is_string($settings['title']) ? $settings['title'] : 'Nuestras aplicaciones';

    return $settings;
}

function esquina_ab_is_active()
{
    if (is_admin()) {
        return false;
    }

    $settings = esquina_ab_get_settings();

    return $settings['enabled'] && !empty($settings['apps']);
}

/*
|--------------------------------------------------------------------------
| Assets (solo en single post)
|--------------------------------------------------------------------------
*/

function esquina_ab_enqueue_assets()
{
    if (!is_singular('post') || !esquina_ab_is_active()) {
        return;
    }

    wp_enqueue_style(
        'esquina-ab',
        ESQUINA_MF_URL . 'includes/apps-bar/assets/apps-bar.css',
        [],
        ESQUINA_MF_VERSION
    );
}
add_action('wp_enqueue_scripts', 'esquina_ab_enqueue_assets');

/*
|--------------------------------------------------------------------------
| SVG sanitizado (whitelist)
|--------------------------------------------------------------------------
*/

function esquina_ab_kses_svg($html)
{
    $allowed = [
        'svg' => [
            'xmlns'          => true,
            'viewbox'        => true,
            'width'          => true,
            'height'         => true,
            'fill'           => true,
            'stroke'         => true,
            'stroke-width'   => true,
            'stroke-linecap' => true,
            'stroke-linejoin'=> true,
            'aria-hidden'    => true,
            'focusable'      => true,
            'class'          => true,
            'role'           => true,
        ],
        'path' => [
            'd'              => true,
            'fill'           => true,
            'stroke'         => true,
            'stroke-width'   => true,
            'stroke-linecap' => true,
            'stroke-linejoin'=> true,
        ],
        'rect' => [
            'x'      => true,
            'y'      => true,
            'width'  => true,
            'height' => true,
            'rx'     => true,
            'ry'     => true,
            'fill'   => true,
            'stroke' => true,
        ],
        'circle' => [
            'cx'     => true,
            'cy'     => true,
            'r'      => true,
            'fill'   => true,
            'stroke' => true,
        ],
        'line' => [
            'x1'     => true,
            'x2'     => true,
            'y1'     => true,
            'y2'     => true,
            'stroke' => true,
        ],
        'polyline' => [
            'points' => true,
            'fill'   => true,
            'stroke' => true,
        ],
        'polygon' => [
            'points' => true,
            'fill'   => true,
            'stroke' => true,
        ],
    ];

    return wp_kses($html, $allowed);
}

/*
|--------------------------------------------------------------------------
| Markup de un icono (SVG o <img>)
|--------------------------------------------------------------------------
*/

function esquina_ab_render_icon($app)
{
    if (!function_exists('esquina_ab_load_icons')) {
        require_once __DIR__ . '/admin-settings.php';
    }

    $icons = esquina_ab_load_icons();
    $svg   = isset($app['svg'], $icons[$app['svg']]) ? $app['svg'] : '';

    if ($svg !== '') {
        $color  = $icons[$svg]['color'];
        $markup = esquina_ab_svg_markup($svg, 22);

        return sprintf(
            '<span class="esquina-ab__icon esquina-ab__icon--svg" style="background-color:%1$s">%2$s</span>',
            esc_attr($color),
            esquina_ab_kses_svg($markup)
        );
    }

    if (!empty($app['icon'])) {
        return sprintf(
            '<img src="%1$s" alt="" class="esquina-ab__icon" width="40" height="40" loading="lazy" />',
            esc_url($app['icon'])
        );
    }

    return '';
}

/*
|--------------------------------------------------------------------------
| HTML de la fila
|--------------------------------------------------------------------------
*/

function esquina_ab_build_html()
{
    $settings = esquina_ab_get_settings();

    if (!$settings['enabled'] || empty($settings['apps'])) {
        return '';
    }

    $title = $settings['title'] !== '' ? $settings['title'] : __('Nuestras aplicaciones', 'esquina-mis-funciones');
    $style = $settings['style'];
    $show_caption = ($style === 'chips');
    $inline_style = sprintf(
        '--esquina-ab-c1:%1$s;--esquina-ab-c2:%2$s;--esquina-ab-accent:%1$s;',
        esc_attr($settings['color_1']),
        esc_attr($settings['color_2'])
    );

    ob_start();
    ?>
    <div
        class="esquina-ab esquina-ab--<?php echo esc_attr($style); ?>"
        style="<?php echo esc_attr($inline_style); ?>"
        role="navigation"
        aria-label="<?php echo esc_attr($title); ?>">
        <div class="esquina-ab__inner">
            <?php if ($title !== '') : ?>
                <div class="esquina-ab__header">
                    <span class="esquina-ab__badge" aria-hidden="true">✦</span>
                    <span class="esquina-ab__title"><?php echo esc_html($title); ?></span>
                </div>
            <?php endif; ?>
            <ul class="esquina-ab__list">
                <?php foreach ($settings['apps'] as $app) : ?>
                    <?php
                    $icon_html = esquina_ab_render_icon($app);
                    if ($icon_html === '') {
                        continue;
                    }
                    ?>
                    <li class="esquina-ab__item">
                        <a
                            href="<?php echo esc_url($app['url']); ?>"
                            class="esquina-ab__link"
                            target="_blank"
                            rel="noopener noreferrer">
                            <?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado/kses ?>
                            <?php if ($show_caption) : ?>
                                <span class="esquina-ab__caption"><?php echo esc_html($app['name']); ?></span>
                            <?php endif; ?>
                            <span class="esquina-ab__tooltip" role="tooltip">
                                <strong class="esquina-ab__tooltip-name"><?php echo esc_html($app['name']); ?></strong>
                                <?php if (!empty($app['desc'])) : ?>
                                    <span class="esquina-ab__tooltip-desc"><?php echo esc_html($app['desc']); ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="screen-reader-text"><?php echo esc_html($app['name']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

/*
|--------------------------------------------------------------------------
| Inyectar al inicio del contenido (solo single post)
|--------------------------------------------------------------------------
*/

function esquina_ab_prepend_content($content)
{
    if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    if (!esquina_ab_is_active()) {
        return $content;
    }

    $bar = esquina_ab_build_html();

    if ($bar === '') {
        return $content;
    }

    return $bar . $content;
}
add_filter('the_content', 'esquina_ab_prepend_content', 8);
