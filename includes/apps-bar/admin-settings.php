<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Iconos SVG compartidos (Floating Apps)
|--------------------------------------------------------------------------
*/

function esquina_ab_load_icons()
{
    if (!function_exists('esquina_fa_icons')) {
        require_once ESQUINA_MF_PATH . 'includes/floating-apps/icons.php';
    }

    return esquina_fa_icons();
}

function esquina_ab_svg_markup($key, $size = 24)
{
    if (!function_exists('esquina_fa_svg_markup')) {
        require_once ESQUINA_MF_PATH . 'includes/floating-apps/icons.php';
    }

    return esquina_fa_svg_markup($key, $size);
}

/*
|--------------------------------------------------------------------------
| Registro opciones
|--------------------------------------------------------------------------
*/

function esquina_ab_register_settings()
{
    // Sin 'type' => 'array': en algunas versiones WP valida el esquema y puede
    // vaciar la opción al guardar arrays anidados (apps).
    register_setting(
        'esquina_apps_bar_group',
        'esquina_apps_bar_settings',
        'esquina_ab_sanitize_settings'
    );
}
add_action('admin_init', 'esquina_ab_register_settings');

function esquina_ab_style_options()
{
    return [
        'panel'  => [
            'label' => __('Panel suave', 'esquina-mis-funciones'),
            'desc'  => __('Tarjeta con degradado claro, borde y título con badge.', 'esquina-mis-funciones'),
        ],
        'banda'  => [
            'label' => __('Banda destacada', 'esquina-mis-funciones'),
            'desc'  => __('Franja con degradado fuerte, título claro e iconos sobre banda translúcida.', 'esquina-mis-funciones'),
        ],
        'chips'  => [
            'label' => __('Chips con nombre', 'esquina-mis-funciones'),
            'desc'  => __('Cada app muestra icono + nombre debajo, en una rejilla ordenada.', 'esquina-mis-funciones'),
        ],
    ];
}

function esquina_ab_sanitize_hex_color($color, $fallback)
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

function esquina_ab_normalize_url($url)
{
    $url = is_string($url) ? trim($url) : '';
    if ($url === '') {
        return '';
    }

    // Si pegaron el dominio sin protocolo, intenta completar.
    if (!preg_match('#^https?://#i', $url) && preg_match('#^[\w.-]+\.[a-z]{2,}(/.*)?$#i', $url)) {
        $url = 'https://' . $url;
    }

    return esc_url_raw($url);
}

function esquina_ab_sanitize_settings($input)
{
    $input = is_array($input) ? $input : [];
    $icons = esquina_ab_load_icons();
    if (!is_array($icons)) {
        $icons = [];
    }

    $title = isset($input['title']) ? sanitize_text_field($input['title']) : '';
    if ($title === '') {
        $title = 'Nuestras aplicaciones';
    }

    $style = isset($input['style']) ? sanitize_text_field($input['style']) : 'panel';
    if (!array_key_exists($style, esquina_ab_style_options())) {
        $style = 'panel';
    }

    $clean = [
        'enabled' => empty($input['enabled']) ? 0 : 1,
        'title'   => $title,
        'style'   => $style,
        'color_1' => esquina_ab_sanitize_hex_color($input['color_1'] ?? '', '#2563eb'),
        'color_2' => esquina_ab_sanitize_hex_color($input['color_2'] ?? '', '#0ea5e9'),
        'apps'    => [],
    ];

    if (empty($input['apps']) || !is_array($input['apps'])) {
        return $clean;
    }

    foreach ($input['apps'] as $app) {
        if (count($clean['apps']) >= 12 || !is_array($app)) {
            continue;
        }

        $name = sanitize_text_field($app['name'] ?? '');
        $url  = esquina_ab_normalize_url($app['url'] ?? '');

        if ($name === '' || $url === '') {
            continue;
        }

        $svg = sanitize_key($app['svg'] ?? '');
        if ($svg === '' || !isset($icons[$svg])) {
            $svg = '';
        }

        $icon = esquina_ab_normalize_url($app['icon'] ?? '');

        // Hace falta imagen o SVG; si no hay ninguno, se descarta.
        if ($svg === '' && $icon === '') {
            continue;
        }

        $clean['apps'][] = [
            'svg'  => $svg,
            'icon' => $icon,
            'name' => $name,
            'desc' => sanitize_text_field($app['desc'] ?? ''),
            'url'  => $url,
        ];
    }

    return $clean;
}

/*
|--------------------------------------------------------------------------
| Menú
|--------------------------------------------------------------------------
*/

