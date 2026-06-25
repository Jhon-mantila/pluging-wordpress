<?php

/**
 * Valida color hexadecimal (#RGB o #RRGGBB).
 *
 * @param string $color Color del shortcode.
 * @return string Vacío si no es válido.
 */
function esquina_mf_ultimas_entradas_sanitize_color( $color ) {
	$color = trim( (string) $color );
	$color = trim( $color, " \t\n\r\0\x0B'\"" );
	$color = html_entity_decode( $color, ENT_QUOTES, 'UTF-8' );
	$color = trim( $color, " \t\n\r\0\x0B'\"" );

	if ( $color === '' ) {
		return '';
	}

	// Algunos editores codifican el # como %23.
	if ( 0 === strpos( $color, '%23' ) ) {
		$color = '#' . substr( $color, 3 );
	}

	$sanitized = sanitize_hex_color( $color );
	if ( $sanitized ) {
		return $sanitized;
	}

	if ( preg_match( '/^[0-9a-f]{3}$/i', $color ) ) {
		return sanitize_hex_color( '#' . $color );
	}

	if ( preg_match( '/^[0-9a-f]{6}$/i', $color ) ) {
		return sanitize_hex_color( '#' . $color );
	}

	return '';
}

/**
 * Obtiene URL de miniatura con medidas aproximadas.
 *
 * @param int $post_id ID del post.
 * @param int $width   Ancho deseado.
 * @param int $height  Alto deseado.
 * @return string
 */
function esquina_mf_ultimas_entradas_thumb_url( $post_id, $width, $height ) {
	$thumb_id = get_post_thumbnail_id( $post_id );

	if ( $thumb_id ) {
		$src = wp_get_attachment_image_src( $thumb_id, array( $width, $height ) );
		if ( ! empty( $src[0] ) ) {
			return $src[0];
		}
		$url = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
		if ( $url ) {
			return $url;
		}
	}

	return '';
}