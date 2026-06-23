<?php
/**
 * Shortcode: [facebook_posts page_id="631930116676494" limit="25" per_page="4"]
 *
 * Token (no lo pongas en el código): en wp-config.php define el constante
 * ESQUINA_FB_PAGE_ACCESS_TOKEN o guarda la opción esquina_fb_page_access_token.
 *
 * @package Mis_Funciones
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Obtiene el token de página (Graph API). Prioridad: constante → opción → filtro.
 *
 * @return string
 */
function esquina_fb_get_access_token() {
    $options = get_option(
        'esquina_facebook_settings',
        []
    );

    if (
        !empty(
            $options['access_token']
        )
    ) {
        return trim(
            $options['access_token']
        );
    }

    if (
        defined('ESQUINA_FB_PAGE_ACCESS_TOKEN')
        &&
        ESQUINA_FB_PAGE_ACCESS_TOKEN
    ) {
        return ESQUINA_FB_PAGE_ACCESS_TOKEN;
    }

    return (string) apply_filters(
        'esquina_fb_access_token',
        ''
    );
}

/**
 * Texto corto para las tarjetas.
 *
 * @param string $text  Texto completo.
 * @param int    $limit Longitud máxima aproximada.
 * @return string
 */
function esquina_fb_preview_text( $text, $limit = 140 ) {
	$text = wp_strip_all_tags( (string) $text );
	$text = preg_replace( '/\s+/u', ' ', $text );
	$text = trim( $text );
	if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) ) {
		if ( mb_strlen( $text ) <= $limit ) {
			return $text;
		}
		return rtrim( mb_substr( $text, 0, $limit - 1 ) ) . '…';
	}
	if ( strlen( $text ) <= $limit ) {
		return $text;
	}
	return rtrim( substr( $text, 0, $limit - 1 ) ) . '…';
}

/**
 * Extrae imagen / video del primer attachment útil (incluye subattachments).
 *
 * @param array $attachments_data Lista `attachments.data` del post.
 * @return array{type:string,video:string,image:string}|null
 */
function esquina_fb_first_attachment_media( $attachments_data ) {
	if ( empty( $attachments_data ) || ! is_array( $attachments_data ) ) {
		return null;
	}

	foreach ( $attachments_data as $att ) {
		$mt = isset( $att['media_type'] ) ? $att['media_type'] : '';

		if ( 'video' === $mt && ! empty( $att['media']['source'] ) ) {
			return array(
				'type'  => 'video',
				'video' => $att['media']['source'],
				'image' => isset( $att['media']['image']['src'] ) ? $att['media']['image']['src'] : '',
			);
		}

		if ( 'photo' === $mt && ! empty( $att['media']['image']['src'] ) ) {
			return array(
				'type'  => 'photo',
				'video' => '',
				'image' => $att['media']['image']['src'],
			);
		}

		if ( ! empty( $att['subattachments']['data'] ) && is_array( $att['subattachments']['data'] ) ) {
			$nested = esquina_fb_first_attachment_media( $att['subattachments']['data'] );
			if ( $nested ) {
				return $nested;
			}
		}
	}

	return null;
}

/**
 * Normaliza un elemento de `data` de la respuesta Graph API.
 *
 * @param array $item Post crudo.
 * @return array
 */
function esquina_fb_normalize_graph_post( $item ) {
	if ( ! is_array( $item ) ) {
		return array();
	}

	$message_raw = isset( $item['message'] ) ? $item['message'] : '';
	$permalink   = isset( $item['permalink_url'] ) ? esc_url_raw( $item['permalink_url'] ) : '';
	$created     = isset( $item['created_time'] ) ? $item['created_time'] : '';

	$created_human = '';
	if ( $created ) {
		$ts = strtotime( $created );
		if ( $ts ) {
			$created_human = date_i18n(
				get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
				$ts
			);
		}
	}

	$media_type = 'none';
	$video      = '';
	$image      = '';

	$attachments = isset( $item['attachments']['data'] ) ? $item['attachments']['data'] : array();
	$media       = esquina_fb_first_attachment_media( $attachments );

	if ( $media ) {
		$media_type = $media['type'];
		$video      = $media['video'] ? esc_url_raw( $media['video'] ) : '';
		$image      = $media['image'] ? esc_url_raw( $media['image'] ) : '';
	}

	return array(
		'id'               => isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : '',
		'message'          => wp_strip_all_tags( $message_raw ),
		'message_preview'  => esquina_fb_preview_text( $message_raw, 160 ),
		'permalink_url'    => $permalink,
		'created_time'     => $created,
		'created_human'    => $created_human,
		'media_type'       => $media_type,
		'image'            => $image,
		'video'            => $video,
	);
}

