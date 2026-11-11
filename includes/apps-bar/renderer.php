<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Settings (dos bloques: top / bottom)
|--------------------------------------------------------------------------
*/

function esquina_ab_raw_options()
{
    $options = get_option('esquina_apps_bar_settings', []);

    return is_array($options) ? $options : [];
}

/**
 * Convierte formato antiguo plano al nuevo { top, bottom }.
 */
function esquina_ab_migrate_options($options)
{
    if (!is_array($options)) {
        $options = [];
    }

    if (isset($options['top']) || isset($options['bottom'])) {
        return $options;
    }

    // Formato antiguo: apps/title/style en raíz → bloque top.
    if (isset($options['apps']) || isset($options['title']) || isset($options['enabled'])) {
        return [
            'top' => [
                'enabled' => $options['enabled'] ?? 0,
                'title'   => $options['title'] ?? '',
                'style'   => $options['style'] ?? 'panel',
                'color_1' => $options['color_1'] ?? '#2563eb',
                'color_2' => $options['color_2'] ?? '#0ea5e9',
                'apps'    => $options['apps'] ?? [],
            ],
            'bottom' => [],
        ];
    }

    return $options;
}

function esquina_ab_normalize_block($raw, $block_id)
{
    if (!function_exists('esquina_ab_get_block_meta')) {
        require_once __DIR__ . '/admin-settings.php';
    }

    $meta = esquina_ab_get_block_meta($block_id);
    if (!$meta) {
        return null;
    }

    if (!function_exists('esquina_ab_sanitize_hex_color')) {
        require_once __DIR__ . '/admin-settings.php';
    }

    $raw = is_array($raw) ? $raw : [];

    $defaults = [
        'enabled' => 0,
        'title'   => $meta['default_title'],
        'style'   => $meta['default_style'],
        'color_1' => $meta['default_c1'],
        'color_2' => $meta['default_c2'],
        'apps'    => [],
    ];

    $settings = wp_parse_args($raw, $defaults);

    $allowed_styles = array_keys(esquina_ab_style_options());
    if (!in_array($settings['style'], $allowed_styles, true)) {
        $settings['style'] = $meta['default_style'];
    }

    $settings['color_1'] = esquina_ab_sanitize_hex_color($settings['color_1'] ?? '', $meta['default_c1']);
    $settings['color_2'] = esquina_ab_sanitize_hex_color($settings['color_2'] ?? '', $meta['default_c2']);

    $apps = is_array($settings['apps']) ? $settings['apps'] : [];
    $apps = array_filter($apps, function ($app) {
        if (!is_array($app) || empty($app['name']) || empty($app['url'])) {
            return false;
        }

        return !empty($app['svg']) || !empty($app['icon']);
    });

    $settings['apps']      = array_slice(array_values($apps), 0, (int) $meta['max_apps']);
    $settings['enabled']   = !empty($settings['enabled']);
    $settings['title']     = is_string($settings['title']) && $settings['title'] !== ''
        ? $settings['title']
        : $meta['default_title'];
    $settings['block_id']  = $block_id;
    $settings['kind']      = $meta['kind'];
    $settings['placement'] = $meta['placement'];
    $settings['meta']      = $meta;

    return $settings;
}

function esquina_ab_get_all_settings()
{
    $options = esquina_ab_migrate_options(esquina_ab_raw_options());
    $all     = [];

    foreach (esquina_ab_blocks() as $block_id => $meta) {
        $all[$block_id] = esquina_ab_normalize_block($options[$block_id] ?? [], $block_id);
    }

    return $all;
}

function esquina_ab_get_block_settings($block_id)
{
    $all = esquina_ab_get_all_settings();

    return isset($all[$block_id]) ? $all[$block_id] : null;
}

/** @deprecated Usar esquina_ab_get_block_settings('top') */
function esquina_ab_get_settings()
{
    return esquina_ab_get_block_settings('top');
}

function esquina_ab_block_is_active($block_id)
{
    $settings = esquina_ab_get_block_settings($block_id);

    return $settings && !empty($settings['enabled']) && !empty($settings['apps']);
}

function esquina_ab_any_active()
{
    foreach (array_keys(esquina_ab_blocks()) as $block_id) {
        if (esquina_ab_block_is_active($block_id)) {
            return true;
        }
    }

    return false;
}

function esquina_ab_is_active()
{
    return esquina_ab_any_active();
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

    return esquina_ab_any_active();
}

function esquina_ab_already_injected($block_id)
{
    $key = 'esquina_ab_injected_' . $block_id;

    return !empty($GLOBALS[$key]);
}

function esquina_ab_mark_injected($block_id)
{
    $GLOBALS['esquina_ab_injected_' . $block_id] = true;
}

/**
 * ¿Es el contenido real de la entrada consultada?
 */
function esquina_ab_is_main_post_content($content = null, $check_min_length = false)
{
    if (!esquina_ab_is_singular_post()) {
        return false;
    }

    if (!is_main_query()) {
        return false;
    }

    if (!in_the_loop()) {
        return false;
    }

    $queried_id = (int) get_queried_object_id();
    $current_id = (int) get_the_ID();

    if ($queried_id <= 0 || $current_id <= 0 || $queried_id !== $current_id) {
        return false;
    }

    if ($check_min_length && is_string($content) && strlen(trim(wp_strip_all_tags($content))) < 40) {
        return false;
    }

    return true;
}

