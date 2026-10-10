<?php
/**
 * Products, product categories, solutions and stored contact messages.
 *
 * Note: post types live in the theme for this project. If the site may switch
 * themes later, move this file into a small must-use plugin unchanged.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'init',
	function () {
		register_post_type(
			'rds_product',
			array(
				'labels'        => array(
					'name'          => __( 'Products', 'rdsco' ),
					'singular_name' => __( 'Product', 'rdsco' ),
					'add_new_item'  => __( 'Add product', 'rdsco' ),
					'edit_item'     => __( 'Edit product', 'rdsco' ),
					'all_items'     => __( 'All products', 'rdsco' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-visibility',
				'menu_position' => 20,
				'has_archive'   => 'products',
				'rewrite'       => array( 'slug' => 'products', 'with_front' => false ),
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields', 'revisions' ),
			)
		);

		register_taxonomy(
			'rds_product_cat',
			'rds_product',
			array(
				'labels'            => array(
					'name'          => __( 'Product families', 'rdsco' ),
					'singular_name' => __( 'Product family', 'rdsco' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'product-family', 'with_front' => false ),
			)
		);

		register_post_type(
			'rds_solution',
			array(
				'labels'        => array(
					'name'          => __( 'Solutions', 'rdsco' ),
					'singular_name' => __( 'Solution', 'rdsco' ),
					'add_new_item'  => __( 'Add solution', 'rdsco' ),
					'edit_item'     => __( 'Edit solution', 'rdsco' ),
					'all_items'     => __( 'All solutions', 'rdsco' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'menu_icon'     => 'dashicons-building',
				'menu_position' => 21,
				'has_archive'   => 'solutions',
				'rewrite'       => array( 'slug' => 'solutions', 'with_front' => false ),
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields', 'revisions' ),
			)
		);

		// Contact-form submissions, kept in admin so no lead is lost if email delivery fails.
		register_post_type(
			'rds_message',
			array(
				'labels'          => array(
					'name'          => __( 'Messages', 'rdsco' ),
					'singular_name' => __( 'Message', 'rdsco' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-email-alt',
				'menu_position'   => 25,
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
	}
);

// Archive ordering by menu order.
add_action(
	'pre_get_posts',
	function ( WP_Query $q ) {
		if ( is_admin() || ! $q->is_main_query() ) {
			return;
		}
		if ( $q->is_post_type_archive( array( 'rds_product', 'rds_solution' ) ) || $q->is_tax( 'rds_product_cat' ) ) {
			$q->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
			$q->set( 'posts_per_page', 100 );
		}
	}
);
