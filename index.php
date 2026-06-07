<?php
/**
 * Plugin Name: Mis funciones
 * Plugin URI: https://esquinaweb.com
 * Description: Snippets y shortcodes (categorías, conteos, feed Facebook, últimas entradas, YouTube).
 * Version: 1.5.0
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

require_once ESQUINA_MF_PATH . 'includes/shortcodes/category-post-count.php';
require_once ESQUINA_MF_PATH . 'includes/shortcodes/categorias-grid.php';
require_once ESQUINA_MF_PATH . 'includes/shortcodes/facebook-posts.php';
require_once ESQUINA_MF_PATH . 'includes/shortcodes/ultimas-entradas.php';
require_once ESQUINA_MF_PATH . 'includes/shortcodes/youtube-feed.php';
