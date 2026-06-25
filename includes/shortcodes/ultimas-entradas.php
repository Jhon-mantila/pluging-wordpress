<?php
/**
 * Shortcode: [ultimas_entradas number="6" width="90" height="68" footer="true" title_color="#ffffff"]
 *
 * Muestra las últimas entradas publicadas (máximo 10).
 * Por defecto imágenes pequeñas (100×75). Con footer="true" aún más compacto (88×66).
 *
 * @package Mis_Funciones
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Lista horizontal: imagen destacada a la izquierda, título a la derecha.
 *
 * @param array|string $atts Atributos del shortcode.
 * @return string
 */
function esquina_mf_ultimas_entradas( $atts ) {
	$atts = shortcode_atts(
		array(
			'number'      => 5,
			'width'       => '',
			'height'      => '',
			'footer'      => 'false',
			'title_color' => '',
			'layout'      => 'vertical',
			'columns'     => 3,
		),
		$atts,
		'ultimas_entradas'
	);

	$count = intval( $atts['number'] );
	if ( $count < 1 ) {
		$count = 1;
	}
	if ( $count > 10 ) {
		$count = 10;
	}

	$is_footer = filter_var( $atts['footer'], FILTER_VALIDATE_BOOLEAN );

	$default_w = $is_footer ? 88 : 100;
	$default_h = $is_footer ? 66 : 75;

	$img_w = ( '' !== $atts['width'] ) ? intval( $atts['width'] ) : $default_w;
	$img_h = ( '' !== $atts['height'] ) ? intval( $atts['height'] ) : $default_h;

	$img_w = min( 200, max( 48, $img_w ) );
	$img_h = min( 200, max( 48, $img_h ) );

	$layout = sanitize_key( $atts['layout'] );
	$columns = min(6, max(1, intval($atts['columns'])));

	if ($layout === 'grid-1') {

		wp_enqueue_style(
			'esquina-grid1',
			ESQUINA_MF_URL . 'assets/css/ultimas-entradas/grid-1.css',
			[],
			ESQUINA_MF_VERSION
		);
	
	} elseif ($layout === 'grid-2') {
	
		wp_enqueue_style(
			'esquina-grid2',
			ESQUINA_MF_URL . 'assets/css/ultimas-entradas/grid-2.css',
			[],
			ESQUINA_MF_VERSION
		);
	
	} else {
	
		wp_enqueue_style(
			'esquina-vertical',
			ESQUINA_MF_URL . 'assets/css/ultimas-entradas.css',
			[],
			ESQUINA_MF_VERSION
		);
	
	}

	$query = new WP_Query(
		array(
			'posts_per_page'      => $count,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'no_found_rows'       => true,
		)
	);

	if ( ! $query->have_posts() ) {
		wp_reset_postdata();
		return '<p class="esquina-ultimas__empty">' . esc_html__( 'No hay entradas publicadas.', 'esquina-mis-funciones' ) . '</p>';
	}


	// 👇 AQUÍ SE RENDERIZA TODO
	$output = esquina_ultimas_render_layout($layout, $query, $atts);

	wp_reset_postdata();

	return $output;
}

add_shortcode( 'ultimas_entradas', 'esquina_mf_ultimas_entradas' );
