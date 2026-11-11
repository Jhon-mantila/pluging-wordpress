<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Recomendaciones
|--------------------------------------------------------------------------
*/

delete_option('esquina_recomendaciones_settings');

/*
|--------------------------------------------------------------------------
| YouTube
|--------------------------------------------------------------------------
*/

delete_option('esquina_youtube_settings');

/*
|--------------------------------------------------------------------------
| Facebook
|--------------------------------------------------------------------------
*/

delete_option('esquina_facebook_settings');

/*
|--------------------------------------------------------------------------
| Floating Apps
|--------------------------------------------------------------------------
*/

delete_option('esquina_floating_apps_settings');
delete_option('esquina_fa_panel2_settings');
delete_option('esquina_fa_tools_settings'); // legacy por si quedó de una prueba

/*
|--------------------------------------------------------------------------
| Apps Bar (fila en entradas)
|--------------------------------------------------------------------------
*/

delete_option('esquina_apps_bar_settings');