/**
 * Llama a Graph API por los posts de la página.
 *
 * @param string $page_id ID de página Facebook.
 * @param int    $limit   Límite por petición (1–100).
 * @param string $after   Cursor `after` para paginación.
 * @return array|WP_Error { posts: array, next_cursor: string, raw: array }
 */
function esquina_fb_fetch_posts_request( $page_id, $limit, $after = '' ) {
	$token = esquina_fb_get_access_token();
	if ( ! $token ) {
		return new WP_Error(
			'esquina_fb_no_token',
			__( 'Falta el token de Facebook. Define ESQUINA_FB_PAGE_ACCESS_TOKEN en wp-config.php o la opción esquina_fb_page_access_token.', 'esquina-mis-funciones' )
		);
	}

	$page_id = preg_replace( '/[^0-9]/', '', (string) $page_id );
	if ( ! $page_id ) {
		return new WP_Error( 'esquina_fb_no_page', __( 'page_id no válido.', 'esquina-mis-funciones' ) );
	}

	$limit = (int) $limit;
	if ( $limit < 1 ) {
		$limit = 25;
	}
	$limit = min( 100, $limit );

	$fields = 'id,message,created_time,permalink_url,attachments{media_type,media,subattachments}';

	$args = array(
		'fields'         => $fields,
		'limit'          => $limit,
		'access_token'   => $token,
	);

	$url = 'https://graph.facebook.com/v25.0/' . rawurlencode( $page_id ) . '/posts?' . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );

	if ( $after ) {
		$url .= '&after=' . rawurlencode( $after );
	}

	/** Primer lote: cache corto para no golpear la API en cada vista. */
	$use_cache = ( '' === $after );
	$cache_key = 'esquina_fb_' . md5( $page_id . '_' . $limit . '_' . $after );

	if ( $use_cache ) {
		$cached = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 20,
			'headers' => array(
				'Accept' => 'application/json',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );

	$data = json_decode( $body, true );
	if ( null === $data ) {
		return new WP_Error(
			'esquina_fb_bad_json',
			__( 'Respuesta inválida de Facebook.', 'esquina-mis-funciones' )
		);
	}

	if ( ! empty( $data['error']['message'] ) ) {
		return new WP_Error(
			'esquina_fb_graph_error',
			sanitize_text_field( $data['error']['message'] )
		);
	}

	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'esquina_fb_http', __( 'Error HTTP al contactar Facebook.', 'esquina-mis-funciones' ) );
	}

	$rows = isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : array();
	$list = array();

	foreach ( $rows as $row ) {
		$norm = esquina_fb_normalize_graph_post( $row );
		if ( ! empty( $norm['id'] ) ) {
			$list[] = $norm;
		}
	}

	$paging       = isset( $data['paging'] ) && is_array( $data['paging'] ) ? $data['paging'] : array();
	$next_cursor  = '';
	$cursors      = isset( $paging['cursors'] ) && is_array( $paging['cursors'] ) ? $paging['cursors'] : array();
	if ( ! empty( $cursors['after'] ) ) {
		$next_cursor = sanitize_text_field( $cursors['after'] );
	}
	if ( empty( $next_cursor ) && ! empty( $paging['next'] ) && is_string( $paging['next'] ) ) {
		$parts = wp_parse_url( $paging['next'] );
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $q );
			if ( ! empty( $q['after'] ) ) {
				$next_cursor = sanitize_text_field( $q['after'] );
			}
		}
	}

	$result = array(
		'posts'       => $list,
		'next_cursor' => $next_cursor,
		'raw'         => $data,
	);

	if ( $use_cache ) {
		set_transient( $cache_key, $result, 5 * MINUTE_IN_SECONDS );
	}

	return $result;
}

/**
 * Shortcode principal.
 *
 * @param array|string $atts Atributos.
 * @return string
 */
