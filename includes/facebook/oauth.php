<?php
/**
 * OAuth Facebook: conectar y obtener Page Access Token.
 *
 * @package Mis_Funciones
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ESQUINA_FB_GRAPH_VERSION', 'v25.0' );

/**
 * @return array
 */
function esquina_fb_get_settings() {
	$settings = get_option( 'esquina_facebook_settings', array() );
	return is_array( $settings ) ? $settings : array();
}

/**
 * @return string
 */
function esquina_fb_oauth_redirect_uri() {
	return admin_url( 'admin.php?page=esquina-facebook&action=esquina_fb_callback' );
}

/**
 * Permisos necesarios para el feed de publicaciones.
 *
 * @return string
 */
function esquina_fb_oauth_scopes() {
	return implode(
		',',
		array(
			'pages_show_list',
			'pages_read_engagement',
			'pages_read_user_content',
			'public_profile',
		)
	);
}

/**
 * @param string $url URL completa.
 * @return array|WP_Error
 */
function esquina_fb_graph_request( $url ) {
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
		return new WP_Error( 'esquina_fb_json', __( 'Respuesta inválida de Facebook.', 'esquina-mis-funciones' ) );
	}

	if ( ! empty( $data['error']['message'] ) ) {
		return new WP_Error(
			'esquina_fb_api',
			sanitize_text_field( $data['error']['message'] )
		);
	}

	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'esquina_fb_http', __( 'Error HTTP al contactar Facebook.', 'esquina-mis-funciones' ) );
	}

	return $data;
}

/**
 * Guarda aviso admin tras redirect.
 *
 * @param string $type    success|error|warning.
 * @param string $message Texto.
 */
function esquina_fb_set_admin_notice( $type, $message ) {
	set_transient(
		'esquina_fb_admin_notice',
		array(
			'type'    => $type,
			'message' => $message,
		),
		45
	);
}

/**
 * Muestra avisos OAuth.
 */
function esquina_fb_admin_notices() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice = get_transient( 'esquina_fb_admin_notice' );
	if ( ! is_array( $notice ) || empty( $notice['message'] ) ) {
		return;
	}

	delete_transient( 'esquina_fb_admin_notice' );

	$class = 'notice notice-info';
	if ( 'success' === ( $notice['type'] ?? '' ) ) {
		$class = 'notice notice-success';
	} elseif ( 'error' === ( $notice['type'] ?? '' ) ) {
		$class = 'notice notice-error';
	} elseif ( 'warning' === ( $notice['type'] ?? '' ) ) {
		$class = 'notice notice-warning';
	}

	printf(
		'<div class="%1$s is-dismissible"><p>%2$s</p></div>',
		esc_attr( $class ),
		esc_html( $notice['message'] )
	);
}

add_action( 'admin_notices', 'esquina_fb_admin_notices' );

/**
 * Redirección externa (wp_safe_redirect solo permite el mismo sitio).
 *
 * @param string $url URL destino.
 */
