<?php

if (!defined('ABSPATH')) {
    exit;
}

function esquina_ultimas_render_layout($layout, $query, $atts)
{
    $file = ESQUINA_MF_PATH .
        'includes/ultimas_entradas/layouts/' .
        $layout .
        '.php';

    if (!file_exists($file)) {
        $file = ESQUINA_MF_PATH .
            'includes/ultimas_entradas/layouts/vertical.php';
    }

    ob_start();

    // =========================
    // 🔥 VARIABLES BASE
    // =========================
    $posts = $query;
    //$atts  = $atts;

    // footer mode
    $is_footer = filter_var($atts['footer'], FILTER_VALIDATE_BOOLEAN);

    // sizes centralizados
    $default_w = $is_footer ? 88 : 100;
    $default_h = $is_footer ? 66 : 75;

    $img_w = ($atts['width'] !== '') ? intval($atts['width']) : $default_w;
    $img_h = ($atts['height'] !== '') ? intval($atts['height']) : $default_h;

    $img_w = min(200, max(48, $img_w));
    $img_h = min(200, max(48, $img_h));

    // columns para grids
    $columns = isset($atts['columns']) ? intval($atts['columns']) : 3;

    // title color (YA SANITIZADO AQUÍ)
    $title_color = esquina_mf_ultimas_entradas_sanitize_color($atts['title_color']);
    
    $classes = 'esquina-ultimas';

    if ($is_footer) {
        $classes .= ' esquina-ultimas--footer';
    }

    $style = sprintf(
        '--esquina-ultimas-w:%dpx;--esquina-ultimas-h:%dpx;',
        $img_w,
        $img_h
    );


    if ($title_color) {
        $classes .= ' esquina-ultimas--has-title-color';
        $style .= '--esquina-ultimas-title-color:' . $title_color . ';';
    }


    include $file;

    return ob_get_clean();
}