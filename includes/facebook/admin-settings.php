<?php
/**
 * Admin: configuración Facebook + OAuth.
 *
 * @package Mis_Funciones
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitiza opciones; preserva secret/token si el campo viene vacío o enmascarado.
 *
 * @param array $input Datos del formulario.
 * @return array
 */
function esquina_facebook_sanitize_settings( $input ) {
	$existing = get_option( 'esquina_facebook_settings', array() );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}
	if ( ! is_array( $input ) ) {
		return $existing;
	}

	$output = array();

	$output['app_id'] = isset( $input['app_id'] ) ? sanitize_text_field( $input['app_id'] ) : '';

	if ( esquina_facebook_is_masked_secret( $input['app_secret'] ?? '' ) || ( isset( $input['app_secret'] ) && trim( (string) $input['app_secret'] ) === '' && ! empty( $existing['app_secret'] ) ) ) {
		$output['app_secret'] = $existing['app_secret'] ?? '';
	} else {
		$output['app_secret'] = isset( $input['app_secret'] ) ? trim( (string) $input['app_secret'] ) : '';
	}

	if ( esquina_facebook_is_masked_secret( $input['access_token'] ?? '' ) || ( isset( $input['access_token'] ) && trim( (string) $input['access_token'] ) === '' && ! empty( $existing['access_token'] ) ) ) {
		$output['access_token'] = $existing['access_token'] ?? '';
	} else {
		$output['access_token'] = isset( $input['access_token'] ) ? trim( (string) $input['access_token'] ) : '';
	}

	$output['page_id']   = isset( $input['page_id'] ) ? preg_replace( '/[^0-9]/', '', $input['page_id'] ) : '';
	$output['limit']     = isset( $input['limit'] ) ? min( 100, max( 1, intval( $input['limit'] ) ) ) : 25;
	$output['per_page']  = isset( $input['per_page'] ) ? min( 20, max( 1, intval( $input['per_page'] ) ) ) : 4;

	$output['connected_at']   = $existing['connected_at'] ?? 0;
	$output['token_expires']  = $existing['token_expires'] ?? 0;
	$output['connected_page'] = $existing['connected_page'] ?? '';

	return $output;
}

/**
 * @param string $value Valor del campo.
 * @return bool
 */
function esquina_facebook_is_masked_secret( $value ) {
	$value = trim( (string) $value );
	if ( $value === '' ) {
		return true;
	}
	return (bool) preg_match( '/^\*+$/', $value );
}

/**
 * Máscara para mostrar en inputs password.
 *
 * @param string $value Valor real.
 * @return string
 */
function esquina_facebook_mask_value( $value ) {
	if ( ! is_string( $value ) || $value === '' ) {
		return '';
	}
	return str_repeat( '*', min( 24, max( 12, strlen( $value ) ) ) );
}

function esquina_facebook_register_settings() {
	register_setting(
		'esquina_facebook_group',
		'esquina_facebook_settings',
		array(
			'sanitize_callback' => 'esquina_facebook_sanitize_settings',
		)
	);
}

add_action( 'admin_init', 'esquina_facebook_register_settings' );

function esquina_facebook_menu() {
	add_submenu_page(
		'esquinaweb-dashboard',
		'Facebook',
		'Facebook',
		'manage_options',
		'esquina-facebook',
		'esquina_facebook_page'
	);
}

add_action( 'admin_menu', 'esquina_facebook_menu' );

