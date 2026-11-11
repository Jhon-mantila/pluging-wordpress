<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/instances.php';

/*
|--------------------------------------------------------------------------
| Registro opciones (todas las instancias)
|--------------------------------------------------------------------------
*/

function esquina_fa_register_settings()
{
    foreach (esquina_fa_instances() as $instance) {
        register_setting(
            $instance['group'],
            $instance['option'],
            function ($input) use ($instance) {
                return esquina_fa_sanitize_settings($input, $instance);
            }
        );
    }
}
add_action('admin_init', 'esquina_fa_register_settings');

function esquina_fa_hide_options()
{
    return [
        'never'   => __('Nunca ocultar (la X solo cierra el panel)', 'esquina-mis-funciones'),
        'session' => __('Solo durante la visita actual', 'esquina-mis-funciones'),
        '1'       => __('1 día', 'esquina-mis-funciones'),
        '7'       => __('7 días', 'esquina-mis-funciones'),
        '30'      => __('30 días', 'esquina-mis-funciones'),
    ];
}

function esquina_fa_sanitize_hex_color($color, $fallback)
{
    $color = is_string($color) ? trim($color) : '';

    if (function_exists('sanitize_hex_color')) {
        $sanitized = sanitize_hex_color($color);
        return $sanitized ? $sanitized : $fallback;
    }

    return preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color) ? $color : $fallback;
}

/*
|--------------------------------------------------------------------------
| Sanitizar
|--------------------------------------------------------------------------
*/

function esquina_fa_sanitize_settings($input, $instance = null)
{
    if (!is_array($instance)) {
        $instance = esquina_fa_get_instance('panel1');
    }

    $positions = ['bottom-right', 'bottom-left', 'top-right', 'top-left'];
    $input     = is_array($input) ? $input : [];
    $current   = get_option($instance['option'], []);
    $current   = is_array($current) ? $current : [];

    $position = isset($input['position']) ? sanitize_text_field($input['position']) : 'bottom-right';
    if (!in_array($position, $positions, true)) {
        $position = 'bottom-right';
    }

    $label = isset($input['label']) ? sanitize_text_field($input['label']) : '';
    if ($label === '') {
        $label = $instance['default_label'];
    }

    $hide = isset($input['hide']) ? sanitize_text_field((string) $input['hide']) : '7';
    if (!array_key_exists($hide, esquina_fa_hide_options())) {
        $hide = '7';
    }

    $shape = isset($input['shape']) ? sanitize_text_field($input['shape']) : 'circle';
    if (!in_array($shape, ['circle', 'rounded'], true)) {
        $shape = 'circle';
    }

    $default_btn = $instance['id'] === 'panel2' ? '#0f766e' : '#2563eb';

    $clean = [
        'enabled'    => empty($input['enabled']) ? 0 : 1,
        'position'   => $position,
        'offset_x'   => isset($input['offset_x']) ? min(absint($input['offset_x']), 500) : 20,
        'offset_y'   => isset($input['offset_y']) ? min(absint($input['offset_y']), 500) : 20,
        'shape'      => $shape,
        'btn_color'  => esquina_fa_sanitize_hex_color($input['btn_color'] ?? '', $default_btn),
        'delay'      => isset($input['delay']) ? absint($input['delay']) : 2,
        'label'      => $label,
        'hide'       => $hide,
        'reopen_tab' => empty($input['reopen_tab']) ? 0 : 1,
        'reset_at'   => isset($input['reset_at']) ? absint($input['reset_at']) : absint($current['reset_at'] ?? 0),
        'apps'       => [],
    ];

    if (empty($input['apps']) || !is_array($input['apps'])) {
        return $clean;
    }

    $icons = esquina_fa_icons();

    foreach ($input['apps'] as $app) {
        if (count($clean['apps']) >= 12 || !is_array($app)) {
            continue;
        }

        $name = sanitize_text_field($app['name'] ?? '');
        $url  = esc_url_raw($app['url'] ?? '');

        if ($name === '' || $url === '') {
            continue;
        }

        $svg = sanitize_text_field($app['svg'] ?? '');
        if (!isset($icons[$svg])) {
            $svg = '';
        }

        $clean['apps'][] = [
            'svg'  => $svg,
            'icon' => esc_url_raw($app['icon'] ?? ''),
            'name' => $name,
            'desc' => sanitize_text_field($app['desc'] ?? ''),
            'url'  => $url,
        ];
    }

    return $clean;
}

