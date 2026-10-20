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
    $settings = esquina_ab_get_settings();

    return !empty($settings['enabled']) && !empty($settings['apps']);
}

function esquina_ab_is_singular_post()
{
    return is_singular('post') || (is_single() && get_post_type() === 'post');
}

function esquina_ab_should_render()
{
    if (is_admin() || is_feed() || wp_doing_ajax()) {
        return false;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
        return false;
    }

    if (!esquina_ab_is_singular_post()) {
        return false;
    }

    if (doing_filter('get_the_excerpt')) {
        return false;
    }

    return esquina_ab_is_active();
}

function esquina_ab_already_injected()
{
    return !empty($GLOBALS['esquina_ab_injected']);
}

function esquina_ab_mark_injected()
{
    $GLOBALS['esquina_ab_injected'] = true;
}

/**
 * ¿Es el contenido real de la entrada consultada?
 * Evita inyectar en header/footer Elementor/HFE que también llaman a the_content.
 *
 * @param string|null $content           Contenido de the_content (opcional).
 * @param bool        $check_min_length  Exigir texto mínimo (útil en the_content).
 */
function esquina_ab_is_main_post_content($content = null, $check_min_length = false)
{
    if (!esquina_ab_is_singular_post()) {
        return false;
    }

    if (!is_main_query()) {
        return false;
    }

    // Header/Footer Elementor suele llamar the_content fuera del loop.
    if (!in_the_loop()) {
        return false;
    }

    $queried_id = (int) get_queried_object_id();
    $current_id = (int) get_the_ID();

    if ($queried_id <= 0 || $current_id <= 0 || $queried_id !== $current_id) {
        return false;
    }

    // Fragmentos del header suelen ser muy cortos o vacíos.
    if ($check_min_length && is_string($content) && strlen(trim(wp_strip_all_tags($content))) < 40) {
        return false;
    }

    return true;
}

function esquina_ab_output_bar()
{
    if (!esquina_ab_should_render() || esquina_ab_already_injected()) {
        return '';
    }

    $bar = esquina_ab_build_html();
    if ($bar === '') {
        return '';
    }

    esquina_ab_mark_injected();

    return $bar;
}

function esquina_ab_maybe_prepend($content)
{
    if (!is_string($content)) {
        return $content;
    }

    if (!esquina_ab_is_main_post_content($content, true)) {
        return $content;
    }

    $bar = esquina_ab_output_bar();
    if ($bar === '') {
        return $content;
    }

    return $bar . $content;
}

/*
|--------------------------------------------------------------------------
| Assets (solo en single post)
|--------------------------------------------------------------------------
*/

function esquina_ab_enqueue_assets()
{
    if (!esquina_ab_should_render()) {
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
| 1) Hook nativo de Astra (más fiable en Astra + Elementor header/footer)
| 2) the_content solo si es el post principal en el loop (evita el masthead)
| 3) Bloque core/post-content por si usan FSE
*/

function esquina_ab_render_via_astra()
{
    if (!esquina_ab_should_render() || esquina_ab_already_injected()) {
        return;
    }

    // Solo en el loop del post consultado (sin chequeo de longitud).
    if (!esquina_ab_is_main_post_content(null, false)) {
        return;
    }

    echo esquina_ab_output_bar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action('astra_entry_content_before', 'esquina_ab_render_via_astra', 5);

function esquina_ab_prepend_content($content)
{
    return esquina_ab_maybe_prepend($content);
}
add_filter('the_content', 'esquina_ab_prepend_content', 5);

function esquina_ab_prepend_post_content_block($block_content, $block)
{
    return esquina_ab_maybe_prepend($block_content);
}
add_filter('render_block_core/post-content', 'esquina_ab_prepend_post_content_block', 10, 2);
