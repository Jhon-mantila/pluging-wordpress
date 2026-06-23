<?php
/**
 * Shortcodes YouTube Data API v3:
 * [youtube_largo channel_id="UC…" api_key="…" max="6" columns="3"]
 * [youtube_shorts channel_id="UC…" api_key="…" max="6" columns="4"]
 *
 * max vacío o "all" = sin límite, carga por lotes (batch) vía AJAX.
 * Largos: duración > 60 s. Shorts: ≤ 60 s.
 *
 * Usa playlistItems (subidas del canal), NO search.list — ahorra cuota API.
 *
 * @package Mis_Funciones
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESQUINA_YT_SHORT_MAX_SECONDS', 60 );
define( 'ESQUINA_YT_PLAYLIST_PAGE_SIZE', 50 );
define( 'ESQUINA_YT_MAX_PLAYLIST_PAGES', 15 );
/** Cache de resultados (evita repetir llamadas a la API en cada visita). */
define( 'ESQUINA_YT_CACHE_TTL', 12 * HOUR_IN_SECONDS );

/**
 * @return string
 */
function esquina_yt_resolve_api_key( $from_shortcode ) {

	$options = get_option(
		'esquina_youtube_settings',
		[]
	);

	if ( ! empty( $options['api_key'] ) ) {
		return trim(
			$options['api_key']
		);
	}

	if (
		defined('ESQUINA_YT_API_KEY')
		&& ESQUINA_YT_API_KEY
	) {
		return ESQUINA_YT_API_KEY;
	}

	return is_string($from_shortcode)
		? trim($from_shortcode)
		: '';
}

/**
 * @param string $raw Valor del atributo max.
 * @return int 0 = sin límite (carga por lotes).
 */
function esquina_yt_parse_max( $raw ) {
	$raw = is_string( $raw ) ? trim( strtolower( $raw ) ) : '';
	if ( $raw === '' || $raw === 'all' || $raw === '0' ) {
		return 0;
	}
	$n = intval( $raw );
	return $n > 0 ? min( 100, $n ) : 0;
}

/**
 * @param string $iso Duración ISO 8601.
 * @return int
 */
function esquina_yt_iso_duration_to_seconds( $iso ) {
	$iso = (string) $iso;
	if ( $iso === '' ) {
		return 0;
	}
	try {
		$interval = new DateInterval( $iso );
		return (int) ( $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s );
	} catch ( Exception $e ) {
		return 0;
	}
}

/**
 * @param string $url URL.
 * @return array|WP_Error
 */
function esquina_yt_remote_get_json( $url ) {
	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 20,
			'headers' => array( 'Accept' => 'application/json' ),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( null === $data ) {
		return new WP_Error( 'esquina_yt_json', __( 'Respuesta inválida de YouTube.', 'esquina-mis-funciones' ) );
	}

	if ( $code < 200 || $code >= 300 ) {
		$msg = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Error HTTP al contactar YouTube.', 'esquina-mis-funciones' );
		return new WP_Error( 'esquina_yt_http', sanitize_text_field( $msg ) );
	}

	if ( ! empty( $data['error']['message'] ) ) {
		return new WP_Error( 'esquina_yt_api', sanitize_text_field( $data['error']['message'] ) );
	}

	return $data;
}

/**
 * ID de la playlist "Subidas" del canal (UC… → UU…). Sin coste de API.
 *
 * @param string $channel_id ID del canal.
 * @return string
 */
function esquina_yt_get_uploads_playlist_id( $channel_id ) {
	$channel_id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $channel_id );

	$cache_key = 'esquina_yt_uploads_pl_' . md5( $channel_id );
	$cached    = get_transient( $cache_key );
	if ( is_string( $cached ) && $cached !== '' ) {
		return $cached;
	}

	$playlist_id = '';
	if ( 0 === strpos( $channel_id, 'UC' ) && strlen( $channel_id ) >= 20 ) {
		$playlist_id = 'UU' . substr( $channel_id, 2 );
	}

	if ( $playlist_id ) {
		set_transient( $cache_key, $playlist_id, DAY_IN_SECONDS );
	}

	return $playlist_id;
}