function esquina_facebook_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$options       = function_exists( 'esquina_fb_get_settings' ) ? esquina_fb_get_settings() : get_option( 'esquina_facebook_settings', array() );
	$has_token     = ! empty( $options['access_token'] );
	$has_app       = ! empty( $options['app_id'] ) && ! empty( $options['app_secret'] );
	$redirect_uri  = function_exists( 'esquina_fb_oauth_redirect_uri' ) ? esquina_fb_oauth_redirect_uri() : '';
	$connect_url   = wp_nonce_url( admin_url( 'admin.php?page=esquina-facebook&action=esquina_fb_connect' ), 'esquina_fb_connect' );
	$disconnect_url = wp_nonce_url( admin_url( 'admin.php?page=esquina-facebook&action=esquina_fb_disconnect' ), 'esquina_fb_disconnect' );

	$token_expires = isset( $options['token_expires'] ) ? (int) $options['token_expires'] : 0;
	$connected_at  = isset( $options['connected_at'] ) ? (int) $options['connected_at'] : 0;
	$page_name     = isset( $options['connected_page'] ) ? $options['connected_page'] : '';
	?>
	<div class="wrap">

		<h1><?php esc_html_e( 'Configuración Facebook', 'esquina-mis-funciones' ); ?></h1>

		<div class="card" style="max-width:720px;padding:16px 20px;margin:16px 0;">
			<h2 style="margin-top:0;"><?php esc_html_e( 'Conexión', 'esquina-mis-funciones' ); ?></h2>

			<?php if ( $has_token ) : ?>
				<p style="color:#008a20;font-weight:600;">
					<?php esc_html_e( 'Estado: conectado', 'esquina-mis-funciones' ); ?>
					<?php if ( $page_name ) : ?>
						— <?php echo esc_html( $page_name ); ?>
					<?php endif; ?>
				</p>
				<?php if ( $connected_at ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: %s: datetime */
							esc_html__( 'Conectado el: %s', 'esquina-mis-funciones' ),
							esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $connected_at ) )
						);
						?>
					</p>
				<?php endif; ?>
				<?php if ( $token_expires ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: %s: date */
							esc_html__( 'Renovar conexión antes del: %s', 'esquina-mis-funciones' ),
							esc_html( date_i18n( get_option( 'date_format' ), $token_expires ) )
						);
						?>
					</p>
				<?php endif; ?>
				<?php
				$token_preview = isset( $options['access_token'] ) ? $options['access_token'] : '';
				if ( $token_preview && strlen( $token_preview ) > 8 ) :
					?>
					<p class="description">
						<?php
						printf(
							/* translators: %s: last characters of token */
							esc_html__( 'Token guardado en la base de datos (termina en …%s). Ver campo Access Token abajo.', 'esquina-mis-funciones' ),
							esc_html( substr( $token_preview, -8 ) )
						);
						?>
					</p>
				<?php endif; ?>
			<?php else : ?>
				<p style="color:#b32d2e;font-weight:600;"><?php esc_html_e( 'Estado: sin conectar', 'esquina-mis-funciones' ); ?></p>
			<?php endif; ?>

			<p>
				<a href="<?php echo esc_url( $connect_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Conectar con Facebook', 'esquina-mis-funciones' ); ?>
				</a>
				<?php if ( $has_token ) : ?>
					<a href="<?php echo esc_url( $disconnect_url ); ?>" class="button" onclick="return confirm('<?php echo esc_js( __( '¿Eliminar el token guardado?', 'esquina-mis-funciones' ) ); ?>');">
						<?php esc_html_e( 'Desconectar', 'esquina-mis-funciones' ); ?>
					</a>
				<?php endif; ?>
			</p>

			<?php if ( ! $has_app ) : ?>
				<p class="description"><?php esc_html_e( 'Guarda App ID y App Secret abajo antes de conectar.', 'esquina-mis-funciones' ); ?></p>
			<?php endif; ?>
		</div>

		<form method="post" action="options.php">

			<?php settings_fields( 'esquina_facebook_group' ); ?>

			<table class="form-table">

				<tr>
					<th scope="row"><?php esc_html_e( 'App ID', 'esquina-mis-funciones' ); ?></th>
					<td>
						<input
							type="text"
							name="esquina_facebook_settings[app_id]"
							value="<?php echo esc_attr( $options['app_id'] ?? '' ); ?>"
							class="regular-text"
							autocomplete="off"
						/>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'App Secret', 'esquina-mis-funciones' ); ?></th>
					<td>
						<div style="display:flex;gap:10px;align-items:center;">
							<input
								type="password"
								id="esquina_fb_app_secret"
								name="esquina_facebook_settings[app_secret]"
								value="<?php echo esc_attr( $options['app_secret'] ?? '' ); ?>"
								class="regular-text"
								autocomplete="new-password"
								placeholder="<?php esc_attr_e( 'Pega tu App Secret', 'esquina-mis-funciones' ); ?>"
							/>
							<button type="button" class="button" onclick="esquinaFbToggleField('esquina_fb_app_secret')" aria-label="<?php esc_attr_e( 'Mostrar u ocultar', 'esquina-mis-funciones' ); ?>">👁</button>
						</div>
						<p class="description"><?php esc_html_e( 'Oculto por defecto. Usa el ojito para ver el secret guardado.', 'esquina-mis-funciones' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Redirect URI (Meta)', 'esquina-mis-funciones' ); ?></th>
					<td>
						<input type="text" readonly class="large-text code" value="<?php echo esc_attr( $redirect_uri ); ?>" onclick="this.select();" />
						<p class="description">
							<?php esc_html_e( 'Copia esta URL en Meta for Developers → tu App → Facebook Login → Configuración → Valid OAuth Redirect URIs. Debe coincidir exactamente (https, dominio y ruta). No la abras manualmente en el navegador.', 'esquina-mis-funciones' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Page ID', 'esquina-mis-funciones' ); ?></th>
					<td>
						<input
							type="text"
							name="esquina_facebook_settings[page_id]"
							value="<?php echo esc_attr( $options['page_id'] ?? '' ); ?>"
							class="regular-text"
						/>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Access Token (página)', 'esquina-mis-funciones' ); ?></th>
					<td>
						<div style="display:flex;gap:10px;align-items:center;">
							<input
								type="password"
								id="esquina_fb_token"
								name="esquina_facebook_settings[access_token]"
								value="<?php echo esc_attr( $options['access_token'] ?? '' ); ?>"
								class="large-text code"
								autocomplete="new-password"
								placeholder="<?php echo $has_token ? esc_attr__( 'Token guardado — usa el ojito para verlo', 'esquina-mis-funciones' ) : esc_attr__( 'Se llena al conectar o pégalo manualmente', 'esquina-mis-funciones' ); ?>"
							/>
							<button type="button" class="button" onclick="esquinaFbToggleField('esquina_fb_token')" aria-label="<?php esc_attr_e( 'Mostrar u ocultar', 'esquina-mis-funciones' ); ?>">👁</button>
						</div>
						<p class="description">
							<?php esc_html_e( 'Al usar «Conectar con Facebook», el token de la página se guarda aquí automáticamente. El ojito muestra u oculta el valor.', 'esquina-mis-funciones' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Límite API', 'esquina-mis-funciones' ); ?></th>
					<td>
						<input
							type="number"
							min="1"
							max="100"
							name="esquina_facebook_settings[limit]"
							value="<?php echo esc_attr( $options['limit'] ?? 25 ); ?>"
						/>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Publicaciones por página', 'esquina-mis-funciones' ); ?></th>
					<td>
						<input
							type="number"
							min="1"
							max="20"
							name="esquina_facebook_settings[per_page]"
							value="<?php echo esc_attr( $options['per_page'] ?? 4 ); ?>"
						/>
					</td>
				</tr>

			</table>

			<?php submit_button(); ?>

		</form>

	</div>

	<script>
	function esquinaFbToggleField(id) {
		var field = document.getElementById(id);
		if (!field) return;
		field.type = field.type === 'password' ? 'text' : 'password';
	}
	</script>
	<?php
}
