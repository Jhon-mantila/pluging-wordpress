<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Registro opciones
|--------------------------------------------------------------------------
*/

function esquina_youtube_register_settings()
{
    register_setting(
        'esquina_youtube_group',
        'esquina_youtube_settings'
    );
}

add_action(
    'admin_init',
    'esquina_youtube_register_settings'
);

/*
|--------------------------------------------------------------------------
| Menú
|--------------------------------------------------------------------------
*/

function esquina_youtube_menu()
{
    add_submenu_page(
        'esquinaweb-dashboard',
        'YouTube',
        'YouTube',
        'manage_options',
        'esquina-youtube',
        'esquina_youtube_page'
    );
}

add_action(
    'admin_menu',
    'esquina_youtube_menu'
);

/*
|--------------------------------------------------------------------------
| Vista
|--------------------------------------------------------------------------
*/

function esquina_youtube_page()
{
    $options = get_option(
        'esquina_youtube_settings',
        []
    );
?>

<div class="wrap">

    <h1>Configuración YouTube</h1>

    <form method="post" action="options.php">

        <?php settings_fields('esquina_youtube_group'); ?>

        <table class="form-table">

            <tr>
                <th>API Key</th>
                <td>

                    <div style="display:flex;gap:10px;align-items:center;">

                        <input
                            type="password"
                            id="esquina_yt_api_key"
                            name="esquina_youtube_settings[api_key]"
                            value="<?php echo esc_attr($options['api_key'] ?? ''); ?>"
                            class="regular-text">

                        <button
                            type="button"
                            class="button"
                            onclick="esquinaToggleApiKey()">
                            👁
                        </button>

                    </div>

                </td>
            </tr>

            <tr>
                <th>Channel ID</th>
                <td>

                    <input
                        type="text"
                        name="esquina_youtube_settings[channel_id]"
                        value="<?php echo esc_attr($options['channel_id'] ?? ''); ?>"
                        class="regular-text">

                </td>
            </tr>

            <tr>
                <th>Máximo Videos Largos</th>
                <td>

                    <input
                        type="number"
                        min="1"
                        max="100"
                        name="esquina_youtube_settings[max_largos]"
                        value="<?php echo esc_attr($options['max_largos'] ?? 6); ?>">

                </td>
            </tr>

            <tr>
                <th>Columnas Largos</th>
                <td>

                    <input
                        type="number"
                        min="1"
                        max="6"
                        name="esquina_youtube_settings[columns_largos]"
                        value="<?php echo esc_attr($options['columns_largos'] ?? 3); ?>">

                </td>
            </tr>

            <tr>
                <th>Batch Largos</th>
                <td>

                    <input
                        type="number"
                        min="1"
                        max="50"
                        name="esquina_youtube_settings[batch_largos]"
                        value="<?php echo esc_attr($options['batch_largos'] ?? 6); ?>">

                </td>
            </tr>

            <tr>
                <th>Máximo Shorts</th>
                <td>

                    <input
                        type="number"
                        min="1"
                        max="100"
                        name="esquina_youtube_settings[max_shorts]"
                        value="<?php echo esc_attr($options['max_shorts'] ?? 6); ?>">

                </td>
            </tr>

            <tr>
                <th>Columnas Shorts</th>
                <td>

                    <input
                        type="number"
                        min="1"
                        max="6"
                        name="esquina_youtube_settings[columns_shorts]"
                        value="<?php echo esc_attr($options['columns_shorts'] ?? 4); ?>">

                </td>
            </tr>

            <tr>
                <th>Batch Shorts</th>
                <td>

                    <input
                        type="number"
                        min="1"
                        max="50"
                        name="esquina_youtube_settings[batch_shorts]"
                        value="<?php echo esc_attr($options['batch_shorts'] ?? 8); ?>">

                </td>
            </tr>

        </table>

        <?php submit_button(); ?>

    </form>

</div>

<script>

function esquinaToggleApiKey()
{
    const field =
        document.getElementById(
            'esquina_yt_api_key'
        );

    field.type =
        field.type === 'password'
        ? 'text'
        : 'password';
}

</script>

<?php
}