/*
|--------------------------------------------------------------------------
| Reset por instancia
|--------------------------------------------------------------------------
*/

function esquina_fa_handle_reset()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('No tienes permisos para hacer esto.', 'esquina-mis-funciones'));
    }

    $instance_id = isset($_POST['instance']) ? sanitize_key(wp_unslash($_POST['instance'])) : 'panel1';
    $instance    = esquina_fa_get_instance($instance_id);

    if (!$instance) {
        wp_die(esc_html__('Instancia no válida.', 'esquina-mis-funciones'));
    }

    check_admin_referer($instance['reset_action']);

    $options = get_option($instance['option'], []);
    $options = is_array($options) ? $options : [];
    $options['reset_at'] = time();
    update_option($instance['option'], $options);

    wp_safe_redirect(add_query_arg(
        ['page' => $instance['menu_slug'], 'esquina_fa_reset' => 1],
        admin_url('admin.php')
    ));
    exit;
}
add_action('admin_post_esquina_fa_reset_panel1', 'esquina_fa_handle_reset');
add_action('admin_post_esquina_fa_reset_panel2', 'esquina_fa_handle_reset');

/*
|--------------------------------------------------------------------------
| Menús
|--------------------------------------------------------------------------
*/

function esquina_fa_menu()
{
    foreach (esquina_fa_instances() as $instance) {
        $hook = add_submenu_page(
            'esquinaweb-dashboard',
            $instance['page_title'],
            $instance['menu_title'],
            'manage_options',
            $instance['menu_slug'],
            function () use ($instance) {
                esquina_fa_page($instance);
            }
        );

        add_action('admin_enqueue_scripts', function ($current) use ($hook) {
            if ($current !== $hook) {
                return;
            }
            wp_enqueue_media();
        });

        add_action('admin_footer', function () use ($hook, $instance) {
            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$screen || $screen->id !== $hook) {
                return;
            }
            esquina_fa_admin_script($instance);
        });
    }
}
add_action('admin_menu', 'esquina_fa_menu');

/*
|--------------------------------------------------------------------------
| Vista admin
|--------------------------------------------------------------------------
*/

