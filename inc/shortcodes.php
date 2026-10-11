<?php
/**
 * Data-driven blocks of content, placed in pages with the Shortcode block.
 *
 * [rds_product_families style="cards|bento"]   Product family tiles (taxonomy).
 * [rds_products slugs="a,b" category="x" columns="2|3|4" limit="12" exclude="id"]
 * [rds_solutions featured="first|slug" style="grid|preview"]
 *                                              Industry grid, or a list with an image preview panel.
 * [rds_documents]                              Datasheets attached to products.
 * [rds_contact_card]                           Company details from the Customizer.
 * [rds_contact_form]                           See inc/contact-form.php.
 * [rds_slab_inspection]                        See inc/slab-inspection.php.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

add_shortcode(
	'rds_product_families',
	function ( $atts ) {
		$atts  = shortcode_atts( array( 'style' => 'cards' ), $atts, 'rds_product_families' );
		$terms = get_terms(
			array(
				'taxonomy'   => 'rds_product_cat',
				'hide_empty' => false,
				'parent'     => 0,
			)
		);
		if ( ! $terms || is_wp_error( $terms ) ) {
			return '';
		}
		usort( $terms, fn( $a, $b ) => (int) get_term_meta( $a->term_id, 'rds_order', true ) <=> (int) get_term_meta( $b->term_id, 'rds_order', true ) );

		if ( 'bento' === $atts['style'] ) {
			return rdsco_family_bento( $terms );
		}

		$out = '<div class="rds-cats__grid">';
		foreach ( $terms as $term ) {
			$products = get_posts(
				array(
					'post_type'      => 'rds_product',
					'posts_per_page' => 20,
					'orderby'        => array( 'menu_order' => 'ASC' ),
					'tax_query'      => array( array( 'taxonomy' => 'rds_product_cat', 'terms' => $term->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);
			$img_id = (int) get_term_meta( $term->term_id, 'rds_image', true );
			if ( ! $img_id && $products ) {
				$img_id = (int) get_post_thumbnail_id( $products[0] );
			}
			$img  = $img_id ? wp_get_attachment_image( $img_id, 'rds-card', false, array( 'loading' => 'lazy', 'alt' => '', 'sizes' => '(max-width: 560px) 100vw, (max-width: 1024px) 50vw, 300px' ) ) : '';
			$tags = '';
			foreach ( $products as $p ) {
				$code = get_post_meta( $p->ID, 'rds_code', true );
				if ( $code ) {
					$tags .= '<span class="rds-tag">' . esc_html( $code ) . '</span>';
				}
			}
			$out .= sprintf(
				'<article class="rds-cat"><div class="rds-cat__img">%1$s</div><div class="rds-cat__body"><h3><a href="%2$s">%3$s</a></h3><p>%4$s</p>%5$s</div></article>',
				$img,
				esc_url( get_term_link( $term ) ),
				esc_html( $term->name ),
				esc_html( $term->description ),
				$tags ? '<div class="rds-cat__tags">' . $tags . '</div>' : ''
			);
		}
		return $out . '</div>';
	}
);

add_shortcode(
	'rds_products',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'slugs'    => '',
				'category' => '',
				'columns'  => '3',
				'limit'    => '12',
				'exclude'  => '',
			),
			$atts
		);
		$args = array(
			'post_type'      => 'rds_product',
			'posts_per_page' => (int) $atts['limit'],
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'no_found_rows'  => true,
		);
		if ( $atts['slugs'] ) {
			$args['post_name__in'] = array_map( 'sanitize_title', explode( ',', $atts['slugs'] ) );
			$args['orderby']       = 'post_name__in';
		}
		if ( $atts['category'] ) {
			$args['tax_query'] = array( array( 'taxonomy' => 'rds_product_cat', 'field' => 'slug', 'terms' => sanitize_title( $atts['category'] ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
		}
		if ( $atts['exclude'] ) {
			$args['post__not_in'] = array_map( 'absint', explode( ',', $atts['exclude'] ) );
		}
		$products = get_posts( $args );
		if ( ! $products ) {
			return '';
		}
		$cols = max( 2, min( 4, (int) $atts['columns'] ) );
		$out  = '<div class="rds-cards rds-cards--' . $cols . '">';
		foreach ( $products as $product ) {
			$out .= rdsco_product_card( $product );
		}
		return $out . '</div>';
	}
);

add_shortcode(
	'rds_solutions',
	function ( $atts ) {
		$atts      = shortcode_atts(
			array(
				'featured' => '',
				'style'    => 'grid',
			),
			$atts,
			'rds_solutions'
		);
		$solutions = get_posts(
			array(
				'post_type'      => 'rds_solution',
				'posts_per_page' => 50,
				'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
				'no_found_rows'  => true,
			)
		);
		if ( ! $solutions ) {
			return '';
		}
		if ( 'preview' === $atts['style'] ) {
			return rdsco_industry_preview( $solutions, $atts['featured'] );
		}
		$out = '<div class="rds-ind-grid">';
		foreach ( $solutions as $i => $s ) {
			$featured = $atts['featured'] && ( $s->post_name === $atts['featured'] || ( 'first' === $atts['featured'] && 0 === $i ) );
			$out     .= sprintf(
				'<article class="rds-ind%1$s">%2$s<h3><a href="%3$s">%4$s</a></h3><p>%5$s</p>%6$s</article>',
				$featured ? ' rds-ind--featured' : '',
				rdsco_badge( (string) get_post_meta( $s->ID, 'rds_badge', true ) ),
				esc_url( get_permalink( $s ) ),
				esc_html( get_the_title( $s ) ),
				esc_html( get_the_excerpt( $s ) ),
				$featured ? '<span class="rds-ind__more">' . esc_html__( 'Explore', 'rdsco' ) . ' ' . rdsco_icon( 'arrow' ) . '</span>' : ''
			);
		}
		return $out . '</div>';
	}
);

add_shortcode(
	'rds_documents',
	function () {
		$products = get_posts(
			array(
				'post_type'      => 'rds_product',
				'posts_per_page' => 50,
				'orderby'        => array( 'menu_order' => 'ASC' ),
				'meta_key'       => 'rds_brochure', // phpcs:ignore WordPress.DB.SlowDBQuery
				'no_found_rows'  => true,
			)
		);
		$out = '';
		foreach ( $products as $p ) {
			$id   = (int) get_post_meta( $p->ID, 'rds_brochure', true );
			$url  = $id ? wp_get_attachment_url( $id ) : '';
			$file = $id ? get_attached_file( $id ) : '';
			if ( ! $url ) {
				continue;
			}
			$size = $file && file_exists( $file ) ? size_format( filesize( $file ), 1 ) : '';
			$out .= sprintf(
				'<a class="rds-doc" href="%1$s" target="_blank" rel="noopener"><span class="rds-doc__type">PDF</span><span class="rds-doc__label">%2$s<small>%3$s</small></span>%4$s</a>',
				esc_url( $url ),
				esc_html( get_the_title( $id ) ),
				esc_html( trim( __( 'System description', 'rdsco' ) . ( $size ? ' · ' . $size : '' ) ) ),
				rdsco_icon( 'download' )
			);
		}
		return $out ? '<div class="rds-docs">' . $out . '</div>' : '';
	}
);

add_shortcode(
	'rds_contact_card',
	function () {
		$rows = array(
			array( 'pin', __( 'Address', 'rdsco' ), rdsco_mod( 'address' ), '' ),
			array( 'phone', __( 'Phone', 'rdsco' ), rdsco_mod( 'phone' ), 'tel:' . rdsco_tel( rdsco_mod( 'phone' ) ) ),
			array( 'mail', __( 'Email', 'rdsco' ), rdsco_mod( 'email' ), 'mailto:' . rdsco_mod( 'email' ) ),
		);
		$out = '<aside class="rds-contact__card">';
		if ( rdsco_mod( 'company_name' ) ) {
			$out .= '<h2>' . esc_html( rdsco_mod( 'company_name' ) ) . '</h2>';
		}
		foreach ( $rows as $row ) {
			if ( '' === $row[2] ) {
				continue;
			}
			$value = nl2br( esc_html( $row[2] ) );
			if ( $row[3] ) {
				$value = '<a href="' . esc_url( $row[3], array( 'tel', 'mailto' ) ) . '" dir="ltr">' . $value . '</a>';
			}
			$out .= sprintf( '<div class="rds-contact__row"><span>%1$s</span><div><small>%2$s</small><b>%3$s</b></div></div>', rdsco_icon( $row[0] ), esc_html( $row[1] ), $value );
		}
		return $out . '</aside>';
	}
);

/**
 * Product families as a bento grid: the first family is the large tile, and with four families
 * the last one spans two columns. Tile text: family name, tagline (term meta) and product codes.
 *
 * @param WP_Term[] $terms Ordered product families.
 */
