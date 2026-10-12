<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Registro opciones
|--------------------------------------------------------------------------
*/

function esquina_fa_register_settings()
{
    register_setting(
        'esquina_floating_apps_group',
        'esquina_floating_apps_settings',
        [
            'type'              => 'array',
            'sanitize_callback' => 'esquina_fa_sanitize_settings',
            'default'           => [
                'enabled'  => 0,
                'position' => 'bottom-right',
                'delay'    => 2,
                'label'    => 'Apps de Esquina',
                'apps'     => [],
            ],
        ]
    );
}
add_action('admin_init', 'esquina_fa_register_settings');

/*
|--------------------------------------------------------------------------
| Sanitizar
|--------------------------------------------------------------------------
*/

function esquina_fa_sanitize_settings($input)
{
    $positions = ['bottom-right', 'bottom-left', 'top-right', 'top-left'];
    $input     = is_array($input) ? $input : [];

    $position = isset($input['position']) ? sanitize_text_field($input['position']) : 'bottom-right';
    if (!in_array($position, $positions, true)) {
        $position = 'bottom-right';
    }

    $label = isset($input['label']) ? sanitize_text_field($input['label']) : '';
    if ($label === '') {
        $label = 'Apps de Esquina';
    }

    $clean = [
        'enabled'  => empty($input['enabled']) ? 0 : 1,
        'position' => $position,
        'delay'    => isset($input['delay']) ? absint($input['delay']) : 2,
        'label'    => $label,
        'apps'     => [],
    ];

    if (empty($input['apps']) || !is_array($input['apps'])) {
        return $clean;
    }

    foreach ($input['apps'] as $app) {
        if (count($clean['apps']) >= 4 || !is_array($app)) {
            continue;
        }

        $name = sanitize_text_field($app['name'] ?? '');
        $url  = esc_url_raw($app['url'] ?? '');

        if ($name === '' || $url === '') {
            continue;
        }

        $clean['apps'][] = [
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
| Menú
|--------------------------------------------------------------------------
*/

function esquina_fa_menu()
{
    $hook = add_submenu_page(
        'esquinaweb-dashboard',
        __('Floating Apps', 'esquina-mis-funciones'),
        __('Floating Apps', 'esquina-mis-funciones'),
        'manage_options',
        'esquina-floating-apps',
        'esquina_fa_page'
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

        esquina_fa_admin_script();
    });
}
add_action('admin_menu', 'esquina_fa_menu');

/*
|--------------------------------------------------------------------------
| Vista
|--------------------------------------------------------------------------
*/

function esquina_fa_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = get_option('esquina_floating_apps_settings', []);
    if (!is_array($options)) {
        $options = [];
    }

    $apps = isset($options['apps']) && is_array($options['apps']) ? $options['apps'] : [];
    $apps = array_slice($apps, 0, 4);

    $positions = [
        'bottom-right' => __('Inferior derecha', 'esquina-mis-funciones'),
        'bottom-left'  => __('Inferior izquierda', 'esquina-mis-funciones'),
        'top-right'    => __('Superior derecha', 'esquina-mis-funciones'),
        'top-left'     => __('Superior izquierda', 'esquina-mis-funciones'),
    ];

    ?>

<div class="wrap">

    <h1><?php esc_html_e('Floating Apps', 'esquina-mis-funciones'); ?></h1>

    <form method="post" action="options.php" id="esquina-fa-settings-form">

        <?php settings_fields('esquina_floating_apps_group'); ?>

        <table class="form-table">

            <tr>
                <th><?php esc_html_e('Activar widget flotante', 'esquina-mis-funciones'); ?></th>
                <td>
                    <input
                        type="checkbox"
                        name="esquina_floating_apps_settings[enabled]"
                        value="1"
                        <?php checked(!empty($options['enabled'])); ?>>
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Posición del widget', 'esquina-mis-funciones'); ?></th>
                <td>
                    <select name="esquina_floating_apps_settings[position]">
                        <?php foreach ($positions as $value => $label) : ?>
                            <option
                                value="<?php echo esc_attr($value); ?>"
                                <?php selected($options['position'] ?? 'bottom-right', $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Retardo antes de aparecer (segundos)', 'esquina-mis-funciones'); ?></th>
                <td>
                    <input
                        type="number"
                        min="0"
                        step="1"
                        name="esquina_floating_apps_settings[delay]"
                        value="<?php echo esc_attr((string) absint($options['delay'] ?? 2)); ?>"
                        class="small-text">
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Título del panel', 'esquina-mis-funciones'); ?></th>
                <td>
                    <input
                        type="text"
                        name="esquina_floating_apps_settings[label]"
                        value="<?php echo esc_attr($options['label'] ?? 'Apps de Esquina'); ?>"
                        class="regular-text">
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Aplicaciones', 'esquina-mis-funciones'); ?></th>
                <td>
                    <p class="description">
                        <?php esc_html_e('Hasta 4 aplicaciones. El nombre y la URL son obligatorios.', 'esquina-mis-funciones'); ?>
                    </p>

                    <div id="esquina-fa-apps">
                        <?php foreach ($apps as $index => $app) : ?>
                            <?php esquina_fa_app_row((int) $index, is_array($app) ? $app : []); ?>
                        <?php endforeach; ?>
                    </div>

                    <p>
                        <button type="button" class="button button-secondary" id="esquina-fa-add">
                            <?php esc_html_e('Añadir aplicación', 'esquina-mis-funciones'); ?>
                        </button>
                    </p>

                    <template id="esquina-fa-app-template">
                        <?php esquina_fa_app_row('__INDEX__', []); ?>
                    </template>
                </td>
            </tr>

        </table>

        <?php submit_button(); ?>

    </form>

</div>

<style>
    .esquina-fa-admin__row {
        margin: 0 0 12px;
        padding: 12px;
        background: #fff;
        border: 1px solid #dcdcde;
        border-radius: 8px;
        max-width: 720px;
    }

    .esquina-fa-admin__row.is-dragging {
        opacity: 0.55;
    }

    .esquina-fa-admin__toolbar {
        display: flex;
        gap: 6px;
        margin-bottom: 8px;
    }

    .esquina-fa-admin__drag {
        cursor: grab;
    }

    .esquina-fa-admin__preview {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 8px;
    }
</style>

    <?php
}

/*
|--------------------------------------------------------------------------
| Fila del repetidor
|--------------------------------------------------------------------------
*/

function esquina_fa_app_row($index, $app)
{
    $icon = isset($app['icon']) ? $app['icon'] : '';
    $name = isset($app['name']) ? $app['name'] : '';
    $desc = isset($app['desc']) ? $app['desc'] : '';
    $url  = isset($app['url']) ? $app['url'] : '';
    $base = 'esquina_floating_apps_settings[apps][' . $index . ']';
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
            <label><?php esc_html_e('Icono', 'esquina-mis-funciones'); ?></label><br>
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
            <img
                class="esquina-fa-admin__preview"
                alt=""
                width="40"
                height="40"
                src="<?php echo esc_url($icon); ?>"
                <?php echo $icon === '' ? 'hidden' : ''; ?>>
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
| Script del repetidor (solo esta pantalla)
|--------------------------------------------------------------------------
*/

function esquina_fa_admin_script()
{
    $i18n = [
        'max'         => __('Máximo 4 aplicaciones.', 'esquina-mis-funciones'),
        'required'    => __('Cada aplicación necesita nombre y URL.', 'esquina-mis-funciones'),
        'mediaTitle'  => __('Elegir icono', 'esquina-mis-funciones'),
        'mediaButton' => __('Usar esta imagen', 'esquina-mis-funciones'),
    ];
    ?>
    <script>
    (function () {
        const i18n = <?php echo wp_json_encode($i18n); ?>;
        const list = document.getElementById('esquina-fa-apps');
        const addButton = document.getElementById('esquina-fa-add');
        const form = document.getElementById('esquina-fa-settings-form');
        const template = document.getElementById('esquina-fa-app-template');
        const maxApps = 4;

        if (!list || !addButton || !form || !template) {
            return;
        }

        const rows = () => Array.from(list.querySelectorAll('.esquina-fa-admin__row'));

        const reindex = () => {
            rows().forEach((row, index) => {
                row.querySelectorAll('[data-field]').forEach((field) => {
                    field.name = 'esquina_floating_apps_settings[apps][' + index + '][' + field.dataset.field + ']';
                });
            });
            addButton.disabled = rows().length >= maxApps;
        };

        const bindRow = (row) => {
            const handle = row.querySelector('.esquina-fa-admin__drag');

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
                    const preview = row.querySelector('.esquina-fa-admin__preview');
                    if (input) {
                        input.value = attachment.url || '';
                    }
                    if (preview) {
                        preview.src = attachment.url || '';
                        preview.hidden = !attachment.url;
                    }
                });

                frame.open();
            }
        });

        list.addEventListener('input', (event) => {
            if (!event.target.classList.contains('esquina-fa-admin__icon')) {
                return;
            }

            const row = event.target.closest('.esquina-fa-admin__row');
            const preview = row ? row.querySelector('.esquina-fa-admin__preview') : null;
            if (!preview) {
                return;
            }

            preview.src = event.target.value;
            preview.hidden = event.target.value === '';
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
