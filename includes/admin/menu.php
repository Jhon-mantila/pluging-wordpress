<?php

if (!defined('ABSPATH')) {
    exit;
}

function esquinaweb_admin_menu()
{
    add_menu_page(
        'EsquinaWeb',
        'EsquinaWeb',
        'manage_options',
        'esquinaweb-dashboard',
        'esquinaweb_dashboard_page',
        'dashicons-superhero',
        30
    );
}

add_action('admin_menu', 'esquinaweb_admin_menu');

function esquinaweb_dashboard_page()
{
    ?>
    <div class="wrap">
        <h1>EsquinaWeb</h1>

        <p>Panel principal del plugin.</p>
    </div>
    <?php
}