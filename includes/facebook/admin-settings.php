<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Registro
|--------------------------------------------------------------------------
*/

function esquina_facebook_register_settings()
{
    register_setting(
        'esquina_facebook_group',
        'esquina_facebook_settings'
    );
}

add_action(
    'admin_init',
    'esquina_facebook_register_settings'
);

/*
|--------------------------------------------------------------------------
| Menú
|--------------------------------------------------------------------------
*/

function esquina_facebook_menu()
{
    add_submenu_page(
        'esquinaweb-dashboard',
        'Facebook',
        'Facebook',
        'manage_options',
        'esquina-facebook',
        'esquina_facebook_page'
    );
}

add_action(
    'admin_menu',
    'esquina_facebook_menu'
);

/*
|--------------------------------------------------------------------------
| Vista
|--------------------------------------------------------------------------
*/

function esquina_facebook_page()
{
    $options = get_option(
        'esquina_facebook_settings',
        []
    );
?>

<div class="wrap">

    <h1>Configuración Facebook</h1>

    <form method="post" action="options.php">

        <?php settings_fields('esquina_facebook_group'); ?>

        <table class="form-table">

            <tr>

                <th>Access Token</th>

                <td>

                    <div style="display:flex;gap:10px;align-items:center;">

                        <input
                            type="password"
                            id="esquina_fb_token"
                            name="esquina_facebook_settings[access_token]"
                            value="<?php echo esc_attr($options['access_token'] ?? ''); ?>"
                            class="regular-text">

                        <button
                            type="button"
                            class="button"
                            onclick="esquinaToggleFbToken()">
                            👁
                        </button>

                    </div>

                </td>

            </tr>

            <tr>

                <th>Page ID</th>

                <td>

                    <input
                        type="text"
                        name="esquina_facebook_settings[page_id]"
                        value="<?php echo esc_attr($options['page_id'] ?? ''); ?>"
                        class="regular-text">

                </td>

            </tr>

            <tr>

                <th>Límite API</th>

                <td>

                    <input
                        type="number"
                        min="1"
                        max="100"
                        name="esquina_facebook_settings[limit]"
                        value="<?php echo esc_attr($options['limit'] ?? 25); ?>">

                </td>

            </tr>

            <tr>

                <th>Publicaciones por página</th>

                <td>

                    <input
                        type="number"
                        min="1"
                        max="20"
                        name="esquina_facebook_settings[per_page]"
                        value="<?php echo esc_attr($options['per_page'] ?? 4); ?>">

                </td>

            </tr>

        </table>

        <?php submit_button(); ?>

    </form>

</div>

<script>

function esquinaToggleFbToken()
{
    const field =
        document.getElementById(
            'esquina_fb_token'
        );

    field.type =
        field.type === 'password'
        ? 'text'
        : 'password';
}

</script>

<?php
}