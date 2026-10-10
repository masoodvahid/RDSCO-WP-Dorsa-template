<?php
/**
 * One-time project seeding helpers. Loaded only while /setup exists in the theme.
 *
 * The content importer runs in the browser (block editor) so every block is serialized
 * by WordPress itself. These two admin-only endpoints cover what REST core cannot:
 *   POST rdsco/v1/seed/apply    theme mods + site icon
 *   POST rdsco/v1/seed/cleanup  delete the /setup folder (media, content.json)
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'rest_api_init',
	function () {
		$permission = fn() => current_user_can( 'manage_options' );

		register_rest_route(
			'rdsco/v1',
			'/seed/apply',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => function ( WP_REST_Request $req ) {
					$mods    = (array) $req->get_param( 'mods' );
					$applied = array();
					foreach ( $mods as $key => $value ) {
						$key = sanitize_key( $key );
						if ( 'custom_logo' === $key ) {
							set_theme_mod( 'custom_logo', absint( $value ) );
						} elseif ( str_starts_with( $key, 'rdsco_' ) ) {
							set_theme_mod( $key, sanitize_textarea_field( (string) $value ) );
						} else {
							continue;
						}
						$applied[] = $key;
					}
					$icon = absint( $req->get_param( 'site_icon' ) );
					if ( $icon && wp_attachment_is_image( $icon ) ) {
						update_option( 'site_icon', $icon );
						$applied[] = 'site_icon';
					}
					flush_rewrite_rules();
					return array( 'applied' => $applied );
				},
			)
		);

		register_rest_route(
			'rdsco/v1',
			'/seed/cleanup',
			array(
				'methods'             => 'POST',
				'permission_callback' => $permission,
				'callback'            => function () {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					WP_Filesystem();
					global $wp_filesystem;
					$ok = $wp_filesystem && $wp_filesystem->delete( RDSCO_DIR . '/setup', true );
					return array( 'deleted' => (bool) $ok );
				},
			)
		);
	}
);

add_action(
	'admin_notices',
	function () {
		if ( current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-info"><p>' . esc_html__( 'RDSCo: the theme still contains its /setup folder (seed content). It is removed automatically after the import.', 'rdsco' ) . '</p></div>';
		}
	}
);
