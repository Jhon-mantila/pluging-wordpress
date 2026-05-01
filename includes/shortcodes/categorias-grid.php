<?php
/**
 * Shortcode: [categorias_grid number="6" columns="3"] [categorias_grid]
 *
 * @package Mis_Funciones
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Grid de categorías con imagen del último post y conteo.
 *
 * @param array|string $atts Atributos del shortcode.
 * @return string
 */
function esquina_mf_categorias_grid( $atts ) {
	$atts = shortcode_atts(
		array(
			'number'     => 0,
			'columns'    => 3,
			'hide_empty' => true,
		),
		$atts,
		'categorias_grid'
	);

	$categorias = get_categories(
		array(
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => intval( $atts['number'] ) > 0 ? intval( $atts['number'] ) : 0,
			'hide_empty' => filter_var( $atts['hide_empty'], FILTER_VALIDATE_BOOLEAN ),
		)
	);

	if ( empty( $categorias ) ) {
		return '<p>No hay categorías disponibles.</p>';
	}

	$output = '<div class="grid-categorias" style="grid-template-columns: repeat(' . intval( $atts['columns'] ) . ', 1fr);">';

	foreach ( $categorias as $categoria ) {
		$query = new WP_Query(
			array(
				'posts_per_page' => 1,
				'cat'            => $categoria->term_id,
				'post_status'    => 'publish',
			)
		);

		$imagen = '';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				if ( has_post_thumbnail() ) {
					$imagen = get_the_post_thumbnail_url( get_the_ID(), 'large' );
				}
			}
		}

		wp_reset_postdata();

		if ( ! $imagen ) {
			$imagen = 'https://via.placeholder.com/600x400?text=' . rawurlencode( $categoria->name );
		}

		$output .= '
        <a href="' . esc_url( get_category_link( $categoria->term_id ) ) . '" class="categoria-item" style="background-image:url(' . esc_url( $imagen ) . ')">
            <div class="overlay">
                <h2>' . esc_html( $categoria->name ) . '</h2>
                <span>' . intval( $categoria->count ) . ' artículos</span>
            </div>
        </a>';
	}

	$output .= '</div>';

	return $output;
}

add_shortcode( 'categorias_grid', 'esquina_mf_categorias_grid' );

/**
 * Estilos del grid de categorías (inline en wp_head).
 */
function esquina_mf_categorias_grid_styles() {
	echo '
    <style>
    .grid-categorias {
        display: grid;
        gap: 20px;
        margin: 20px 0;
    }

    .categoria-item {
        position: relative;
        height: 250px;
        background-size: cover;
        background-position: center;
        border-radius: 12px;
        overflow: hidden;
        text-decoration: none;
        box-shadow: inset 0 -80px 80px rgba(0,0,0,0.4);
    }

    .categoria-item .overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(
            to top,
            rgba(0,0,0,0.65),
            rgba(0,0,0,0.25),
            rgba(0,0,0,0.1)
        );
        color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        align-items: flex-start;
        padding: 20px;
        transition: 0.3s;
    }

    .categoria-item:hover .overlay {
        background: rgba(0,0,0,0.7);
    }

    .categoria-item h2 {
        margin: 0;
        text-transform: capitalize;
        color: #fff;
        font-size: 32px;
    }

    .categoria-item span {
        font-size: 14px;
        opacity: 0.9;
    }

    @media (max-width: 768px) {
        .grid-categorias {
            grid-template-columns: 1fr !important;
        }
    }
    </style>
    ';
}

add_action( 'wp_head', 'esquina_mf_categorias_grid_styles' );