function esquina_mf_facebook_posts_shortcode( $atts ) {
	
	$options = get_option(
		'esquina_facebook_settings',
		[]
	);
	
	$atts = shortcode_atts(
		array(
			'page_id' =>
				$options['page_id'] ?? '',
	
			'limit' =>
				$options['limit'] ?? 25,
	
			'per_page' =>
				$options['per_page'] ?? 4,
		),
		$atts,
		'facebook_posts'
	);

	$page_id = preg_replace( '/[^0-9]/', '', (string) $atts['page_id'] );
	$limit = max( 1, min( 100, intval( $atts['limit'] ) ) );
	$per   = max( 1, intval( $atts['per_page'] ) );

	if ( ! $page_id ) {
		return '<p class="esquina-fb-feed__error">' . esc_html__( 'Shortcode facebook_posts: indica page_id="…".', 'esquina-mis-funciones' ) . '</p>';
	}

	wp_enqueue_style(
		'esquina-fb-feed',
		ESQUINA_MF_URL . 'assets/css/facebook-posts.css',
		array(),
		ESQUINA_MF_VERSION
	);

	wp_enqueue_script(
		'esquina-fb-feed',
		ESQUINA_MF_URL . 'assets/js/facebook-posts.js',
		array(),
		ESQUINA_MF_VERSION,
		true
	);

	$feed = esquina_fb_fetch_posts_request( $page_id, $limit, '' );

	if ( is_wp_error( $feed ) ) {
		return '<p class="esquina-fb-feed__error">' . esc_html( $feed->get_error_message() ) . '</p>';
	}

	$posts       = isset( $feed['posts'] ) ? $feed['posts'] : array();
	$next_cursor = isset( $feed['next_cursor'] ) ? $feed['next_cursor'] : '';

	$uid = 'fb-' . wp_generate_uuid4();

	$config = array(
		'ajax_url'    => admin_url( 'admin-ajax.php' ),
		'nonce'       => wp_create_nonce( 'esquina_fb_feed' ),
		'page_id'     => $page_id,
		'limit'       => $limit,
		'per_page'    => $per,
		'posts'       => $posts,
		'next_cursor' => $next_cursor,
		'strings'     => array(
			'no_posts'          => __( 'No hay publicaciones para mostrar.', 'esquina-mis-funciones' ),
			'no_message'        => __( '(Sin texto en esta publicación.)', 'esquina-mis-funciones' ),
			'view_on_facebook'  => __( 'Ver en Facebook', 'esquina-mis-funciones' ),
			'page_label'        => __( 'Página', 'esquina-mis-funciones' ),
		),
	);

	$json = wp_json_encode(
		$config,
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
	);

	if ( false === $json ) {
		$json = '{}';
	}

	ob_start();
	?>
	<div id="<?php echo esc_attr( $uid ); ?>" class="esquina-fb-feed" data-config="<?php echo esc_attr( $json ); ?>">
		<div class="esquina-fb-feed__layout" aria-live="polite"></div>
		<div class="esquina-fb-feed__pager">
			<button type="button" data-esquina-fb-prev><?php esc_html_e( 'Anterior', 'esquina-mis-funciones' ); ?></button>
			<span class="esquina-fb-feed__meta" data-esquina-fb-meta></span>
			<button type="button" data-esquina-fb-next><?php esc_html_e( 'Siguiente', 'esquina-mis-funciones' ); ?></button>
		</div>

		<div class="esquina-fb-modal" hidden>
			<div class="esquina-fb-modal__backdrop" tabindex="-1"></div>
			<div class="esquina-fb-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title">
				<button type="button" class="esquina-fb-modal__close" aria-label="<?php esc_attr_e( 'Cerrar', 'esquina-mis-funciones' ); ?>">×</button>
				<div class="esquina-fb-modal__media"></div>
				<div class="esquina-fb-modal__body">
					<h2 id="<?php echo esc_attr( $uid ); ?>-title" class="screen-reader-text"><?php esc_html_e( 'Publicación', 'esquina-mis-funciones' ); ?></h2>
					<div class="esquina-fb-modal__date"></div>
					<div class="esquina-fb-modal__message"></div>
					<p><a class="esquina-fb-modal__link" href="#" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ver en Facebook', 'esquina-mis-funciones' ); ?></a></p>
				</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'facebook_posts', 'esquina_mf_facebook_posts_shortcode' );

/**
 * AJAX: más resultados (cursor `after`).
 */
function esquina_fb_ajax_more() {
	check_ajax_referer( 'esquina_fb_feed', 'nonce' );

	$page_id = isset( $_POST['page_id'] ) ? preg_replace( '/[^0-9]/', '', wp_unslash( $_POST['page_id'] ) ) : '';
	$limit   = isset( $_POST['limit'] ) ? intval( wp_unslash( $_POST['limit'] ), 10 ) : 25;
	$after   = isset( $_POST['after'] ) ? sanitize_text_field( wp_unslash( $_POST['after'] ) ) : '';

	$limit = max( 1, min( 100, $limit ) );

	if ( ! $page_id || '' === $after ) {
		wp_send_json_error( array( 'message' => 'Bad request' ), 400 );
	}

	$feed = esquina_fb_fetch_posts_request( $page_id, $limit, $after );

	if ( is_wp_error( $feed ) ) {
		wp_send_json_error(
			array(
				'message' => $feed->get_error_message(),
			),
			502
		);
	}

	wp_send_json_success(
		array(
			'posts'       => isset( $feed['posts'] ) ? $feed['posts'] : array(),
			'next_cursor' => isset( $feed['next_cursor'] ) ? $feed['next_cursor'] : '',
		)
	);
}

add_action( 'wp_ajax_esquina_fb_more', 'esquina_fb_ajax_more' );
add_action( 'wp_ajax_nopriv_esquina_fb_more', 'esquina_fb_ajax_more' );
