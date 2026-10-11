<?php
/**
 * Theme supports, menus, image sizes, block styles and front-end clean-up.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	function () {
		load_theme_textdomain( 'rdsco', RDSCO_DIR . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 96,
				'width'       => 300,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
		add_theme_support( 'editor-styles' );
		add_editor_style( array( 'assets/css/main.css', 'assets/css/editor.css' ) );

		// Palette, fonts and editor controls are defined in theme.json.

		add_image_size( 'rds-card', 800, 500, true );
		add_image_size( 'rds-hero', 1920, 900, false );

		register_nav_menus(
			array(
				'primary'  => __( 'Primary (header)', 'rdsco' ),
				'footer_1' => __( 'Footer column 1', 'rdsco' ),
				'footer_2' => __( 'Footer column 2', 'rdsco' ),
				'footer_3' => __( 'Footer column 3', 'rdsco' ),
				'legal'    => __( 'Footer legal links', 'rdsco' ),
			)
		);

		add_post_type_support( 'page', 'excerpt' );
	}
);

// Button and section styles selectable in the block editor (no JS build needed).
add_action(
	'init',
	function () {
		$buttons = array(
			'rds-primary'     => __( 'Primary', 'rdsco' ),
			'rds-light'       => __( 'Light (on dark)', 'rdsco' ),
			'rds-ghost'       => __( 'Outline', 'rdsco' ),
			'rds-ghost-light' => __( 'Outline (on dark)', 'rdsco' ),
			'rds-arrow'       => __( 'Text link with arrow', 'rdsco' ),
		);
		foreach ( $buttons as $name => $label ) {
			register_block_style( 'core/button', array( 'name' => $name, 'label' => $label ) );
		}
		register_block_style( 'core/list', array( 'name' => 'rds-checks', 'label' => __( 'Check list', 'rdsco' ) ) );
		register_block_style( 'core/table', array( 'name' => 'rds-specs', 'label' => __( 'Specifications', 'rdsco' ) ) );
		register_block_style( 'core/table', array( 'name' => 'rds-refs', 'label' => __( 'References list', 'rdsco' ) ) );
		register_block_style( 'core/table', array( 'name' => 'rds-kv', 'label' => __( 'Key / value', 'rdsco' ) ) );
		register_block_style( 'core/image', array( 'name' => 'rds-frame', 'label' => __( 'Viewfinder frame', 'rdsco' ) ) );
		register_block_style( 'core/group', array( 'name' => 'rds-surface', 'label' => __( 'Light gray band', 'rdsco' ) ) );
		register_block_style( 'core/group', array( 'name' => 'rds-dark', 'label' => __( 'Navy band', 'rdsco' ) ) );
	}
);

// New post types need fresh rewrite rules after activation.
add_action( 'after_switch_theme', 'flush_rewrite_rules' );

// Load only the block CSS that a page actually uses.
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

// Front-end clean-up: emoji, oEmbed discovery, generator, classic theme styles.
add_action(
	'init',
	function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
		remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
		remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		add_filter( 'emoji_svg_url', '__return_false' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	}
);
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_dequeue_style( 'classic-theme-styles' );
	},
	20
);

// Excerpt length/format for cards.
add_filter( 'excerpt_length', fn() => 24 );
add_filter( 'excerpt_more', fn() => '…' );

add_filter(
	'body_class',
	function ( array $classes ): array {
		if ( is_page_template( 'templates/template-sections.php' ) || is_front_page() || is_singular( array( 'rds_product', 'rds_solution' ) ) ) {
			$classes[] = 'rds-has-sections';
		}
		if ( rdsco_is_showcase_home() ) {
			$classes[] = 'rds-showcase-home';
		}
		return $classes;
	}
);
