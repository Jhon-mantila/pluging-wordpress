<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Registro opciones
|--------------------------------------------------------------------------
*/

function esquina_ultimas_register_settings()
{
    register_setting(
        'esquina_ultimas_group',
        'esquina_ultimas_settings'
    );
}
add_action('admin_init', 'esquina_ultimas_register_settings');

/*
|--------------------------------------------------------------------------
| Valores por defecto
|--------------------------------------------------------------------------
*/

function esquina_ultimas_default_settings()
{
    return [
        'layout'          => 'vertical',
        'number'          => 6,
        'columns'         => 3,
        'width'           => 320,
        'height'          => 220,
        'autoplay'        => 1,
        'autoplay_speed'  => 3000,
        'show_excerpt'    => 1,
        'show_category'   => 1,
    ];
}