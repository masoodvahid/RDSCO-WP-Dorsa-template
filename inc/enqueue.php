<?php
/**
 * Assets: one stylesheet, one deferred script, local font preload.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		$css = RDSCO_DIR . '/assets/css/main.css';
		$js  = RDSCO_DIR . '/assets/js/main.js';

		wp_enqueue_style( 'rdsco-main', RDSCO_URI . '/assets/css/main.css', array(), (string) filemtime( $css ) );
		wp_enqueue_script(
			'rdsco-main',
			RDSCO_URI . '/assets/js/main.js',
			array(),
			(string) filemtime( $js ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
);

add_action(
	'wp_head',
	function () {
		// Preload only the font the current language actually uses.
		$font = is_rtl() ? 'Vazirmatn-Variable.woff2' : 'ibm-plex-sans-latin-wght-normal.woff2';
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( RDSCO_URI . '/assets/fonts/' . $font )
		);
	},
	1
);

// Icon sprite, printed once right after <body>.
add_action(
	'wp_body_open',
	function () {
		get_template_part( 'template-parts/icons' );
	}
);