function esquina_ab_output_bar($block_id)
{
    if (!esquina_ab_should_render() || esquina_ab_already_injected($block_id) || !esquina_ab_block_is_active($block_id)) {
        return '';
    }

    $bar = esquina_ab_build_html($block_id);
    if ($bar === '') {
        return '';
    }

    esquina_ab_mark_injected($block_id);

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

    $bar = esquina_ab_output_bar('top');
    if ($bar === '') {
        return $content;
    }

    return $bar . $content;
}

/**
 * Busca el final de un bloque TOC conocido (con anidación de etiquetas).
 *
 * @return int|false Offset absoluto justo después del bloque, o false.
 */
function esquina_ab_find_toc_end_offset($content)
{
    $open_pattern = '#<(div|nav|aside|section)\b[^>]*(?:id|class)\s*=\s*["\'][^"\']*(?:ez-toc(?:-container)?|lwptoc|toc[_-]container|table-of-contents|rank-math-toc(?:-block)?|wp-block-(?:rank-math-)?toc(?:-block)?|elementor-toc|toc-wrapper)[^"\']*["\'][^>]*>#is';

    if (!preg_match($open_pattern, $content, $m, PREG_OFFSET_CAPTURE)) {
        return false;
    }

    $tag   = strtolower($m[1][0]);
    $pos   = $m[0][1] + strlen($m[0][0]);
    $depth = 1;
    $token = '#</?' . preg_quote($tag, '#') . '\b[^>]*>#i';

    while ($depth > 0 && preg_match($token, $content, $tm, PREG_OFFSET_CAPTURE, $pos)) {
        $full = $tm[0][0];
        $at   = $tm[0][1];

        if ($full[1] === '/') {
            $depth--;
        } else {
            // Autocierre raro: <div ... />
            if (substr(rtrim($full), -2) === '/>') {
                $pos = $at + strlen($full);
                continue;
            }
            $depth++;
        }

        $pos = $at + strlen($full);
    }

    return $depth === 0 ? $pos : false;
}

/**
 * Inserta la barra de juegos tras la TOC o, si no hay, tras el primer H2.
 * Si no encuentra ancla, la deja cerca del inicio (después del primer párrafo).
 */
function esquina_ab_insert_games_bar($content, $bar)
{
    if ($bar === '' || $content === '') {
        return $content;
    }

    // 1) Tras contenedores típicos de tabla de contenidos.
    $toc_end = esquina_ab_find_toc_end_offset($content);
    if ($toc_end !== false) {
        return substr($content, 0, $toc_end) . $bar . substr($content, $toc_end);
    }

    // 2) Tras el primer H2 del contenido.
    if (preg_match('#</h2>#i', $content, $m, PREG_OFFSET_CAPTURE)) {
        $end = $m[0][1] + strlen($m[0][0]);
        return substr($content, 0, $end) . $bar . substr($content, $end);
    }

    // 3) Tras el primer H3 si no hay H2.
    if (preg_match('#</h3>#i', $content, $m, PREG_OFFSET_CAPTURE)) {
        $end = $m[0][1] + strlen($m[0][0]);
        return substr($content, 0, $end) . $bar . substr($content, $end);
    }

    // 4) Tras el primer párrafo (fallback temprano, no al final).
    if (preg_match('#</p>#i', $content, $m, PREG_OFFSET_CAPTURE)) {
        $end = $m[0][1] + strlen($m[0][0]);
        return substr($content, 0, $end) . $bar . substr($content, $end);
    }

    return $bar . $content;
}

function esquina_ab_maybe_insert_mid($content)
{
    if (!is_string($content)) {
        return $content;
    }

    if (!esquina_ab_is_main_post_content($content, true)) {
        return $content;
    }

    $bar = esquina_ab_output_bar('bottom');
    if ($bar === '') {
        return $content;
    }

    return esquina_ab_insert_games_bar($content, $bar);
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

function esquina_ab_build_html($block_id = 'top')
{
    $settings = esquina_ab_get_block_settings($block_id);

    if (!$settings || !$settings['enabled'] || empty($settings['apps'])) {
        return '';
    }

    $title = $settings['title'] !== '' ? $settings['title'] : $settings['meta']['default_title'];
    $style = $settings['style'];
    $show_caption = ($style === 'chips');
    $inline_style = sprintf(
        '--esquina-ab-c1:%1$s;--esquina-ab-c2:%2$s;--esquina-ab-accent:%1$s;',
        esc_attr($settings['color_1']),
        esc_attr($settings['color_2'])
    );
    $classes = sprintf(
        'esquina-ab esquina-ab--%1$s esquina-ab--placement-%2$s esquina-ab--kind-%3$s',
        $style,
        $settings['placement'],
        $settings['kind']
    );

    ob_start();
    ?>
    <div
        class="<?php echo esc_attr($classes); ?>"
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
| Inyectar inicio (herramientas) y mitad temprana (juegos)
|--------------------------------------------------------------------------
*/

function esquina_ab_render_top_via_astra()
{
    if (!esquina_ab_should_render() || esquina_ab_already_injected('top') || !esquina_ab_block_is_active('top')) {
        return;
    }

    if (!esquina_ab_is_main_post_content(null, false)) {
        return;
    }

    echo esquina_ab_output_bar('top'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action('astra_entry_content_before', 'esquina_ab_render_top_via_astra', 5);

function esquina_ab_inject_content($content)
{
    $content = esquina_ab_maybe_prepend($content);
    $content = esquina_ab_maybe_insert_mid($content);

    return $content;
}
add_filter('the_content', 'esquina_ab_inject_content', 5);

function esquina_ab_inject_post_content_block($block_content, $block)
{
    return esquina_ab_inject_content($block_content);
}
add_filter('render_block_core/post-content', 'esquina_ab_inject_post_content_block', 10, 2);
