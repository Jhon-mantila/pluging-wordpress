<?php
/**
 * Plugin Name: Mis funciones
 * Plugin URI: https://esquinaweb.com
 * Description: Snippets y shortcodes (categorías, conteos, feed Facebook, últimas entradas, YouTube) (recomendaciones).
 * Version: 1.6.9
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

define( 'ESQUINA_MF_VERSION', '1.6.9' );
define( 'ESQUINA_MF_PATH', plugin_dir_path( __FILE__ ) );
define( 'ESQUINA_MF_URL', plugin_dir_url( __FILE__ ) );

/*
para mostrar el menu en el panel de admin
*/
require_once ESQUINA_MF_PATH . 'includes/admin/menu.php';
require_once ESQUINA_MF_PATH . 'includes/recomendaciones/admin-settings.php';
require_once ESQUINA_MF_PATH . 'includes/youtube/admin-settings.php';
require_once ESQUINA_MF_PATH . 'includes/facebook/oauth.php';
require_once ESQUINA_MF_PATH . 'includes/facebook/admin-settings.php';

require_once ESQUINA_MF_PATH . 'includes/ultimas_entradas/helpers/ultimas-entradas-utils.php';
require_once ESQUINA_MF_PATH . 'includes/ultimas_entradas/renderer.php';

require_once ESQUINA_MF_PATH . 'includes/shortcodes/category-post-count.php';
require_once ESQUINA_MF_PATH . 'includes/shortcodes/categorias-grid.php';
require_once ESQUINA_MF_PATH . 'includes/shortcodes/facebook-posts.php';
require_once ESQUINA_MF_PATH . 'includes/shortcodes/ultimas-entradas.php';
require_once ESQUINA_MF_PATH . 'includes/shortcodes/youtube-feed.php';
/*
para mostrar las recomendaciones al final de las publicaciones
*/

require_once ESQUINA_MF_PATH . 'includes/recomendaciones/recomendaciones.php';

/*
widget flotante de apps en el frontend
*/

require_once ESQUINA_MF_PATH . 'includes/floating-apps/admin-settings.php';
require_once ESQUINA_MF_PATH . 'includes/floating-apps/renderer.php';

/*
fila de apps al inicio de cada entrada individual
*/

require_once ESQUINA_MF_PATH . 'includes/apps-bar/admin-settings.php';
require_once ESQUINA_MF_PATH . 'includes/apps-bar/renderer.php';
