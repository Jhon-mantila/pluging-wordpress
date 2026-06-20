<?php
/**
 * Plugin Name: Mis funciones
 * Plugin URI: https://esquinaweb.com
 * Description: Snippets y shortcodes (categorías, conteos, feed Facebook, últimas entradas, YouTube) (recomendaciones).
 * Version: 1.5.7
 * Author: Jhon mantilla
 * Author URI: https://esquinaweb.com
 * License: GPLv2 or later
 * Text Domain: esquina-mis-funciones
 *
 * @package Mis_Funciones
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESQUINA_MF_VERSION', '1.5.0' );
define( 'ESQUINA_MF_PATH', plugin_dir_path( __FILE__ ) );
define( 'ESQUINA_MF_URL', plugin_dir_url( __FILE__ ) );

/*
para mostrar el menu en el panel de admin
*/
require_once ESQUINA_MF_PATH . 'includes/admin/menu.php';

require_once ESQUINA_MF_PATH . 'includes/shortcodes/category-post-count.php';
require_once ESQUINA_MF_PATH . 'includes/shortcodes/categorias-grid.php';
require_once ESQUINA_MF_PATH . 'includes/shortcodes/facebook-posts.php';
require_once ESQUINA_MF_PATH . 'includes/shortcodes/ultimas-entradas.php';
require_once ESQUINA_MF_PATH . 'includes/shortcodes/youtube-feed.php';
/*
para mostrar las recomendaciones al final de las publicaciones
*/
require_once ESQUINA_MF_PATH . 'includes/recomendaciones/admin-settings.php';
require_once ESQUINA_MF_PATH . 'includes/recomendaciones/recomendaciones.php';
