<?php
/**
 * Shortcode: [category_post_count category="anime"]
 *
 * @package Mis_Funciones
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve el número de entradas publicadas en una categoría (slug).
 *
 * @param array|string $atts Atributos del shortcode.
 * @return int|string
 */
function esquina_mf_category_post_count( $atts ) {
	$atts = shortcode_atts(
		array(
			'category' => null,
		),
		$atts,
		'category_post_count'
	);

	if ( ! $atts['category'] ) {
		return 0;
	}

	$term = get_term_by( 'slug', $atts['category'], 'category' );

	if ( ! $term || is_wp_error( $term ) ) {
		return 0;
	}

	return isset( $term->count ) ? (int) $term->count : 0;
}

add_shortcode( 'category_post_count', 'esquina_mf_category_post_count' );
