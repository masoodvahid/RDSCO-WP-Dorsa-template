<?php
/**
 * Small helpers used across templates.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inline SVG icon from the sprite printed in the footer.
 */
function rdsco_icon( string $name, string $class = '' ): string {
	return sprintf(
		'<svg class="rds-svg %2$s" aria-hidden="true" focusable="false"><use href="#i-%1$s"></use></svg>',
		esc_attr( $name ),
		esc_attr( $class )
	);
}

/**
 * Theme mod with optional Polylang/WPML string translation.
 */
function rdsco_mod( string $key, string $default = '' ): string {
	$value = (string) get_theme_mod( 'rdsco_' . $key, $default );
	if ( '' !== $value && function_exists( 'pll__' ) ) {
		$value = pll__( $value );
	}
	return (string) apply_filters( 'wpml_translate_single_string', $value, 'Theme RDSCo', 'rdsco_' . $key );
}

/**
 * Phone number reduced to a dialable tel: value.
 */
function rdsco_tel( string $phone ): string {
	return preg_replace( '/[^0-9+]/', '', $phone );
}

/**
 * Industry badge: "Fe:26" renders a periodic-table tile, "icon:mountain" an icon tile.
 */
function rdsco_badge( string $badge ): string {
	$badge = trim( $badge );
	if ( '' === $badge ) {
		return '<span class="rds-ico">' . rdsco_icon( 'chip' ) . '</span>';
	}
	if ( str_starts_with( $badge, 'icon:' ) ) {
		return '<span class="rds-ico">' . rdsco_icon( sanitize_key( substr( $badge, 5 ) ) ) . '</span>';
	}
	$parts = explode( ':', $badge );
	return sprintf(
		'<span class="rds-el"><small>%2$s</small><b>%1$s</b></span>',
		esc_html( $parts[0] ),
		esc_html( $parts[1] ?? '' )
	);
}

/**
 * Breadcrumb trail for the page hero.
 */
function rdsco_breadcrumbs(): void {
	$items = array( array( home_url( '/' ), __( 'Home', 'rdsco' ) ) );

	if ( is_singular( 'rds_product' ) ) {
		$items[] = array( get_post_type_archive_link( 'rds_product' ), __( 'Products', 'rdsco' ) );
		$terms   = get_the_terms( get_the_ID(), 'rds_product_cat' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$items[] = array( get_term_link( $terms[0] ), $terms[0]->name );
		}
		$items[] = array( '', get_the_title() );
	} elseif ( is_singular( 'rds_solution' ) ) {
		$items[] = array( get_post_type_archive_link( 'rds_solution' ), __( 'Solutions', 'rdsco' ) );
		$items[] = array( '', get_the_title() );
	} elseif ( is_tax( 'rds_product_cat' ) ) {
		$items[] = array( get_post_type_archive_link( 'rds_product' ), __( 'Products', 'rdsco' ) );
		$items[] = array( '', single_term_title( '', false ) );
	} elseif ( is_post_type_archive() ) {
		$items[] = array( '', post_type_archive_title( '', false ) );
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $ancestor ) {
			$items[] = array( get_permalink( $ancestor ), get_the_title( $ancestor ) );
		}
		$items[] = array( '', get_the_title() );
	} elseif ( is_singular() ) {
		$items[] = array( '', get_the_title() );
	} elseif ( is_search() ) {
		$items[] = array( '', __( 'Search', 'rdsco' ) );
	} elseif ( is_archive() ) {
		$items[] = array( '', get_the_archive_title() );
	}

	echo '<ol class="rds-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'rdsco' ) . '">';
	foreach ( $items as $item ) {
		echo '<li>';
		if ( $item[0] ) {
			echo '<a href="' . esc_url( $item[0] ) . '">' . esc_html( $item[1] ) . '</a>';
		} else {
			echo '<span aria-current="page">' . esc_html( $item[1] ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ol>';
}

/**
 * URL of the page that holds the contact form (falls back to the "contact" slug).
 */
function rdsco_contact_url(): string {
	$custom = rdsco_mod( 'header_cta_url' );
	if ( $custom ) {
		return $custom;
	}
	$page = get_page_by_path( 'contact' );
	return $page ? get_permalink( $page ) : home_url( '/' );
}

/**
 * Product card markup used by shortcodes and related products.
 */
function rdsco_product_card( WP_Post $product ): string {
	$code  = get_post_meta( $product->ID, 'rds_code', true );
	$thumb = get_the_post_thumbnail(
		$product,
		'rds-card',
		array(
			'loading' => 'lazy',
			'alt'     => '',
			'sizes'   => '(max-width: 600px) 100vw, (max-width: 1024px) 50vw, 400px',
		)
	);
	ob_start();
	?>
	<article class="rds-card rds-card--media">
		<div class="rds-card__img"><?php echo $thumb ? $thumb : '<span class="rds-card__ph">' . rdsco_icon( 'camera' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<div class="rds-card__body">
			<?php if ( $code ) : ?>
				<span class="rds-card__code"><?php echo esc_html( $code ); ?></span>
			<?php endif; ?>
			<h3><a href="<?php echo esc_url( get_permalink( $product ) ); ?>"><?php echo esc_html( get_the_title( $product ) ); ?></a></h3>
			<p><?php echo esc_html( get_the_excerpt( $product ) ); ?></p>
		</div>
	</article>
	<?php
	return (string) ob_get_clean();
}
