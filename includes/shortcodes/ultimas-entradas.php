<?php
/**
 * Shortcode: [ultimas_entradas number="6" width="90" height="68" footer="true"]
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

/**
 * Lista horizontal: imagen destacada a la izquierda, título a la derecha.
 *
 * @param array|string $atts Atributos del shortcode.
 * @return string
 */
function esquina_mf_ultimas_entradas( $atts ) {
	$atts = shortcode_atts(
		array(
			'number' => 5,
			'width'  => '',
			'height' => '',
			'footer' => 'false',
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

	wp_enqueue_style(
		'esquina-ultimas-entradas',
		ESQUINA_MF_URL . 'assets/css/ultimas-entradas.css',
		array(),
		ESQUINA_MF_VERSION
	);

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

	$classes = 'esquina-ultimas';
	if ( $is_footer ) {
		$classes .= ' esquina-ultimas--footer';
	}

	$style = sprintf(
		'--esquina-ultimas-w:%dpx;--esquina-ultimas-h:%dpx;',
		$img_w,
		$img_h
	);

	ob_start();
	printf(
		'<div class="%s" style="%s" role="list">',
		esc_attr( $classes ),
		esc_attr( $style )
	);

	while ( $query->have_posts() ) {
		$query->the_post();

		$post_id = get_the_ID();
		$title   = get_the_title();
		$url     = get_permalink();
		$thumb   = esquina_mf_ultimas_entradas_thumb_url( $post_id, $img_w, $img_h );

		if ( ! $thumb ) {
			$thumb = sprintf(
				'https://via.placeholder.com/%1$dx%2$d?text=%3$s',
				$img_w,
				$img_h,
				rawurlencode( wp_trim_words( $title, 3, '…' ) )
			);
		}

		?>
		<div class="esquina-ultimas__item" role="listitem">
			<a class="esquina-ultimas__link" href="<?php echo esc_url( $url ); ?>">
				<span class="esquina-ultimas__thumb">
					<img
						src="<?php echo esc_url( $thumb ); ?>"
						alt="<?php echo esc_attr( $title ); ?>"
						width="<?php echo esc_attr( (string) $img_w ); ?>"
						height="<?php echo esc_attr( (string) $img_h ); ?>"
						loading="lazy"
						decoding="async"
					/>
				</span>
				<span class="esquina-ultimas__title"><?php echo esc_html( $title ); ?></span>
			</a>
		</div>
		<?php
	}

	echo '</div>';

	wp_reset_postdata();

	return ob_get_clean();
}

add_shortcode( 'ultimas_entradas', 'esquina_mf_ultimas_entradas' );