/**
 * Una página de la playlist de subidas (1 unidad de cuota vs 100 de search).
 *
 * @return array|WP_Error { ids: string[], nextPageToken: string }
 */
function esquina_yt_playlist_page( $api_key, $playlist_id, $page_token = '' ) {
	$args = array(
		'key'        => $api_key,
		'playlistId' => $playlist_id,
		'part'       => 'contentDetails',
		'maxResults' => ESQUINA_YT_PLAYLIST_PAGE_SIZE,
	);

	if ( $page_token ) {
		$args['pageToken'] = $page_token;
	}

	$url  = 'https://www.googleapis.com/youtube/v3/playlistItems?' . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
	$data = esquina_yt_remote_get_json( $url );

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	$ids = array();
	if ( ! empty( $data['items'] ) && is_array( $data['items'] ) ) {
		foreach ( $data['items'] as $item ) {
			if ( ! empty( $item['contentDetails']['videoId'] ) ) {
				$ids[] = sanitize_text_field( $item['contentDetails']['videoId'] );
			}
		}
	}

	$next = '';
	if ( ! empty( $data['nextPageToken'] ) ) {
		$next = sanitize_text_field( $data['nextPageToken'] );
	}

	return array(
		'ids'           => $ids,
		'nextPageToken' => $next,
	);
}

/**
 * @param string   $api_key Clave.
 * @param string[] $ids     IDs.
 * @return array|WP_Error
 */
function esquina_yt_videos_details( $api_key, array $ids ) {
	$ids = array_values( array_filter( array_unique( $ids ) ) );
	if ( empty( $ids ) ) {
		return array();
	}

	$out = array();

	foreach ( array_chunk( $ids, 50 ) as $chunk ) {
		$args = array(
			'key'  => $api_key,
			'id'   => implode( ',', $chunk ),
			'part' => 'snippet,contentDetails',
		);
		$url  = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
		$data = esquina_yt_remote_get_json( $url );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( empty( $data['items'] ) || ! is_array( $data['items'] ) ) {
			continue;
		}

		foreach ( $data['items'] as $item ) {
			$vid = isset( $item['id'] ) ? $item['id'] : '';
			if ( ! $vid ) {
				continue;
			}

			$duration_iso = isset( $item['contentDetails']['duration'] ) ? $item['contentDetails']['duration'] : '';
			$seconds      = esquina_yt_iso_duration_to_seconds( $duration_iso );

			$thumbs = isset( $item['snippet']['thumbnails'] ) ? $item['snippet']['thumbnails'] : array();
			$thumb  = esquina_yt_pick_thumb_from_snippet(
				$thumbs,
				$seconds > 0 && $seconds <= ESQUINA_YT_SHORT_MAX_SECONDS
			);

			$title = isset( $item['snippet']['title'] ) ? $item['snippet']['title'] : '';

			$out[] = array(
				'id'      => sanitize_text_field( $vid ),
				'title'   => sanitize_text_field( $title ),
				'thumb'   => $thumb,
				'seconds' => $seconds,
			);
		}
	}

	return $out;
}

/**
 * Elige la mejor miniatura del snippet de YouTube.
 *
 * @param array $thumbs Thumbnails del snippet.
 * @param bool  $prefer_portrait Para Shorts (alto > ancho).
 * @return string
 */
function esquina_yt_pick_thumb_from_snippet( array $thumbs, $prefer_portrait = false ) {
	if ( $prefer_portrait ) {
		$best_url = '';
		$best_h   = 0;
		foreach ( $thumbs as $data ) {
			if ( empty( $data['url'] ) ) {
				continue;
			}
			$w = isset( $data['width'] ) ? (int) $data['width'] : 0;
			$h = isset( $data['height'] ) ? (int) $data['height'] : 0;
			if ( $h > $w && $h >= $best_h ) {
				$best_h   = $h;
				$best_url = $data['url'];
			}
		}
		if ( $best_url ) {
			return esc_url_raw( $best_url );
		}
	}

	foreach ( array( 'maxres', 'standard', 'high', 'medium', 'default' ) as $size ) {
		if ( ! empty( $thumbs[ $size ]['url'] ) ) {
			return esc_url_raw( $thumbs[ $size ]['url'] );
		}
	}

	return '';
}

