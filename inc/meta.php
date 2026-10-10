<?php
/**
 * Product/solution meta and product-family term meta, with plain admin fields (no ACF).
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions: post type => [ meta_key => [label, type, help] ].
 */
function rdsco_meta_fields(): array {
	return array(
		'rds_product'  => array(
			'rds_code'        => array( __( 'Product code', 'rdsco' ), 'string', __( 'Short code shown on cards, e.g. SENS.', 'rdsco' ) ),
			'rds_application' => array( __( 'Application', 'rdsco' ), 'string', __( 'Shown in menus and the page header, e.g. Steel sheet & plate.', 'rdsco' ) ),
			'rds_brochure'    => array( __( 'Brochure (attachment ID)', 'rdsco' ), 'integer', __( 'Media Library ID of the PDF datasheet.', 'rdsco' ) ),
		),
		'rds_solution' => array(
			'rds_badge' => array( __( 'Badge', 'rdsco' ), 'string', __( 'Element tile "Fe:26" or icon "icon:mountain" (mountain, box, wheat, flask, paper, chip, flame, camera).', 'rdsco' ) ),
		),
	);
}

add_action(
	'init',
	function () {
		foreach ( rdsco_meta_fields() as $post_type => $fields ) {
			foreach ( $fields as $key => $def ) {
				register_post_meta(
					$post_type,
					$key,
					array(
						'type'              => $def[1],
						'single'            => true,
						'show_in_rest'      => true,
						'sanitize_callback' => 'integer' === $def[1] ? 'absint' : 'sanitize_text_field',
						'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
					)
				);
			}
		}
		foreach ( array( 'rds_image' => 'integer', 'rds_order' => 'integer' ) as $key => $type ) {
			register_term_meta(
				'rds_product_cat',
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'absint',
					'auth_callback'     => fn() => current_user_can( 'manage_categories' ),
				)
			);
		}
	},
	20
);

// Post meta box.
add_action(
	'add_meta_boxes',
	function () {
		foreach ( array_keys( rdsco_meta_fields() ) as $post_type ) {
			add_meta_box( 'rdsco_details', __( 'Details', 'rdsco' ), 'rdsco_render_meta_box', $post_type, 'side' );
		}
	}
);

function rdsco_render_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'rdsco_meta', 'rdsco_meta_nonce' );
	foreach ( rdsco_meta_fields()[ $post->post_type ] ?? array() as $key => $def ) {
		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br><input type="%5$s" class="widefat" id="%1$s" name="%1$s" value="%3$s"><br><small>%4$s</small></p>',
			esc_attr( $key ),
			esc_html( $def[0] ),
			esc_attr( (string) get_post_meta( $post->ID, $key, true ) ),
			esc_html( $def[2] ),
			'integer' === $def[1] ? 'number' : 'text'
		);
	}
}

add_action(
	'save_post',
	function ( int $post_id, WP_Post $post ) {
		if ( ! isset( $_POST['rdsco_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rdsco_meta_nonce'] ) ), 'rdsco_meta' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( rdsco_meta_fields()[ $post->post_type ] ?? array() as $key => $def ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$raw   = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$value = 'integer' === $def[1] ? absint( $raw ) : sanitize_text_field( $raw );
			if ( '' === $value || 0 === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	},
	10,
	2
);

// Term fields (edit screen only).
add_action(
	'rds_product_cat_edit_form_fields',
	function ( WP_Term $term ) {
		wp_nonce_field( 'rdsco_term', 'rdsco_term_nonce' );
		$fields = array(
			'rds_image' => array( __( 'Image (attachment ID)', 'rdsco' ), __( 'Media Library ID used on the product family tile. Leave empty to use the first product image.', 'rdsco' ) ),
			'rds_order' => array( __( 'Order', 'rdsco' ), __( 'Lower numbers show first.', 'rdsco' ) ),
		);
		foreach ( $fields as $key => $def ) {
			printf(
				'<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td><input type="number" id="%1$s" name="%1$s" value="%3$s"><p class="description">%4$s</p></td></tr>',
				esc_attr( $key ),
				esc_html( $def[0] ),
				esc_attr( (string) get_term_meta( $term->term_id, $key, true ) ),
				esc_html( $def[1] )
			);
		}
	}
);

add_action(
	'edited_rds_product_cat',
	function ( int $term_id ) {
		if ( ! isset( $_POST['rdsco_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rdsco_term_nonce'] ) ), 'rdsco_term' ) || ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		foreach ( array( 'rds_image', 'rds_order' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_term_meta( $term_id, $key, absint( wp_unslash( $_POST[ $key ] ) ) );
			}
		}
	}
);
