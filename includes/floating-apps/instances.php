<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Registro de instancias (2 paneles genéricos, 1 código)
|--------------------------------------------------------------------------
| El título visible de cada panel se configura en el admin ("Título del panel").
*/

function esquina_fa_instances()
{
    return [
        'panel1' => [
            'id'            => 'panel1',
            'option'        => 'esquina_floating_apps_settings', // opción histórica
            'group'         => 'esquina_floating_apps_group',
            'menu_slug'     => 'esquina-floating-apps',
            'menu_title'    => __('Floating Apps 1', 'esquina-mis-funciones'),
            'page_title'    => __('Floating Apps — Panel 1', 'esquina-mis-funciones'),
            'default_label' => 'Panel 1',
            'storage_key'   => 'esquina_fa_closed_v1',
            'stack'         => 0,
            'toggle_svg'    => 'apps',
            'reset_action'  => 'esquina_fa_reset_panel1',
        ],
        'panel2' => [
            'id'            => 'panel2',
            'option'        => 'esquina_fa_panel2_settings',
            'group'         => 'esquina_fa_panel2_group',
            'menu_slug'     => 'esquina-floating-apps-2',
            'menu_title'    => __('Floating Apps 2', 'esquina-mis-funciones'),
            'page_title'    => __('Floating Apps — Panel 2', 'esquina-mis-funciones'),
            'default_label' => 'Panel 2',
            'storage_key'   => 'esquina_fa_closed_panel2_v1',
            'stack'         => 1,
            'toggle_svg'    => 'esquina',
            'reset_action'  => 'esquina_fa_reset_panel2',
        ],
    ];
}

function esquina_fa_get_instance($id)
{
    $instances = esquina_fa_instances();

    return $instances[$id] ?? null;
}