/**
 * URL de miniatura (API o CDN estándar de YouTube).
 *
 * @param string $video_id  ID del video.
 * @param string $api_thumb   URL de la API si existe.
 * @param string $mode        long|short.
 * @return string
 */
function esquina_yt_video_thumb_url( $video_id, $api_thumb = '', $mode = 'long' ) {
	$video_id = sanitize_text_field( (string) $video_id );
	if ( $api_thumb ) {
		return esc_url_raw( $api_thumb );
	}
	if ( ! $video_id ) {
		return '';
	}
	if ( 'short' === $mode ) {
		return esc_url_raw( 'https://i.ytimg.com/vi/' . $video_id . '/maxresdefault.jpg' );
	}
	return esc_url_raw( 'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg' );
}

/**
 * ¿Encaja el video en el modo largo/corto?
 *
 * @param array  $row  Video normalizado.
 * @param string $mode long|short.
 * @return bool
 */
function esquina_yt_matches_mode( array $row, $mode ) {
	$s = isset( $row['seconds'] ) ? (int) $row['seconds'] : 0;
	if ( $s <= 0 ) {
		return false;
	}
	if ( 'short' === $mode ) {
		return $s <= ESQUINA_YT_SHORT_MAX_SECONDS;
	}
	return $s > ESQUINA_YT_SHORT_MAX_SECONDS;
}

/**
 * Recorre la playlist de subidas hasta reunir $want videos filtrados por duración.
 *
 * Coste aproximado por lote: 1 (playlistItems) + 1 (videos.list) por página.
 *
 * @return array|WP_Error { videos: array, nextPageToken: string, has_more: bool }
 */
function esquina_yt_fetch_videos_batch( $api_key, $channel_id, $mode, $want, $page_token = '', &$seen_ids = array() ) {
	$want = max( 1, (int) $want );

	$playlist_id = esquina_yt_get_uploads_playlist_id( $channel_id );
	if ( ! $playlist_id ) {
		return new WP_Error(
			'esquina_yt_no_playlist',
			__( 'No se pudo obtener la playlist de subidas del canal.', 'esquina-mis-funciones' )
		);
	}

	$filtered = array();
	$token    = $page_token;
	$pages    = 0;
	$has_more = false;

	while ( count( $filtered ) < $want && $pages < ESQUINA_YT_MAX_PLAYLIST_PAGES ) {
		$pages++;
		$page = esquina_yt_playlist_page( $api_key, $playlist_id, $token );

		if ( is_wp_error( $page ) ) {
			return $page;
		}

		$ids   = isset( $page['ids'] ) ? $page['ids'] : array();
		$token = isset( $page['nextPageToken'] ) ? $page['nextPageToken'] : '';

		if ( empty( $ids ) ) {
			$has_more = false;
			break;
		}

		$new_ids = array();
		foreach ( $ids as $id ) {
			if ( ! isset( $seen_ids[ $id ] ) ) {
				$new_ids[]       = $id;
				$seen_ids[ $id ] = true;
			}
		}

		if ( empty( $new_ids ) ) {
			if ( ! $token ) {
				break;
			}
			continue;
		}

		$details = esquina_yt_videos_details( $api_key, $new_ids );
		if ( is_wp_error( $details ) ) {
			return $details;
		}

		foreach ( $details as $row ) {
			if ( esquina_yt_matches_mode( $row, $mode ) ) {
				$filtered[] = array(
					'id'    => $row['id'],
					'title' => $row['title'],
					'thumb' => esquina_yt_video_thumb_url(
						$row['id'],
						isset( $row['thumb'] ) ? $row['thumb'] : '',
						$mode
					),
				);
			}
			if ( count( $filtered ) >= $want ) {
				break;
			}
		}

		if ( count( $filtered ) >= $want ) {
			$has_more = (bool) $token;
			break;
		}

		if ( ! $token ) {
			$has_more = false;
			break;
		}

		$has_more = true;
	}

	if ( count( $filtered ) < $want && $token ) {
		$has_more = true;
	}

	return array(
		'videos'        => $filtered,
		'nextPageToken' => $token,
		'has_more'      => $has_more,
	);
}