function esquina_fa_page($instance)
{
    if (!current_user_can('manage_options') || !is_array($instance)) {
        return;
    }

    $option  = $instance['option'];
    $options = get_option($option, []);
    if (!is_array($options)) {
        $options = [];
    }

    $apps = isset($options['apps']) && is_array($options['apps']) ? $options['apps'] : [];
    $apps = array_slice($apps, 0, 12);

    $positions = [
        'bottom-right' => __('Inferior derecha', 'esquina-mis-funciones'),
        'bottom-left'  => __('Inferior izquierda', 'esquina-mis-funciones'),
        'top-right'    => __('Superior derecha', 'esquina-mis-funciones'),
        'top-left'     => __('Superior izquierda', 'esquina-mis-funciones'),
    ];

    ?>

<div class="wrap">

    <h1><?php echo esc_html($instance['page_title']); ?></h1>

    <p class="description">
        <?php esc_html_e('Configura este panel flotante. Si activas los dos, se apilan en la misma esquina. El nombre que ve el visitante es el «Título del panel».', 'esquina-mis-funciones'); ?>
    </p>

    <?php if (!empty($_GET['esquina_fa_reset'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Listo: este panel volverá a mostrarse a todos los visitantes que lo habían cerrado.', 'esquina-mis-funciones'); ?></p>
        </div>
    <?php endif; ?>

    <form
        method="post"
        action="options.php"
        class="esquina-fa-settings-form"
        id="esquina-fa-settings-form-<?php echo esc_attr($instance['id']); ?>"
        data-option="<?php echo esc_attr($option); ?>">

        <?php settings_fields($instance['group']); ?>

        <div class="esquina-fa-admin__savebar esquina-fa-admin__savebar--top">
            <?php submit_button(__('Guardar cambios', 'esquina-mis-funciones'), 'primary', 'submit', false); ?>
        </div>

        <table class="form-table">

            <tr>
                <th><?php esc_html_e('Activar widget flotante', 'esquina-mis-funciones'); ?></th>
                <td>
                    <input
                        type="checkbox"
                        name="<?php echo esc_attr($option); ?>[enabled]"
                        value="1"
                        <?php checked(!empty($options['enabled'])); ?>>
                </td>
            </tr>

            <?php if ($instance['id'] === 'panel1') : ?>
            <tr>
                <th><?php esc_html_e('Posición del widget', 'esquina-mis-funciones'); ?></th>
                <td>
                    <select name="<?php echo esc_attr($option); ?>[position]">
                        <?php foreach ($positions as $value => $label) : ?>
                            <option
                                value="<?php echo esc_attr($value); ?>"
                                <?php selected($options['position'] ?? 'bottom-right', $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Si ambos paneles están activos, el Panel 2 se apila solo en esta misma esquina.', 'esquina-mis-funciones'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Separación del borde (px)', 'esquina-mis-funciones'); ?></th>
                <td>
                    <label>
                        <?php esc_html_e('Horizontal', 'esquina-mis-funciones'); ?>
                        <input
                            type="number"
                            min="0"
                            max="500"
                            step="1"
                            name="<?php echo esc_attr($option); ?>[offset_x]"
                            value="<?php echo esc_attr((string) absint($options['offset_x'] ?? 20)); ?>"
                            class="small-text">
                    </label>
                    &nbsp;
                    <label>
                        <?php esc_html_e('Vertical', 'esquina-mis-funciones'); ?>
                        <input
                            type="number"
                            min="0"
                            max="500"
                            step="1"
                            name="<?php echo esc_attr($option); ?>[offset_y]"
                            value="<?php echo esc_attr((string) absint($options['offset_y'] ?? 20)); ?>"
                            class="small-text">
                    </label>
                </td>
            </tr>
            <?php else : ?>
            <tr>
                <th><?php esc_html_e('Posición', 'esquina-mis-funciones'); ?></th>
                <td>
                    <p class="description">
                        <?php esc_html_e('Automática: se alinea con el Panel 1 (misma esquina y separación). No hace falta configurarla aquí.', 'esquina-mis-funciones'); ?>
                    </p>
                    <input type="hidden" name="<?php echo esc_attr($option); ?>[position]" value="<?php echo esc_attr($options['position'] ?? 'bottom-right'); ?>">
                    <input type="hidden" name="<?php echo esc_attr($option); ?>[offset_x]" value="<?php echo esc_attr((string) absint($options['offset_x'] ?? 20)); ?>">
                    <input type="hidden" name="<?php echo esc_attr($option); ?>[offset_y]" value="<?php echo esc_attr((string) absint($options['offset_y'] ?? 20)); ?>">
                </td>
            </tr>
            <?php endif; ?>

            <tr>
                <th><?php esc_html_e('Forma del botón', 'esquina-mis-funciones'); ?></th>
                <td>
                    <select name="<?php echo esc_attr($option); ?>[shape]">
                        <option value="circle" <?php selected($options['shape'] ?? 'circle', 'circle'); ?>>
                            <?php esc_html_e('Circular', 'esquina-mis-funciones'); ?>
                        </option>
                        <option value="rounded" <?php selected($options['shape'] ?? 'circle', 'rounded'); ?>>
                            <?php esc_html_e('Cuadrado redondeado', 'esquina-mis-funciones'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Color del botón', 'esquina-mis-funciones'); ?></th>
                <td>
                    <?php
                    $default_btn = $instance['id'] === 'panel2' ? '#0f766e' : '#2563eb';
                    $btn_color   = $options['btn_color'] ?? $default_btn;
                    ?>
                    <input
                        type="color"
                        name="<?php echo esc_attr($option); ?>[btn_color]"
                        value="<?php echo esc_attr($btn_color); ?>">
                    <p class="description">
                        <?php esc_html_e('Aplica al botón flotante, la pestaña de reabrir y el acento del panel.', 'esquina-mis-funciones'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Retardo antes de aparecer (segundos)', 'esquina-mis-funciones'); ?></th>
                <td>
                    <input
                        type="number"
                        min="0"
                        step="1"
                        name="<?php echo esc_attr($option); ?>[delay]"
                        value="<?php echo esc_attr((string) absint($options['delay'] ?? 2)); ?>"
                        class="small-text">
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Título del panel', 'esquina-mis-funciones'); ?></th>
                <td>
                    <input
                        type="text"
                        name="<?php echo esc_attr($option); ?>[label]"
                        value="<?php echo esc_attr($options['label'] ?? $instance['default_label']); ?>"
                        class="regular-text">
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Al cerrar con la X, ocultar durante', 'esquina-mis-funciones'); ?></th>
                <td>
                    <select name="<?php echo esc_attr($option); ?>[hide]">
                        <?php foreach (esquina_fa_hide_options() as $value => $label) : ?>
                            <option
                                value="<?php echo esc_attr($value); ?>"
                                <?php selected((string) ($options['hide'] ?? '7'), (string) $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Pestaña para reabrir', 'esquina-mis-funciones'); ?></th>
                <td>
                    <label>
                        <input
                            type="checkbox"
                            name="<?php echo esc_attr($option); ?>[reopen_tab]"
                            value="1"
                            <?php checked(!empty($options['reopen_tab'] ?? 1)); ?>>
                        <?php esc_html_e('Tras cerrar con la X, dejar una pestaña pequeña en el borde para volver a abrirlo', 'esquina-mis-funciones'); ?>
                    </label>
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Aplicaciones', 'esquina-mis-funciones'); ?></th>
                <td>
                    <p class="description">
                        <?php esc_html_e('Hasta 12 aplicaciones. El nombre y la URL son obligatorios.', 'esquina-mis-funciones'); ?>
                    </p>

                    <div class="esquina-fa-apps" id="esquina-fa-apps-<?php echo esc_attr($instance['id']); ?>">
                        <?php foreach ($apps as $index => $app) : ?>
                            <?php esquina_fa_app_row((int) $index, is_array($app) ? $app : [], $option); ?>
                        <?php endforeach; ?>
                    </div>

                    <p>
                        <button type="button" class="button button-secondary esquina-fa-add" id="esquina-fa-add-<?php echo esc_attr($instance['id']); ?>">
                            <?php esc_html_e('Añadir aplicación', 'esquina-mis-funciones'); ?>
                        </button>
                    </p>

                    <template id="esquina-fa-app-template-<?php echo esc_attr($instance['id']); ?>">
                        <?php esquina_fa_app_row('__INDEX__', [], $option); ?>
                    </template>
                </td>
            </tr>

        </table>

        <div class="esquina-fa-admin__savebar esquina-fa-admin__savebar--sticky">
            <?php submit_button(__('Guardar cambios', 'esquina-mis-funciones'), 'primary', 'submit', false); ?>
            <span class="esquina-fa-admin__savebar-hint">
                <?php esc_html_e('Se guarda al hacer clic; no hace falta bajar hasta el final.', 'esquina-mis-funciones'); ?>
            </span>
        </div>

    </form>

    <hr>

    <h2><?php esc_html_e('Volver a mostrar este panel', 'esquina-mis-funciones'); ?></h2>

    <p class="description">
        <?php esc_html_e('Solo afecta a esta instancia. Si usas caché de páginas, vacíala después.', 'esquina-mis-funciones'); ?>
    </p>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="<?php echo esc_attr($instance['reset_action']); ?>">
        <input type="hidden" name="instance" value="<?php echo esc_attr($instance['id']); ?>">
        <?php wp_nonce_field($instance['reset_action']); ?>
        <?php submit_button(__('Volver a mostrar a todos los visitantes', 'esquina-mis-funciones'), 'secondary', 'submit', false); ?>
    </form>

</div>

<style>
    .esquina-fa-admin__savebar {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin: 12px 0 18px;
    }

    .esquina-fa-admin__savebar .button {
        margin: 0;
    }

    .esquina-fa-admin__savebar--sticky {
        position: sticky;
        bottom: 12px;
        z-index: 20;
        margin: 24px 0 8px;
        padding: 12px 14px;
        border: 1px solid #c3c4c7;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        backdrop-filter: blur(6px);
    }

    .esquina-fa-admin__savebar-hint {
        color: #646970;
        font-size: 12px;
    }

    .esquina-fa-apps {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin: 12px 0;
        max-width: 1100px;
        align-items: start;
    }

    .esquina-fa-admin__row {
        margin: 0;
        padding: 12px;
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 8px;
        min-width: 0;
    }

    .esquina-fa-admin__row.is-dragging {
        opacity: 0.55;
    }

    .esquina-fa-admin__toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 8px;
    }

    .esquina-fa-admin__drag {
        cursor: grab;
    }

    .esquina-fa-admin__row .regular-text {
        width: 100%;
        max-width: 100%;
    }

    .esquina-fa-admin__preview {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 8px;
    }

    .esquina-fa-admin__svg {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        color: #fff;
    }

    .esquina-fa-admin__svg[hidden],
    .esquina-fa-admin__preview[hidden] {
        display: none;
    }

    @media (max-width: 1280px) {
        .esquina-fa-apps {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 782px) {
        .esquina-fa-apps {
            grid-template-columns: 1fr;
        }
    }
</style>

    <?php
}

/*
|--------------------------------------------------------------------------
| Fila del repetidor
|--------------------------------------------------------------------------
*/

function esquina_fa_app_row($index, $app, $option = 'esquina_floating_apps_settings')
{
    $icons = esquina_fa_icons();
    $svg   = isset($app['svg'], $icons[$app['svg']]) ? $app['svg'] : '';
    $icon  = isset($app['icon']) ? $app['icon'] : '';
    $name  = isset($app['name']) ? $app['name'] : '';
    $desc  = isset($app['desc']) ? $app['desc'] : '';
    $url   = isset($app['url']) ? $app['url'] : '';
    $base  = $option . '[apps][' . $index . ']';
    ?>
    <div class="esquina-fa-admin__row">
        <div class="esquina-fa-admin__toolbar">
            <button
                type="button"
                class="button esquina-fa-admin__drag"
                draggable="true"
                aria-label="<?php esc_attr_e('Arrastrar para reordenar', 'esquina-mis-funciones'); ?>">↕</button>
            <button
                type="button"
                class="button esquina-fa-admin__up"
                aria-label="<?php esc_attr_e('Subir', 'esquina-mis-funciones'); ?>">↑</button>
            <button
                type="button"
                class="button esquina-fa-admin__down"
                aria-label="<?php esc_attr_e('Bajar', 'esquina-mis-funciones'); ?>">↓</button>
            <button type="button" class="button esquina-fa-admin__remove">
                <?php esc_html_e('Eliminar', 'esquina-mis-funciones'); ?>
            </button>
        </div>

        <p>
            <label><?php esc_html_e('Icono predefinido', 'esquina-mis-funciones'); ?></label><br>
            <select class="esquina-fa-admin__svg-select" data-field="svg" name="<?php echo esc_attr($base); ?>[svg]">
                <option value=""><?php esc_html_e('— Usar imagen —', 'esquina-mis-funciones'); ?></option>
                <?php foreach ($icons as $key => $data) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($svg, $key); ?>>
                        <?php echo esc_html($data['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label><?php esc_html_e('Imagen', 'esquina-mis-funciones'); ?></label><br>
            <input
                type="url"
                class="regular-text esquina-fa-admin__icon"
                data-field="icon"
                name="<?php echo esc_attr($base); ?>[icon]"
                value="<?php echo esc_attr($icon); ?>">
            <button type="button" class="button esquina-fa-admin__pick">
                <?php esc_html_e('Elegir imagen', 'esquina-mis-funciones'); ?>
            </button>
        </p>

        <p>
            <span
                class="esquina-fa-admin__svg"
                style="background: <?php echo esc_attr($svg !== '' ? $icons[$svg]['color'] : '#2563eb'); ?>"
                <?php echo $svg === '' ? 'hidden' : ''; ?>>
                <?php echo $svg !== '' ? esquina_fa_svg_markup($svg) : ''; ?>
            </span>
            <img
                class="esquina-fa-admin__preview"
                alt=""
                width="40"
                height="40"
                src="<?php echo esc_url($icon); ?>"
                <?php echo ($svg !== '' || $icon === '') ? 'hidden' : ''; ?>>
        </p>

        <p>
            <label><?php esc_html_e('Nombre', 'esquina-mis-funciones'); ?></label><br>
            <input
                type="text"
                class="regular-text"
                required
                data-field="name"
                name="<?php echo esc_attr($base); ?>[name]"
                value="<?php echo esc_attr($name); ?>">
        </p>

        <p>
            <label><?php esc_html_e('Descripción corta', 'esquina-mis-funciones'); ?></label><br>
            <input
                type="text"
                class="regular-text"
                data-field="desc"
                name="<?php echo esc_attr($base); ?>[desc]"
                value="<?php echo esc_attr($desc); ?>">
        </p>

        <p>
            <label><?php esc_html_e('URL', 'esquina-mis-funciones'); ?></label><br>
            <input
                type="url"
                class="regular-text"
                required
                data-field="url"
                name="<?php echo esc_attr($base); ?>[url]"
                value="<?php echo esc_attr($url); ?>">
        </p>
    </div>
    <?php
}

/*
|--------------------------------------------------------------------------
| Script del repetidor
|--------------------------------------------------------------------------
*/

function esquina_fa_admin_script($instance)
{
    $i18n = [
        'max'         => __('Máximo 12 aplicaciones.', 'esquina-mis-funciones'),
        'required'    => __('Cada aplicación necesita nombre y URL.', 'esquina-mis-funciones'),
        'mediaTitle'  => __('Elegir icono', 'esquina-mis-funciones'),
        'mediaButton' => __('Usar esta imagen', 'esquina-mis-funciones'),
    ];

    $svgs = [];
    foreach (esquina_fa_icons() as $key => $data) {
        $svgs[$key] = [
            'color'  => $data['color'],
            'markup' => esquina_fa_svg_markup($key),
        ];
    }

    $id     = $instance['id'];
    $option = $instance['option'];
    ?>
    <script>
    (function () {
        const i18n = <?php echo wp_json_encode($i18n); ?>;
        const svgs = <?php echo wp_json_encode($svgs); ?>;
        const optionName = <?php echo wp_json_encode($option); ?>;
        const list = document.getElementById(<?php echo wp_json_encode('esquina-fa-apps-' . $id); ?>);
        const addButton = document.getElementById(<?php echo wp_json_encode('esquina-fa-add-' . $id); ?>);
        const form = document.getElementById(<?php echo wp_json_encode('esquina-fa-settings-form-' . $id); ?>);
        const template = document.getElementById(<?php echo wp_json_encode('esquina-fa-app-template-' . $id); ?>);
        const maxApps = 12;

        if (!list || !addButton || !form || !template) {
            return;
        }

        const rows = () => Array.from(list.querySelectorAll('.esquina-fa-admin__row'));

        const reindex = () => {
            rows().forEach((row, index) => {
                row.querySelectorAll('[data-field]').forEach((field) => {
                    field.name = optionName + '[apps][' + index + '][' + field.dataset.field + ']';
                });
            });
            addButton.disabled = rows().length >= maxApps;
        };

        const refreshPreview = (row) => {
            const select = row.querySelector('.esquina-fa-admin__svg-select');
            const input = row.querySelector('.esquina-fa-admin__icon');
            const svgBox = row.querySelector('.esquina-fa-admin__svg');
            const preview = row.querySelector('.esquina-fa-admin__preview');
            const key = select ? select.value : '';
            const icon = input ? input.value.trim() : '';

            if (svgBox) {
                if (key && svgs[key]) {
                    svgBox.innerHTML = svgs[key].markup;
                    svgBox.style.background = svgs[key].color;
                    svgBox.hidden = false;
                } else {
                    svgBox.innerHTML = '';
                    svgBox.hidden = true;
                }
            }

            if (preview) {
                preview.src = icon;
                preview.hidden = Boolean(key) || icon === '';
            }
        };

        const bindRow = (row) => {
            const handle = row.querySelector('.esquina-fa-admin__drag');
            if (!handle) {
                return;
            }

            handle.addEventListener('dragstart', (event) => {
                row.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', 'app');
            });

            handle.addEventListener('dragend', () => {
                row.classList.remove('is-dragging');
                reindex();
            });
        };

        const closestRow = (y) => {
            const items = rows().filter((row) => !row.classList.contains('is-dragging'));
            let nearest = null;
            let nearestOffset = Number.NEGATIVE_INFINITY;

            items.forEach((row) => {
                const box = row.getBoundingClientRect();
                const offset = y - box.top - (box.height / 2);
                if (offset < 0 && offset > nearestOffset) {
                    nearestOffset = offset;
                    nearest = row;
                }
            });

            return nearest;
        };

        list.addEventListener('dragover', (event) => {
            const dragging = list.querySelector('.esquina-fa-admin__row.is-dragging');
            if (!dragging) {
                return;
            }

            event.preventDefault();
            const target = closestRow(event.clientY);
            if (target) {
                list.insertBefore(dragging, target);
            } else {
                list.appendChild(dragging);
            }
        });

        list.addEventListener('click', (event) => {
            const row = event.target.closest('.esquina-fa-admin__row');
            if (!row) {
                return;
            }

            if (event.target.closest('.esquina-fa-admin__remove')) {
                row.remove();
                reindex();
                return;
            }

            if (event.target.closest('.esquina-fa-admin__up') && row.previousElementSibling) {
                list.insertBefore(row, row.previousElementSibling);
                reindex();
                return;
            }

            if (event.target.closest('.esquina-fa-admin__down') && row.nextElementSibling) {
                list.insertBefore(row.nextElementSibling, row);
                reindex();
                return;
            }

            if (event.target.closest('.esquina-fa-admin__pick')) {
                if (!window.wp || !wp.media) {
                    return;
                }

                const frame = wp.media({
                    title: i18n.mediaTitle,
                    button: { text: i18n.mediaButton },
                    multiple: false,
                    library: { type: 'image' }
                });

                frame.on('select', () => {
                    const attachment = frame.state().get('selection').first().toJSON();
                    const input = row.querySelector('.esquina-fa-admin__icon');
                    const select = row.querySelector('.esquina-fa-admin__svg-select');
                    if (input) {
                        input.value = attachment.url || '';
                    }
                    if (select) {
                        select.value = '';
                    }
                    refreshPreview(row);
                });

                frame.open();
            }
        });

        list.addEventListener('input', (event) => {
            if (!event.target.classList.contains('esquina-fa-admin__icon')) {
                return;
            }
            const row = event.target.closest('.esquina-fa-admin__row');
            if (row) {
                refreshPreview(row);
            }
        });

        list.addEventListener('change', (event) => {
            if (!event.target.classList.contains('esquina-fa-admin__svg-select')) {
                return;
            }
            const row = event.target.closest('.esquina-fa-admin__row');
            if (row) {
                refreshPreview(row);
            }
        });

        addButton.addEventListener('click', () => {
            if (rows().length >= maxApps) {
                window.alert(i18n.max);
                return;
            }

            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('.esquina-fa-admin__row');
            if (!row) {
                return;
            }

            list.appendChild(row);
            bindRow(row);
            reindex();
        });

        form.addEventListener('submit', (event) => {
            reindex();
            const current = rows();

            if (current.length > maxApps) {
                event.preventDefault();
                window.alert(i18n.max);
                return;
            }

            const invalid = current.some((row) => {
                const name = row.querySelector('[data-field="name"]');
                const url = row.querySelector('[data-field="url"]');
                return !name || !url || name.value.trim() === '' || url.value.trim() === '';
            });

            if (invalid) {
                event.preventDefault();
                window.alert(i18n.required);
            }
        });

        rows().forEach(bindRow);
        reindex();
    }());
    </script>
    <?php
}