function esquina_ab_menu()
{
    $hook = add_submenu_page(
        'esquinaweb-dashboard',
        __('Apps en entradas', 'esquina-mis-funciones'),
        __('Apps en entradas', 'esquina-mis-funciones'),
        'manage_options',
        'esquina-apps-bar',
        'esquina_ab_page'
    );

    add_action('admin_enqueue_scripts', function ($current) use ($hook) {
        if ($current !== $hook) {
            return;
        }

        wp_enqueue_media();
    });

    add_action('admin_footer', function () use ($hook) {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (!$screen || $screen->id !== $hook) {
            return;
        }

        esquina_ab_admin_script();
    });
}
add_action('admin_menu', 'esquina_ab_menu');

/*
|--------------------------------------------------------------------------
| Vista
|--------------------------------------------------------------------------
*/

function esquina_ab_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = get_option('esquina_apps_bar_settings', []);
    if (!is_array($options)) {
        $options = [];
    }

    $apps = isset($options['apps']) && is_array($options['apps']) ? $options['apps'] : [];
    $apps = array_slice($apps, 0, 12);

    ?>

<div class="wrap">

    <h1><?php esc_html_e('Apps en entradas', 'esquina-mis-funciones'); ?></h1>

    <p class="description">
        <?php esc_html_e('Muestra una fila de iconos de aplicaciones al inicio de cada entrada individual (single post).', 'esquina-mis-funciones'); ?>
    </p>

    <form method="post" action="options.php" id="esquina-ab-settings-form">

        <?php settings_fields('esquina_apps_bar_group'); ?>

        <table class="form-table">

            <tr>
                <th><?php esc_html_e('Activar fila de iconos', 'esquina-mis-funciones'); ?></th>
                <td>
                    <input
                        type="checkbox"
                        name="esquina_apps_bar_settings[enabled]"
                        value="1"
                        <?php checked(!empty($options['enabled'])); ?>>
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Título de la fila', 'esquina-mis-funciones'); ?></th>
                <td>
                    <input
                        type="text"
                        name="esquina_apps_bar_settings[title]"
                        value="<?php echo esc_attr($options['title'] ?? 'Nuestras aplicaciones'); ?>"
                        class="regular-text">
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Estilo visual', 'esquina-mis-funciones'); ?></th>
                <td>
                    <?php
                    $current_style = $options['style'] ?? 'panel';
                    foreach (esquina_ab_style_options() as $value => $meta) :
                        ?>
                        <label style="display:block;margin:0 0 10px;">
                            <input
                                type="radio"
                                name="esquina_apps_bar_settings[style]"
                                value="<?php echo esc_attr($value); ?>"
                                <?php checked($current_style, $value); ?>>
                            <strong><?php echo esc_html($meta['label']); ?></strong>
                            <span class="description" style="display:inline;margin-left:6px;">
                                — <?php echo esc_html($meta['desc']); ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Colores del degradado', 'esquina-mis-funciones'); ?></th>
                <td>
                    <label style="margin-right:16px;">
                        <?php esc_html_e('Color 1', 'esquina-mis-funciones'); ?>
                        <input
                            type="color"
                            name="esquina_apps_bar_settings[color_1]"
                            value="<?php echo esc_attr($options['color_1'] ?? '#2563eb'); ?>">
                    </label>
                    <label>
                        <?php esc_html_e('Color 2', 'esquina-mis-funciones'); ?>
                        <input
                            type="color"
                            name="esquina_apps_bar_settings[color_2]"
                            value="<?php echo esc_attr($options['color_2'] ?? '#0ea5e9'); ?>">
                    </label>
                    <p class="description">
                        <?php esc_html_e('Se usan en el fondo, el badge y los acentos de los tres estilos. Guarda para aplicar la combinación.', 'esquina-mis-funciones'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Aplicaciones', 'esquina-mis-funciones'); ?></th>
                <td>
                    <p class="description">
                        <?php esc_html_e('Hasta 12 aplicaciones. Nombre y URL son obligatorios. Usa un icono SVG predefinido o una imagen personalizada.', 'esquina-mis-funciones'); ?>
                    </p>

                    <div id="esquina-ab-apps">
                        <?php foreach ($apps as $index => $app) : ?>
                            <?php esquina_ab_app_row((int) $index, is_array($app) ? $app : []); ?>
                        <?php endforeach; ?>
                    </div>

                    <p>
                        <button type="button" class="button button-secondary" id="esquina-ab-add">
                            <?php esc_html_e('Añadir aplicación', 'esquina-mis-funciones'); ?>
                        </button>
                    </p>

                    <template id="esquina-ab-app-template">
                        <?php esquina_ab_app_row('__INDEX__', []); ?>
                    </template>
                </td>
            </tr>

        </table>

        <?php submit_button(); ?>

    </form>

</div>

<style>
    #esquina-ab-apps {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin: 12px 0;
        max-width: 1100px;
        align-items: start;
    }

    .esquina-ab-admin__row {
        margin: 0;
        padding: 12px;
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 8px;
        min-width: 0;
    }

    .esquina-ab-admin__row.is-dragging {
        opacity: 0.55;
    }

    .esquina-ab-admin__toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 8px;
    }

    .esquina-ab-admin__drag {
        cursor: grab;
    }

    .esquina-ab-admin__row .regular-text {
        width: 100%;
        max-width: 100%;
    }

    .esquina-ab-admin__preview {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 8px;
    }

    .esquina-ab-admin__svg {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        color: #fff;
    }

    .esquina-ab-admin__svg[hidden],
    .esquina-ab-admin__preview[hidden] {
        display: none;
    }

    @media (max-width: 1280px) {
        #esquina-ab-apps {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 782px) {
        #esquina-ab-apps {
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

function esquina_ab_app_row($index, $app)
{
    $icons = esquina_ab_load_icons();
    $svg   = isset($app['svg'], $icons[$app['svg']]) ? $app['svg'] : '';
    $icon  = isset($app['icon']) ? $app['icon'] : '';
    $name  = isset($app['name']) ? $app['name'] : '';
    $desc  = isset($app['desc']) ? $app['desc'] : '';
    $url   = isset($app['url']) ? $app['url'] : '';
    $base  = 'esquina_apps_bar_settings[apps][' . $index . ']';
    ?>
    <div class="esquina-ab-admin__row">
        <div class="esquina-ab-admin__toolbar">
            <button
                type="button"
                class="button esquina-ab-admin__drag"
                draggable="true"
                aria-label="<?php esc_attr_e('Arrastrar para reordenar', 'esquina-mis-funciones'); ?>">↕</button>
            <button
                type="button"
                class="button esquina-ab-admin__up"
                aria-label="<?php esc_attr_e('Subir', 'esquina-mis-funciones'); ?>">↑</button>
            <button
                type="button"
                class="button esquina-ab-admin__down"
                aria-label="<?php esc_attr_e('Bajar', 'esquina-mis-funciones'); ?>">↓</button>
            <button type="button" class="button esquina-ab-admin__remove">
                <?php esc_html_e('Eliminar', 'esquina-mis-funciones'); ?>
            </button>
        </div>

        <p>
            <label><?php esc_html_e('Icono SVG predefinido', 'esquina-mis-funciones'); ?></label><br>
            <select class="esquina-ab-admin__svg-select" data-field="svg" name="<?php echo esc_attr($base); ?>[svg]">
                <option value=""><?php esc_html_e('— Usar imagen —', 'esquina-mis-funciones'); ?></option>
                <?php foreach ($icons as $key => $data) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($svg, $key); ?>>
                        <?php echo esc_html($data['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label><?php esc_html_e('Imagen personalizada', 'esquina-mis-funciones'); ?></label><br>
            <input
                type="url"
                class="regular-text esquina-ab-admin__icon"
                data-field="icon"
                name="<?php echo esc_attr($base); ?>[icon]"
                value="<?php echo esc_attr($icon); ?>">
            <button type="button" class="button esquina-ab-admin__pick">
                <?php esc_html_e('Elegir imagen', 'esquina-mis-funciones'); ?>
            </button>
        </p>

        <p>
            <span
                class="esquina-ab-admin__svg"
                style="background: <?php echo esc_attr($svg !== '' ? $icons[$svg]['color'] : '#2563eb'); ?>"
                <?php echo $svg === '' ? 'hidden' : ''; ?>>
                <?php echo $svg !== '' ? esquina_ab_svg_markup($svg) : ''; ?>
            </span>
            <img
                class="esquina-ab-admin__preview"
                alt=""
                width="40"
                height="40"
                src="<?php echo esc_url($icon); ?>"
                <?php echo ($svg !== '' || $icon === '') ? 'hidden' : ''; ?>>
        </p>

        <p>
            <label><?php esc_html_e('Nombre (tooltip)', 'esquina-mis-funciones'); ?></label><br>
            <input
                type="text"
                class="regular-text"
                required
                data-field="name"
                name="<?php echo esc_attr($base); ?>[name]"
                value="<?php echo esc_attr($name); ?>">
        </p>

        <p>
            <label><?php esc_html_e('Descripción breve (tooltip)', 'esquina-mis-funciones'); ?></label><br>
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
| Script del repetidor (solo esta pantalla)
|--------------------------------------------------------------------------
*/

function esquina_ab_admin_script()
{
    $i18n = [
        'max'         => __('Máximo 12 aplicaciones.', 'esquina-mis-funciones'),
        'required'    => __('Cada aplicación necesita nombre y URL.', 'esquina-mis-funciones'),
        'iconNeeded'  => __('Cada aplicación necesita un icono SVG o una imagen.', 'esquina-mis-funciones'),
        'mediaTitle'  => __('Elegir icono', 'esquina-mis-funciones'),
        'mediaButton' => __('Usar esta imagen', 'esquina-mis-funciones'),
    ];

    $svgs = [];
    foreach (esquina_ab_load_icons() as $key => $data) {
        $svgs[$key] = [
            'color'  => $data['color'],
            'markup' => esquina_ab_svg_markup($key),
        ];
    }
    ?>
    <script>
    (function () {
        const i18n = <?php echo wp_json_encode($i18n); ?>;
        const svgs = <?php echo wp_json_encode($svgs); ?>;
        const list = document.getElementById('esquina-ab-apps');
        const addButton = document.getElementById('esquina-ab-add');
        const form = document.getElementById('esquina-ab-settings-form');
        const template = document.getElementById('esquina-ab-app-template');
        const maxApps = 12;

        if (!list || !addButton || !form || !template) {
            return;
        }

        const rows = () => Array.from(list.querySelectorAll('.esquina-ab-admin__row'));

        const reindex = () => {
            rows().forEach((row, index) => {
                row.querySelectorAll('[data-field]').forEach((field) => {
                    field.name = 'esquina_apps_bar_settings[apps][' + index + '][' + field.dataset.field + ']';
                });
            });
            addButton.disabled = rows().length >= maxApps;
        };

        const refreshPreview = (row) => {
            const select = row.querySelector('.esquina-ab-admin__svg-select');
            const input = row.querySelector('.esquina-ab-admin__icon');
            const svgBox = row.querySelector('.esquina-ab-admin__svg');
            const preview = row.querySelector('.esquina-ab-admin__preview');
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
            const handle = row.querySelector('.esquina-ab-admin__drag');

            if (handle) {
                handle.addEventListener('dragstart', (event) => {
                    row.classList.add('is-dragging');
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', 'app');
                });

                handle.addEventListener('dragend', () => {
                    row.classList.remove('is-dragging');
                    reindex();
                });
            }
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
            const dragging = list.querySelector('.esquina-ab-admin__row.is-dragging');
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
            const row = event.target.closest('.esquina-ab-admin__row');
            if (!row) {
                return;
            }

            if (event.target.closest('.esquina-ab-admin__remove')) {
                row.remove();
                reindex();
                return;
            }

            if (event.target.closest('.esquina-ab-admin__up') && row.previousElementSibling) {
                list.insertBefore(row, row.previousElementSibling);
                reindex();
                return;
            }

            if (event.target.closest('.esquina-ab-admin__down') && row.nextElementSibling) {
                list.insertBefore(row.nextElementSibling, row);
                reindex();
                return;
            }

            if (event.target.closest('.esquina-ab-admin__pick')) {
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
                    const input = row.querySelector('.esquina-ab-admin__icon');
                    const select = row.querySelector('.esquina-ab-admin__svg-select');
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
            if (!event.target.classList.contains('esquina-ab-admin__icon')) {
                return;
            }

            const row = event.target.closest('.esquina-ab-admin__row');
            if (row) {
                refreshPreview(row);
            }
        });

        list.addEventListener('change', (event) => {
            if (!event.target.classList.contains('esquina-ab-admin__svg-select')) {
                return;
            }

            const row = event.target.closest('.esquina-ab-admin__row');
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
            const row = fragment.querySelector('.esquina-ab-admin__row');
            if (!row) {
                return;
            }

            list.appendChild(row);
            bindRow(row);
            reindex();
        });

        form.addEventListener('submit', (event) => {
            // Reindexar siempre antes de enviar (grid / drag & drop).
            reindex();

            const current = rows();

            if (current.length > maxApps) {
                event.preventDefault();
                window.alert(i18n.max);
                return;
            }

            const missingFields = current.some((row) => {
                const name = row.querySelector('[data-field="name"]');
                const url = row.querySelector('[data-field="url"]');
                return !name || !url || name.value.trim() === '' || url.value.trim() === '';
            });

            if (missingFields) {
                event.preventDefault();
                window.alert(i18n.required);
                return;
            }

            const missingIcon = current.some((row) => {
                const svg = row.querySelector('[data-field="svg"]');
                const icon = row.querySelector('[data-field="icon"]');
                return (!svg || svg.value === '') && (!icon || icon.value.trim() === '');
            });

            if (missingIcon) {
                event.preventDefault();
                window.alert(i18n.iconNeeded);
            }
        });

        rows().forEach(bindRow);
        reindex();
    }());
    </script>
    <?php
}