function esquina_fb_redirect_external( $url ) {
	wp_redirect( $url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
	exit;
}

/**
 * Inicia login OAuth.
 */
function esquina_fb_oauth_start() {
	$settings = esquina_fb_get_settings();
	$app_id   = isset( $settings['app_id'] ) ? trim( $settings['app_id'] ) : '';
	$page_id  = isset( $settings['page_id'] ) ? preg_replace( '/[^0-9]/', '', $settings['page_id'] ) : '';

	if ( ! $app_id || empty( $settings['app_secret'] ) ) {
		esquina_fb_set_admin_notice( 'error', __( 'Guarda App ID y App Secret antes de conectar.', 'esquina-mis-funciones' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
		exit;
	}

	if ( ! $page_id ) {
		esquina_fb_set_admin_notice( 'error', __( 'Indica el Page ID antes de conectar.', 'esquina-mis-funciones' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
		exit;
	}

	$state = wp_create_nonce( 'esquina_fb_oauth_state' );

	$auth_url = sprintf(
		'https://www.facebook.com/%1$s/dialog/oauth?client_id=%2$s&redirect_uri=%3$s&state=%4$s&scope=%5$s&response_type=code',
		ESQUINA_FB_GRAPH_VERSION,
		rawurlencode( $app_id ),
		rawurlencode( esquina_fb_oauth_redirect_uri() ),
		rawurlencode( $state ),
		rawurlencode( esquina_fb_oauth_scopes() )
	);

	esquina_fb_redirect_external( $auth_url );
}

/**
 * Callback OAuth: code → token de página.
 */
function esquina_fb_oauth_callback() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permisos insuficientes.', 'esquina-mis-funciones' ) );
	}

	$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';
	if ( ! wp_verify_nonce( $state, 'esquina_fb_oauth_state' ) ) {
		esquina_fb_set_admin_notice( 'error', __( 'Sesión OAuth inválida. Intenta de nuevo.', 'esquina-mis-funciones' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
		exit;
	}

	if ( ! empty( $_GET['error'] ) ) {
		$msg = isset( $_GET['error_description'] ) ? sanitize_text_field( wp_unslash( $_GET['error_description'] ) ) : sanitize_text_field( wp_unslash( $_GET['error'] ) );
		esquina_fb_set_admin_notice( 'error', $msg );
		wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
		exit;
	}

	$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
	if ( ! $code ) {
		esquina_fb_set_admin_notice( 'error', __( 'Facebook no devolvió código de autorización.', 'esquina-mis-funciones' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
		exit;
	}

	$settings = esquina_fb_get_settings();
	$app_id   = isset( $settings['app_id'] ) ? trim( $settings['app_id'] ) : '';
	$secret   = isset( $settings['app_secret'] ) ? trim( $settings['app_secret'] ) : '';
	$page_id  = isset( $settings['page_id'] ) ? preg_replace( '/[^0-9]/', '', $settings['page_id'] ) : '';

	if ( ! $app_id || ! $secret || ! $page_id ) {
		esquina_fb_set_admin_notice( 'error', __( 'Faltan App ID, App Secret o Page ID en la configuración.', 'esquina-mis-funciones' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
		exit;
	}

	$redirect = rawurlencode( esquina_fb_oauth_redirect_uri() );

	// 1) Token corto.
	$token_url = sprintf(
		'https://graph.facebook.com/%1$s/oauth/access_token?client_id=%2$s&redirect_uri=%3$s&client_secret=%4$s&code=%5$s',
		ESQUINA_FB_GRAPH_VERSION,
		rawurlencode( $app_id ),
		$redirect,
		rawurlencode( $secret ),
		rawurlencode( $code )
	);

	$short = esquina_fb_graph_request( $token_url );
	if ( is_wp_error( $short ) ) {
		esquina_fb_set_admin_notice( 'error', $short->get_error_message() );
		wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
		exit;
	}

	$short_token = isset( $short['access_token'] ) ? $short['access_token'] : '';
	if ( ! $short_token ) {
		esquina_fb_set_admin_notice( 'error', __( 'No se recibió token de acceso.', 'esquina-mis-funciones' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
		exit;
	}

	// 2) Token largo (~60 días).
	$long_url = sprintf(
		'https://graph.facebook.com/%1$s/oauth/access_token?grant_type=fb_exchange_token&client_id=%2$s&client_secret=%3$s&fb_exchange_token=%4$s',
		ESQUINA_FB_GRAPH_VERSION,
		rawurlencode( $app_id ),
		rawurlencode( $secret ),
		rawurlencode( $short_token )
	);

	$long = esquina_fb_graph_request( $long_url );
	if ( is_wp_error( $long ) ) {
		esquina_fb_set_admin_notice( 'error', $long->get_error_message() );
		wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
		exit;
	}

	$user_token  = isset( $long['access_token'] ) ? $long['access_token'] : '';
	$expires_in  = isset( $long['expires_in'] ) ? (int) $long['expires_in'] : 0;

	if ( ! $user_token ) {
		esquina_fb_set_admin_notice( 'error', __( 'No se pudo obtener token de larga duración.', 'esquina-mis-funciones' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
		exit;
	}

	// 3) Token de la página.
	$pages_url = sprintf(
		'https://graph.facebook.com/%1$s/me/accounts?fields=id,name,access_token&access_token=%2$s',
		ESQUINA_FB_GRAPH_VERSION,
		rawurlencode( $user_token )
	);

	$pages = esquina_fb_graph_request( $pages_url );
	if ( is_wp_error( $pages ) ) {
		esquina_fb_set_admin_notice( 'error', $pages->get_error_message() );
		wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
		exit;
	}

	$page_token = '';
	$page_name  = '';
	$items      = isset( $pages['data'] ) && is_array( $pages['data'] ) ? $pages['data'] : array();

	foreach ( $items as $item ) {
		$pid = isset( $item['id'] ) ? preg_replace( '/[^0-9]/', '', $item['id'] ) : '';
		if ( $pid === $page_id && ! empty( $item['access_token'] ) ) {
			$page_token = $item['access_token'];
			$page_name  = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '';
			break;
		}
	}

	if ( ! $page_token ) {
		esquina_fb_set_admin_notice(
			'error',
			__( 'No se encontró token para el Page ID configurado. Verifica que tu usuario sea admin de esa página.', 'esquina-mis-funciones' )
		);
		wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
		exit;
	}

	$settings['access_token']   = $page_token;
	$settings['connected_at']   = time();
	$settings['token_expires']  = $expires_in > 0 ? time() + $expires_in : 0;
	$settings['connected_page'] = $page_name;

	update_option( 'esquina_facebook_settings', $settings );

	// Limpiar caché de posts.
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_esquina_fb_%' OR option_name LIKE '_transient_timeout_esquina_fb_%'" );

	$expires_msg = $expires_in > 0
		? sprintf(
			/* translators: %s: date */
			__( ' Renueva antes del %s.', 'esquina-mis-funciones' ),
			date_i18n( get_option( 'date_format' ), time() + $expires_in )
		)
		: '';

	esquina_fb_set_admin_notice(
		'success',
		__( 'Facebook conectado correctamente.', 'esquina-mis-funciones' ) . $expires_msg
	);

	wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
	exit;
}

/**
 * Desconecta y borra token guardado.
 */
function esquina_fb_oauth_disconnect() {
	$settings = esquina_fb_get_settings();

	unset( $settings['access_token'], $settings['connected_at'], $settings['token_expires'], $settings['connected_page'] );

	update_option( 'esquina_facebook_settings', $settings );

	esquina_fb_set_admin_notice( 'success', __( 'Token de Facebook eliminado.', 'esquina-mis-funciones' ) );

	wp_safe_redirect( admin_url( 'admin.php?page=esquina-facebook' ) );
	exit;
}

/**
 * Enruta acciones OAuth en admin.
 */
function esquina_fb_oauth_handle_actions() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	if ( 'esquina-facebook' !== $page ) {
		return;
	}

	$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

	if ( 'esquina_fb_connect' === $action ) {
		check_admin_referer( 'esquina_fb_connect' );
		esquina_fb_oauth_start();
	}

	if ( 'esquina_fb_callback' === $action ) {
		esquina_fb_oauth_callback();
	}

	if ( 'esquina_fb_disconnect' === $action ) {
		check_admin_referer( 'esquina_fb_disconnect' );
		esquina_fb_oauth_disconnect();
	}
}

add_action( 'admin_init', 'esquina_fb_oauth_handle_actions' );

/**
 * Aviso si el token está por vencer (7 días).
 */
function esquina_fb_token_expiry_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || strpos( $screen->id, 'esquina-facebook' ) === false ) {
		return;
	}

	$settings = esquina_fb_get_settings();
	$expires  = isset( $settings['token_expires'] ) ? (int) $settings['token_expires'] : 0;

	if ( ! $expires || empty( $settings['access_token'] ) ) {
		return;
	}

	$days_left = (int) floor( ( $expires - time() ) / DAY_IN_SECONDS );

	if ( $days_left > 7 ) {
		return;
	}

	$class = $days_left <= 0 ? 'notice-error' : 'notice-warning';
	$msg   = $days_left <= 0
		? __( 'El token de Facebook expiró. Vuelve a conectar desde esta página.', 'esquina-mis-funciones' )
		: sprintf(
			/* translators: %d: days */
			__( 'El token de Facebook vence en %d días. Usa "Conectar con Facebook" para renovarlo.', 'esquina-mis-funciones' ),
			max( 1, $days_left )
		);

	printf(
		'<div class="notice %1$s"><p>%2$s</p></div>',
		esc_attr( $class ),
		esc_html( $msg )
	);
}

add_action( 'admin_notices', 'esquina_fb_token_expiry_notice' );