/**
 * Crea sesión segura para AJAX (no expone api_key al navegador).
 *
 * @return string ID de sesión.
 */
function esquina_yt_create_session( $api_key, $channel_id, $mode ) {
	$session_id = wp_generate_uuid4();
	set_transient(
		'esquina_yt_s_' . $session_id,
		array(
			'api_key'    => $api_key,
			'channel_id' => $channel_id,
			'mode'       => $mode,
		),
		HOUR_IN_SECONDS
	);
	return $session_id;
}

/**
 * @param string $session_id UUID.
 * @return array|null
 */
function esquina_yt_get_session( $session_id ) {
	$session_id = preg_replace( '/[^a-f0-9-]/i', '', (string) $session_id );
	if ( strlen( $session_id ) < 10 ) {
		return null;
	}
	$data = get_transient( 'esquina_yt_s_' . $session_id );
	return is_array( $data ) ? $data : null;
}

/**
 * @param array $args channel_id, api_key, mode, max, columns, batch, variant.
 * @return string
 */
function esquina_yt_render_feed( array $args ) {
	$channel_id = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $args['channel_id'] );
	$api_key    = esquina_yt_resolve_api_key( $args['api_key'] );
	$mode       = ( 'short' === $args['variant'] ) ? 'short' : 'long';
	$max        = esquina_yt_parse_max( isset( $args['max'] ) ? $args['max'] : '6' );
	$columns    = min( 6, max( 1, intval( isset( $args['columns'] ) ? $args['columns'] : 3 ) ) );
	$batch      = min( 50, max( 1, intval( isset( $args['batch'] ) ? $args['batch'] : max( $columns * 2, 6 ) ) ) );

	if ( ! $channel_id || ! $api_key ) {
		return '<p class="esquina-yt__error">' . esc_html__( 'Indica channel_id y api_key (o define ESQUINA_YT_API_KEY en wp-config.php).', 'esquina-mis-funciones' ) . '</p>';
	}

	wp_enqueue_style(
		'esquina-yt-feed',
		ESQUINA_MF_URL . 'assets/css/youtube-feed.css',
		array(),
		ESQUINA_MF_VERSION
	);
	wp_enqueue_script(
		'esquina-yt-feed',
		ESQUINA_MF_URL . 'assets/js/youtube-feed.js',
		array(),
		ESQUINA_MF_VERSION,
		true
	);

	$unlimited   = ( 0 === $max );
	$first_count = $unlimited ? $batch : $max;
	$seen_ids    = array();

	$cache_key = 'esquina_yt_v5_' . md5( $channel_id . '|' . $mode . '|' . $first_count . '|' . $max . '|' . substr( hash( 'sha256', $api_key ), 0, 12 ) );
	$result    = get_transient( $cache_key );

	if ( false === $result || ! is_array( $result ) ) {
		$result = esquina_yt_fetch_videos_batch( $api_key, $channel_id, $mode, $first_count, '', $seen_ids );
		if ( is_wp_error( $result ) ) {
			return '<p class="esquina-yt__error">' . esc_html( $result->get_error_message() ) . '</p>';
		}
		set_transient( $cache_key, $result, ESQUINA_YT_CACHE_TTL );
	} else {
		foreach ( $result['videos'] as $v ) {
			$seen_ids[ $v['id'] ] = true;
		}
	}

	if ( is_wp_error( $result ) ) {
		return '<p class="esquina-yt__error">' . esc_html( $result->get_error_message() ) . '</p>';
	}

	$videos = isset( $result['videos'] ) ? $result['videos'] : array();
	if ( empty( $videos ) ) {
		$msg = ( 'short' === $mode )
			? __( 'No se encontraron videos cortos (≤ 60 s).', 'esquina-mis-funciones' )
			: __( 'No se encontraron videos largos (> 60 s).', 'esquina-mis-funciones' );
		return '<p class="esquina-yt__error">' . esc_html( $msg ) . '</p>';
	}

	$session_id = '';
	if ( $unlimited ) {
		$session_id = esquina_yt_create_session( $api_key, $channel_id, $mode );
		set_transient(
			'esquina_yt_seen_' . $session_id,
			array_keys( $seen_ids ),
			HOUR_IN_SECONDS
		);
	}

	$uid    = 'yt-' . wp_generate_uuid4();
	$class  = 'esquina-yt esquina-yt--' . ( 'short' === $mode ? 'short' : 'long' );
	$style  = '--esquina-yt-columns:' . $columns . ';';

	$config = array(
		'session'         => $session_id,
		'ajax_url'        => admin_url( 'admin-ajax.php' ),
		'nonce'           => wp_create_nonce( 'esquina_yt_feed' ),
		'mode'            => $mode,
		'batch'           => $batch,
		'unlimited'       => $unlimited,
		'next_page_token' => isset( $result['nextPageToken'] ) ? $result['nextPageToken'] : '',
		'has_more'        => ! empty( $result['has_more'] ),
		'strings'         => array(
			'load_more' => __( 'Cargar más videos', 'esquina-mis-funciones' ),
			'loading'   => __( 'Cargando…', 'esquina-mis-funciones' ),
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
	<div
		id="<?php echo esc_attr( $uid ); ?>"
		class="<?php echo esc_attr( $class ); ?>"
		style="<?php echo esc_attr( $style ); ?>"
		data-config="<?php echo esc_attr( $json ); ?>"
	>
		<div class="esquina-yt__rail" role="list">
			<?php foreach ( $videos as $v ) : ?>
				<?php echo esquina_yt_card_html( $v, $mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>
		</div>

		<?php if ( $unlimited && $session_id ) : ?>
			<div class="esquina-yt__more-wrap">
				<button type="button" class="esquina-yt__load-more" data-esquina-yt-load-more>
					<?php esc_html_e( 'Cargar más videos', 'esquina-mis-funciones' ); ?>
				</button>
			</div>
		<?php endif; ?>

		<div class="esquina-yt-modal" hidden>
			<div class="esquina-yt-modal__backdrop" tabindex="-1"></div>
			<div class="esquina-yt-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $uid ); ?>-title">
				<button type="button" class="esquina-yt-modal__close" aria-label="<?php esc_attr_e( 'Cerrar', 'esquina-mis-funciones' ); ?>">×</button>
				<div class="esquina-yt-modal__embed"></div>
				<div class="esquina-yt-modal__footer">
					<h2 id="<?php echo esc_attr( $uid ); ?>-title" class="esquina-yt-modal__heading"></h2>
					<a class="esquina-yt-modal__link" href="#" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ver en YouTube', 'esquina-mis-funciones' ); ?></a>
				</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * HTML de una tarjeta.
 *
 * @param array  $v    { id, title, thumb }.
 * @param string $mode long|short.
 * @return string
 */
function esquina_yt_card_html( array $v, $mode ) {
	$thumb = esquina_yt_video_thumb_url(
		isset( $v['id'] ) ? $v['id'] : '',
		isset( $v['thumb'] ) ? $v['thumb'] : '',
		$mode
	);
	ob_start();
	?>
	<button
		type="button"
		class="esquina-yt__card"
		role="listitem"
		data-video-id="<?php echo esc_attr( $v['id'] ); ?>"
		data-video-title="<?php echo esc_attr( $v['title'] ); ?>"
		aria-label="<?php echo esc_attr( $v['title'] ); ?>"
	>
		<span class="esquina-yt__thumb-wrap">
			<img
				class="esquina-yt__thumb"
				src="<?php echo esc_url( $thumb ); ?>"
				alt=""
				loading="lazy"
				decoding="async"
			/>
			<span class="esquina-yt__preview" aria-hidden="true"></span>
		</span>
		<span class="esquina-yt__title"><?php echo esc_html( $v['title'] ); ?></span>
	</button>
	<?php
	return ob_get_clean();
}

/**
 * AJAX: siguiente lote.
 */
function esquina_yt_ajax_load_more() {
	check_ajax_referer( 'esquina_yt_feed', 'nonce' );

	$session_id = isset( $_POST['session'] ) ? sanitize_text_field( wp_unslash( $_POST['session'] ) ) : '';
	$page_token = isset( $_POST['page_token'] ) ? sanitize_text_field( wp_unslash( $_POST['page_token'] ) ) : '';
	$batch      = isset( $_POST['batch'] ) ? intval( wp_unslash( $_POST['batch'] ) ) : 6;
	$batch      = min( 50, max( 1, $batch ) );

	$session = esquina_yt_get_session( $session_id );
	if ( ! $session ) {
		wp_send_json_error( array( 'message' => 'Session expired' ), 403 );
	}

	$seen_list = get_transient( 'esquina_yt_seen_' . $session_id );
	$seen_ids  = array();
	if ( is_array( $seen_list ) ) {
		foreach ( $seen_list as $id ) {
			$seen_ids[ $id ] = true;
		}
	}

	$result = esquina_yt_fetch_videos_batch(
		$session['api_key'],
		$session['channel_id'],
		$session['mode'],
		$batch,
		$page_token,
		$seen_ids
	);

	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), 502 );
	}

	foreach ( $result['videos'] as $v ) {
		$seen_ids[ $v['id'] ] = true;
	}
	set_transient( 'esquina_yt_seen_' . $session_id, array_keys( $seen_ids ), HOUR_IN_SECONDS );

	$html = '';
	foreach ( $result['videos'] as $v ) {
		$html .= esquina_yt_card_html( $v, $session['mode'] );
	}

	wp_send_json_success(
		array(
			'html'            => $html,
			'next_page_token' => isset( $result['nextPageToken'] ) ? $result['nextPageToken'] : '',
			'has_more'        => ! empty( $result['has_more'] ),
			'count'           => count( $result['videos'] ),
		)
	);
}

