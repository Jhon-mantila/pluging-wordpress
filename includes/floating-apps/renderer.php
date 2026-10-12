<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/icons.php';

/*
|--------------------------------------------------------------------------
| Settings
|--------------------------------------------------------------------------
*/

function esquina_fa_get_settings()
{
    $defaults = [
        'enabled'    => 0,
        'position'   => 'bottom-right',
        'offset_x'   => 20,
        'offset_y'   => 20,
        'shape'      => 'circle',
        'delay'      => 2,
        'label'      => 'Apps de Esquina',
        'hide'       => '7',
        'reopen_tab' => 1,
        'reset_at'   => 0,
        'apps'       => [],
    ];

    $options = get_option('esquina_floating_apps_settings', []);
    if (!is_array($options)) {
        $options = [];
    }

    $settings = wp_parse_args($options, $defaults);

    if (!in_array($settings['position'], ['bottom-right', 'bottom-left', 'top-right', 'top-left'], true)) {
        $settings['position'] = 'bottom-right';
    }

    if (!in_array((string) $settings['hide'], ['never', 'session', '1', '7', '30'], true)) {
        $settings['hide'] = '7';
    }

    if (!in_array($settings['shape'], ['circle', 'rounded'], true)) {
        $settings['shape'] = 'circle';
    }

    $settings['offset_x'] = min(absint($settings['offset_x']), 500);
    $settings['offset_y'] = min(absint($settings['offset_y']), 500);

    $apps = is_array($settings['apps']) ? $settings['apps'] : [];
    $apps = array_filter($apps, function ($app) {
        return is_array($app) && !empty($app['name']) && !empty($app['url']);
    });

    $settings['apps']       = array_slice(array_values($apps), 0, 4);
    $settings['enabled']    = !empty($settings['enabled']);
    $settings['reopen_tab'] = !empty($settings['reopen_tab']);
    $settings['delay']      = absint($settings['delay']);
    $settings['reset_at']   = absint($settings['reset_at']);
    $settings['hide']       = (string) $settings['hide'];

    return $settings;
}

function esquina_fa_is_active()
{
    if (is_admin()) {
        return false;
    }

    $settings = esquina_fa_get_settings();

    return $settings['enabled'] && !empty($settings['apps']);
}

/*
|--------------------------------------------------------------------------
| Assets
|--------------------------------------------------------------------------
*/

function esquina_fa_enqueue_assets()
{
    if (!esquina_fa_is_active()) {
        return;
    }

    $settings = esquina_fa_get_settings();

    wp_enqueue_style(
        'esquina-fa',
        ESQUINA_MF_URL . 'includes/floating-apps/assets/floating-apps.css',
        [],
        ESQUINA_MF_VERSION
    );

    wp_enqueue_script(
        'esquina-fa',
        ESQUINA_MF_URL . 'includes/floating-apps/assets/floating-apps.js',
        [],
        ESQUINA_MF_VERSION,
        true
    );

    wp_localize_script('esquina-fa', 'esquinaFA', [
        'delay'      => (int) $settings['delay'],
        'storageKey' => 'esquina_fa_closed_v1',
        'hide'       => $settings['hide'],
        'resetAt'    => $settings['reset_at'] * 1000,
        'reopenTab'  => $settings['reopen_tab'],
    ]);
}
add_action('wp_enqueue_scripts', 'esquina_fa_enqueue_assets');

/*
|--------------------------------------------------------------------------
| Render
|--------------------------------------------------------------------------
*/

function esquina_fa_render()
{
    if (!esquina_fa_is_active()) {
        return;
    }

    $settings = esquina_fa_get_settings();
    $icons    = esquina_fa_icons();
    $label    = $settings['label'] !== '' ? $settings['label'] : __('Apps de Esquina', 'esquina-mis-funciones');
    $classes  = 'esquina-fa esquina-fa--' . $settings['position'] . ' esquina-fa--' . $settings['shape'];
    $style    = sprintf('--esquina-fa-x: %dpx; --esquina-fa-y: %dpx;', $settings['offset_x'], $settings['offset_y']);
    ?>
    <div
        id="esquina-fa"
        class="<?php echo esc_attr($classes); ?>"
        style="<?php echo esc_attr($style); ?>"
        hidden>
        <button
            type="button"
            class="esquina-fa__toggle"
            aria-label="<?php esc_attr_e('Abrir apps', 'esquina-mis-funciones'); ?>"
            aria-expanded="false"
            aria-controls="esquina-fa-panel">
            <span class="esquina-fa__toggle-icon"><?php echo esquina_fa_svg_markup('apps', 26); ?></span>
        </button>

        <?php if ($settings['reopen_tab']) : ?>
            <button
                type="button"
                class="esquina-fa__reopen"
                aria-label="<?php esc_attr_e('Mostrar apps de nuevo', 'esquina-mis-funciones'); ?>">
                <?php echo esquina_fa_svg_markup('apps', 16); ?>
            </button>
        <?php endif; ?>

        <div id="esquina-fa-panel" class="esquina-fa__panel" role="dialog" aria-label="<?php echo esc_attr($label); ?>">
            <div class="esquina-fa__header">
                <span><?php echo esc_html($label); ?></span>
                <button
                    type="button"
                    class="esquina-fa__close"
                    aria-label="<?php esc_attr_e('Cerrar', 'esquina-mis-funciones'); ?>">×</button>
            </div>
            <ul class="esquina-fa__list">
                <?php foreach ($settings['apps'] as $app) : ?>
                    <?php $svg = isset($app['svg'], $icons[$app['svg']]) ? $app['svg'] : ''; ?>
                    <li class="esquina-fa__item">
                        <a href="<?php echo esc_url($app['url']); ?>" class="esquina-fa__link">
                            <?php if ($svg !== '') : ?>
                                <span
                                    class="esquina-fa__icon esquina-fa__icon--svg"
                                    style="background-color: <?php echo esc_attr($icons[$svg]['color']); ?>">
                                    <?php echo esquina_fa_svg_markup($svg, 22); ?>
                                </span>
                            <?php elseif (!empty($app['icon'])) : ?>
                                <img
                                    src="<?php echo esc_url($app['icon']); ?>"
                                    alt=""
                                    class="esquina-fa__icon"
                                    width="40"
                                    height="40"
                                    loading="lazy" />
                            <?php endif; ?>
                            <span class="esquina-fa__meta">
                                <strong><?php echo esc_html($app['name']); ?></strong>
                                <?php if (!empty($app['desc'])) : ?>
                                    <small><?php echo esc_html($app['desc']); ?></small>
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'esquina_fa_render');