function rdsco_family_bento( array $terms ): string {
	$count = count( $terms );
	$out   = '<div class="rds-bento">';
	foreach ( $terms as $i => $term ) {
		$size     = 0 === $i ? 'xl' : ( 4 === $count && $count - 1 === $i ? 'wide' : '' );
		$products = get_posts(
			array(
				'post_type'      => 'rds_product',
				'posts_per_page' => 20,
				'orderby'        => array( 'menu_order' => 'ASC' ),
				'no_found_rows'  => true,
				'tax_query'      => array( array( 'taxonomy' => 'rds_product_cat', 'terms' => $term->term_id ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
		$img_id = (int) get_term_meta( $term->term_id, 'rds_image', true );
		if ( ! $img_id && $products ) {
			$img_id = (int) get_post_thumbnail_id( $products[0] );
		}
		$img = $img_id ? wp_get_attachment_image(
			$img_id,
			'xl' === $size ? 'large' : 'rds-card',
			false,
			array(
				'loading' => 'lazy',
				'alt'     => '',
				'sizes'   => 'xl' === $size ? '(max-width: 620px) 100vw, (max-width: 1000px) 100vw, 640px' : '(max-width: 620px) 100vw, (max-width: 1000px) 50vw, 320px',
			)
		) : '';

		$tagline = trim( (string) get_term_meta( $term->term_id, 'rds_tagline', true ) );
		$tags    = '';
		foreach ( $products as $p ) {
			$code = get_post_meta( $p->ID, 'rds_code', true );
			if ( $code ) {
				$tags .= '<span>' . esc_html( $code ) . '</span>';
			}
		}

		$out .= sprintf(
			'<a class="rds-tile%1$s" href="%2$s">%3$s<span class="rds-tile__go">%4$s</span>%5$s<h3>%6$s</h3>%7$s%8$s</a>',
			$size ? ' rds-tile--' . $size : '',
			esc_url( get_term_link( $term ) ),
			$img,
			rdsco_icon( 'arrow' ),
			$tagline ? '<span class="rds-tile__k">' . esc_html( $term->name ) . '</span>' : '',
			esc_html( $tagline ? $tagline : $term->name ),
			$term->description ? '<p>' . esc_html( $term->description ) . '</p>' : '',
			$tags ? '<span class="rds-tile__tags">' . $tags . '</span>' : ''
		);
	}
	return $out . '</div>';
}

/**
 * Industries as a list of links with a preview panel (image, summary, related product codes).
 * Hover or focus on a link swaps the panel (assets/js/main.js); without JS the first panel shows.
 *
 * @param WP_Post[] $solutions Ordered solutions.
 * @param string    $featured  Slug of the solution shown first, or "first".
 */
function rdsco_industry_preview( array $solutions, string $featured ): string {
	$active = 0;
	foreach ( $solutions as $i => $s ) {
		if ( $featured && $s->post_name === $featured ) {
			$active = $i;
		}
	}

	$list   = '';
	$panels = '';
	foreach ( $solutions as $i => $s ) {
		$on       = $i === $active;
		$badge    = trim( (string) get_post_meta( $s->ID, 'rds_badge', true ) );
		$mark     = str_starts_with( $badge, 'icon:' ) ? rdsco_icon( sanitize_key( substr( $badge, 5 ) ) ) : esc_html( $badge ? explode( ':', $badge )[0] : '' );
		$products = rdsco_listed_products( $s );

		$img_id = (int) get_post_thumbnail_id( $s );
		foreach ( $products as $p ) {
			if ( $img_id ) {
				break;
			}
			$img_id = (int) get_post_thumbnail_id( $p );
		}
		$img = $img_id ? wp_get_attachment_image(
			$img_id,
			'rds-card',
			false,
			array(
				'loading' => 'lazy',
				'alt'     => '',
				'sizes'   => '(max-width: 900px) 100vw, 560px',
			)
		) : '<span class="rds-card__ph">' . rdsco_icon( 'camera' ) . '</span>';

		$codes = array_values( array_filter( array_map( fn( $p ) => (string) get_post_meta( $p->ID, 'rds_code', true ), $products ) ) );
		$chips = '';
		foreach ( array_slice( $codes, 0, 6 ) as $code ) {
			$chips .= '<span>' . esc_html( $code ) . '</span>';
		}
		if ( count( $codes ) > 6 ) {
			$chips .= '<small>+' . ( count( $codes ) - 6 ) . '</small>';
		}

		$list .= sprintf(
			'<li><a class="rds-industries__item%1$s" href="%2$s" data-i="%3$d"><span class="rds-industries__mark">%4$s</span><span>%5$s</span>%6$s</a></li>',
			$on ? ' is-on' : '',
			esc_url( get_permalink( $s ) ),
			$i,
			$mark,
			esc_html( get_the_title( $s ) ),
			rdsco_icon( 'arrow' )
		);

		$panels .= sprintf(
			'<article class="rds-industries__card" data-i="%1$d"%2$s><figure>%3$s</figure><div class="rds-industries__body"><h3>%4$s</h3><p>%5$s</p>%6$s<a class="rds-link-arrow" href="%7$s">%8$s %9$s</a></div></article>',
			$i,
			$on ? '' : ' hidden',
			$img,
			esc_html( get_the_title( $s ) ),
			esc_html( get_the_excerpt( $s ) ),
			$chips ? '<div class="rds-industries__chips">' . $chips . '</div>' : '',
			esc_url( get_permalink( $s ) ),
			esc_html__( 'Explore the solution', 'rdsco' ),
			rdsco_icon( 'arrow' )
		);
	}

	return '<div class="rds-industries" data-rds-industries><ul class="rds-industries__list">' . $list . '</ul><div class="rds-industries__panel">' . $panels . '</div></div>';
}
