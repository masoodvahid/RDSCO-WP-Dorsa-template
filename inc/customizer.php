<?php
/**
 * Customizer: company details, header button, footer and closing call-to-action.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings grouped by section: key => [label, control type].
 */
function rdsco_customizer_fields(): array {
	return array(
		'rdsco_company' => array(
			'title'  => __( 'Company details', 'rdsco' ),
			'fields' => array(
				'company_name' => array( __( 'Legal name', 'rdsco' ), 'text' ),
				'address'      => array( __( 'Address', 'rdsco' ), 'textarea' ),
				'phone'        => array( __( 'Phone', 'rdsco' ), 'text' ),
				'email'        => array( __( 'Public email', 'rdsco' ), 'email' ),
				'form_email'   => array( __( 'Contact form recipient (defaults to admin email)', 'rdsco' ), 'email' ),
			),
		),
		'rdsco_header'  => array(
			'title'  => __( 'Header', 'rdsco' ),
			'fields' => array(
				'header_cta_label' => array( __( 'Button label', 'rdsco' ), 'text' ),
				'header_cta_url'   => array( __( 'Button link (empty = contact page)', 'rdsco' ), 'url' ),
			),
		),
		'rdsco_footer'  => array(
			'title'  => __( 'Footer', 'rdsco' ),
			'fields' => array(
				'footer_text' => array( __( 'Short description under the logo', 'rdsco' ), 'textarea' ),
				'copyright'   => array( __( 'Copyright line ({year} = current year)', 'rdsco' ), 'text' ),
				'cta_show'    => array( __( 'Show closing call-to-action band', 'rdsco' ), 'checkbox' ),
				'cta_title'   => array( __( 'Call-to-action title', 'rdsco' ), 'text' ),
				'cta_text'    => array( __( 'Call-to-action text', 'rdsco' ), 'textarea' ),
				'cta_label'   => array( __( 'Call-to-action button label', 'rdsco' ), 'text' ),
			),
		),
	);
}

add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		$wp_customize->add_panel( 'rdsco', array( 'title' => __( 'Theme options', 'rdsco' ), 'priority' => 30 ) );
		foreach ( rdsco_customizer_fields() as $section => $def ) {
			$wp_customize->add_section( $section, array( 'title' => $def['title'], 'panel' => 'rdsco' ) );
			foreach ( $def['fields'] as $key => $field ) {
				$sanitize = match ( $field[1] ) {
					'email'    => 'sanitize_email',
					'url'      => 'esc_url_raw',
					'textarea' => 'sanitize_textarea_field',
					'checkbox' => fn( $v ) => $v ? '1' : '',
					default    => 'sanitize_text_field',
				};
				$wp_customize->add_setting(
					'rdsco_' . $key,
					array(
						'default'           => 'cta_show' === $key ? '1' : '',
						'sanitize_callback' => $sanitize,
					)
				);
				$wp_customize->add_control(
					'rdsco_' . $key,
					array(
						'label'   => $field[0],
						'section' => $section,
						'type'    => $field[1],
					)
				);
			}
		}
	}
);

// Register translatable strings with Polylang when it is installed later.
add_action(
	'init',
	function () {
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}
		foreach ( array( 'company_name', 'address', 'header_cta_label', 'footer_text', 'copyright', 'cta_title', 'cta_text', 'cta_label' ) as $key ) {
			$value = (string) get_theme_mod( 'rdsco_' . $key, '' );
			if ( '' !== $value ) {
				pll_register_string( 'rdsco_' . $key, $value, 'RDSCo theme', str_contains( $key, 'text' ) || 'address' === $key );
			}
		}
	}
);
