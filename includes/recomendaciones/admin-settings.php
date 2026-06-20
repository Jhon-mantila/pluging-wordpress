<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Registro opciones
|--------------------------------------------------------------------------
*/

function esquina_recomendaciones_register_settings()
{
    register_setting(
        'esquina_recomendaciones_group',
        'esquina_recomendaciones_settings'
    );
}
add_action('admin_init', 'esquina_recomendaciones_register_settings');

/*
|--------------------------------------------------------------------------
| Menú
|--------------------------------------------------------------------------
*/

function esquina_recomendaciones_menu()
{
    // Menú EsquinaWeb
    add_submenu_page(
        'esquinaweb-dashboard',
        'Recomendaciones',
        'Recomendaciones',
        'manage_options',
        'esquina-recomendaciones',
        'esquina_recomendaciones_page'
    );
}
add_action('admin_menu', 'esquina_recomendaciones_menu');

/*
|--------------------------------------------------------------------------
| Vista
|--------------------------------------------------------------------------
*/

function esquina_recomendaciones_page()
{
    $options = get_option('esquina_recomendaciones_settings');

?>

<div class="wrap">

    <h1>Recomendaciones Automáticas</h1>

    <form method="post" action="options.php">

        <?php
        settings_fields('esquina_recomendaciones_group');
        ?>

        <table class="form-table">

            <tr>
                <th>Activar</th>
                <td>
                    <input
                        type="checkbox"
                        name="esquina_recomendaciones_settings[enabled]"
                        value="1"
                        <?php checked($options['enabled'] ?? '', 1); ?>>
                </td>
            </tr>

            <tr>
                <th>Título</th>
                <td>
                    <input
                        type="text"
                        name="esquina_recomendaciones_settings[title]"
                        value="<?php echo esc_attr($options['title'] ?? 'Recomendaciones'); ?>"
                        class="regular-text">
                </td>
            </tr>

            <tr>
                <th>Cantidad</th>
                <td>

                    <select name="esquina_recomendaciones_settings[posts_per_page]">

                        <?php

                        foreach ([2,3,4,5,6,8] as $num)
                        {
                            ?>
                            <option
                                value="<?php echo $num; ?>"
                                <?php selected($options['posts_per_page'] ?? 6, $num); ?>>
                                <?php echo $num; ?>
                            </option>
                            <?php
                        }

                        ?>

                    </select>

                </td>
            </tr>

            <tr>
                <th>Modo</th>
                <td>

                    <select name="esquina_recomendaciones_settings[mode]">

                        <option value="latest"
                            <?php selected($options['mode'] ?? '', 'latest'); ?>>
                            Últimos artículos
                        </option>

                        <option value="random"
                            <?php selected($options['mode'] ?? '', 'random'); ?>>
                            Aleatorios
                        </option>

                    </select>

                </td>
            </tr>

            <tr>
                <th>Diseño</th>
                <td>

                    <select name="esquina_recomendaciones_settings[layout]">

                        <option value="grid"
                            <?php selected($options['layout'] ?? 'grid', 'grid'); ?>>
                            Grid
                        </option>

                        <option value="carousel"
                            <?php selected($options['layout'] ?? '', 'carousel'); ?>>
                            Carrusel Netflix
                        </option>

                    </select>

                </td>
            </tr>
            <?php if ($options['layout'] === 'carousel') : ?>
            <tr>
                <th>Autoplay</th>
                <td>
                    <input
                        type="checkbox"
                        name="esquina_recomendaciones_settings[autoplay]"
                        value="1"
                        <?php checked($options['autoplay'] ?? '', 1); ?>>
                </td>
            </tr>
            
            <tr>
                <th>Velocidad</th>
                <td>

                    <select name="esquina_recomendaciones_settings[speed]">

                        <option value="5000"
                            <?php selected($options['speed'] ?? 5000, 5000); ?>>
                            Lenta (5s)
                        </option>

                        <option value="3000"
                            <?php selected($options['speed'] ?? '', 3000); ?>>
                            Media (3s)
                        </option>

                        <option value="2000"
                            <?php selected($options['speed'] ?? '', 2000); ?>>
                            Rápida (2s)
                        </option>

                    </select>

                </td>
            </tr>

            <tr>
                <th>Tamaño</th>
                <td>

                    <select name="esquina_recomendaciones_settings[size]">

                        <option value="small"
                            <?php selected($options['size'] ?? 'medium', 'small'); ?>>
                            Pequeño
                        </option>

                        <option value="medium"
                            <?php selected($options['size'] ?? 'medium', 'medium'); ?>>
                            Mediano
                        </option>

                        <option value="large"
                            <?php selected($options['size'] ?? '', 'large'); ?>>
                            Grande
                        </option>

                    </select>

                </td>
            </tr>

            <?php endif; ?>

            <tr>
                <th>Mostrar miniatura</th>
                <td>
                    <input
                        type="checkbox"
                        name="esquina_recomendaciones_settings[thumbnail]"
                        value="1"
                        <?php checked($options['thumbnail'] ?? '', 1); ?>>
                </td>
            </tr>

            <tr>
                <th>Mostrar enlace categoría</th>
                <td>
                    <input
                        type="checkbox"
                        name="esquina_recomendaciones_settings[category_link]"
                        value="1"
                        <?php checked($options['category_link'] ?? '', 1); ?>>
                </td>
            </tr>

        </table>

        <?php submit_button(); ?>

    </form>

</div>

<?php
}