<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/instances.php';

/*
|--------------------------------------------------------------------------
| Settings por instancia
|--------------------------------------------------------------------------
*/

function esquina_fa_get_settings($instance_id = 'panel1')
{
    $instance = esquina_fa_get_instance($instance_id);
    if (!$instance) {
        return null;
    }

    $defaults = [
        'enabled'    => 0,
        'position'   => 'bottom-right',
        'offset_x'   => 20,
        'offset_y'   => 20,
        'shape'      => 'circle',
        'btn_color'  => $instance_id === 'panel2' ? '#0f766e' : '#2563eb',
        'delay'      => 2,
        'label'      => $instance['default_label'],
        'hide'       => '7',
        'reopen_tab' => 1,
        'reset_at'   => 0,
        'apps'       => [],
    ];

    $options = get_option($instance['option'], []);
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

    $apps = is_array($settings['apps']) ? $settings['apps'] : [];
    $apps = array_filter($apps, function ($app) {
        return is_array($app) && !empty($app['name']) && !empty($app['url']);
    });

    $fallback_btn = $instance_id === 'panel2' ? '#0f766e' : '#2563eb';
    $btn_color    = is_string($settings['btn_color'] ?? null) ? trim($settings['btn_color']) : '';
    if (!preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $btn_color)) {
        $btn_color = $fallback_btn;
    }

    $settings['apps']       = array_slice(array_values($apps), 0, 12);
    $settings['enabled']    = !empty($settings['enabled']);
    $settings['reopen_tab'] = !empty($settings['reopen_tab']);
    $settings['delay']      = absint($settings['delay']);
    $settings['reset_at']   = absint($settings['reset_at']);
    $settings['hide']       = (string) $settings['hide'];
    $settings['offset_x']   = min(absint($settings['offset_x']), 500);
    $settings['offset_y']   = min(absint($settings['offset_y']), 500);
    $settings['btn_color']  = $btn_color;
    $settings['instance']   = $instance;

    return $settings;
}

function esquina_fa_active_instances()
{
    if (is_admin()) {
        return [];
    }

    $active = [];
    $stack  = 0;
    $leader = null;

    foreach (esquina_fa_instances() as $id => $meta) {
        $settings = esquina_fa_get_settings($id);
        if (!$settings || !$settings['enabled'] || empty($settings['apps'])) {
            continue;
        }

        if ($leader === null) {
            $leader = $settings;
        } else {
            // Auto-alinear con el primer panel activo (misma esquina y márgenes).
            $settings['position'] = $leader['position'];
            $settings['offset_x'] = $leader['offset_x'];
            $settings['offset_y'] = $leader['offset_y'];
        }

        $settings['stack_index'] = $stack;
        $active[$id] = $settings;
        $stack++;
    }

    return $active;
}

/*
|--------------------------------------------------------------------------
| Assets
|--------------------------------------------------------------------------
*/

function esquina_fa_enqueue_assets()
{
    $active = esquina_fa_active_instances();
    if (empty($active)) {
        return;
    }

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

    $configs = [];
    foreach ($active as $id => $settings) {
        $configs[$id] = [
            'delay'      => (int) $settings['delay'],
            'storageKey' => $settings['instance']['storage_key'],
            'hide'       => $settings['hide'],
            'resetAt'    => $settings['reset_at'] * 1000,
            'reopenTab'  => $settings['reopen_tab'],
        ];
    }

    wp_localize_script('esquina-fa', 'esquinaFAInstances', $configs);
}
add_action('wp_enqueue_scripts', 'esquina_fa_enqueue_assets');

/*
|--------------------------------------------------------------------------
| Render
|--------------------------------------------------------------------------
*/

function esquina_fa_render_instance($id, $settings)
{
    $instance = $settings['instance'];
    $icons    = esquina_fa_icons();
    $label    = $settings['label'] !== '' ? $settings['label'] : $instance['default_label'];
    $dom_id   = 'esquina-fa-' . $id;
    $panel_id = $dom_id . '-panel';
    $classes  = 'esquina-fa esquina-fa--' . $settings['position'] . ' esquina-fa--' . $settings['shape'];
    $toggle   = isset($icons[$instance['toggle_svg']]) ? $instance['toggle_svg'] : 'apps';
    $style    = sprintf(
        '--esquina-fa-x: %dpx; --esquina-fa-y: %dpx; --esquina-fa-stack: %d; --esquina-fa-btn: %s;',
        $settings['offset_x'],
        $settings['offset_y'],
        (int) $settings['stack_index'],
        $settings['btn_color']
    );
    ?>
    <div
        id="<?php echo esc_attr($dom_id); ?>"
        class="<?php echo esc_attr($classes); ?>"
        style="<?php echo esc_attr($style); ?>"
        data-esquina-fa="<?php echo esc_attr($id); ?>"
        hidden>
        <button
            type="button"
            class="esquina-fa__toggle"
            aria-label="<?php echo esc_attr(sprintf(__('Abrir %s', 'esquina-mis-funciones'), $label)); ?>"
            aria-expanded="false"
            aria-controls="<?php echo esc_attr($panel_id); ?>">
            <span class="esquina-fa__toggle-icon"><?php echo esquina_fa_svg_markup($toggle, 26); ?></span>
        </button>

        <?php if ($settings['reopen_tab']) : ?>
            <button
                type="button"
                class="esquina-fa__reopen"
                aria-label="<?php echo esc_attr(sprintf(__('Mostrar %s de nuevo', 'esquina-mis-funciones'), $label)); ?>">
                <?php echo esquina_fa_svg_markup($toggle, 16); ?>
            </button>
        <?php endif; ?>

        <div id="<?php echo esc_attr($panel_id); ?>" class="esquina-fa__panel" role="dialog" aria-label="<?php echo esc_attr($label); ?>">
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

function esquina_fa_render()
{
    $active = esquina_fa_active_instances();
    if (empty($active)) {
        return;
    }

    foreach ($active as $id => $settings) {
        esquina_fa_render_instance($id, $settings);
    }
}
add_action('wp_footer', 'esquina_fa_render');