add_action( 'wp_ajax_esquina_yt_more', 'esquina_yt_ajax_load_more' );
add_action( 'wp_ajax_nopriv_esquina_yt_more', 'esquina_yt_ajax_load_more' );

/**
 * [youtube_largo]
 */
function esquina_mf_youtube_largo_shortcode( $atts ) {

	$options = get_option(
		'esquina_youtube_settings',
		[]
	);

	$atts = shortcode_atts(
		array(
			'channel_id' => $options['channel_id'] ?? '',
			'api_key'    => $options['api_key'] ?? '',
			'max'        => $options['max_largos'] ?? '6',
			'columns'    => $options['columns_largos'] ?? '3',
			'batch'      => $options['batch_largos'] ?? '6',
		),
		$atts,
		'youtube_largo'
	);

	return esquina_yt_render_feed(
		array(
			'channel_id' => $atts['channel_id'],
			'api_key'    => $atts['api_key'],
			'max'        => $atts['max'],
			'columns'    => $atts['columns'],
			'batch'      => $atts['batch'],
			'variant'    => 'long',
		)
	);
}

/**
 * [youtube_shorts]
 */
function esquina_mf_youtube_shorts_shortcode( $atts ) {

	$options = get_option(
		'esquina_youtube_settings',
		[]
	);

	$atts = shortcode_atts(
		array(
			'channel_id' => $options['channel_id'] ?? '',
			'api_key'    => $options['api_key'] ?? '',
			'max'        => $options['max_shorts'] ?? '6',
			'columns'    => $options['columns_shorts'] ?? '4',
			'batch'      => $options['batch_shorts'] ?? '8',
		),
		$atts,
		'youtube_shorts'
	);

	return esquina_yt_render_feed(
		array(
			'channel_id' => $atts['channel_id'],
			'api_key'    => $atts['api_key'],
			'max'        => $atts['max'],
			'columns'    => $atts['columns'],
			'batch'      => $atts['batch'],
			'variant'    => 'short',
		)
	);
}

add_shortcode( 'youtube_largo', 'esquina_mf_youtube_largo_shortcode' );
add_shortcode( 'youtube_shorts', 'esquina_mf_youtube_shorts_shortcode' );
