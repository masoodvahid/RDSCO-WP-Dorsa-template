<?php
/**
 * Site header.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

$rdsco_logo_id   = (int) get_theme_mod( 'custom_logo' );
$rdsco_cta_label = rdsco_mod( 'header_cta_label' );
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="rds-skip" href="#main"><?php esc_html_e( 'Skip to content', 'rdsco' ); ?></a>

<header class="rds-header<?php echo rdsco_is_showcase_home() ? ' rds-header--overlay' : ''; ?>">
	<div class="rds-container rds-header__inner">
		<a class="rds-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php
			if ( $rdsco_logo_id ) {
				echo wp_get_attachment_image(
					$rdsco_logo_id,
					'full',
					false,
					array(
						'class'   => 'rds-brand__img',
						'alt'     => get_bloginfo( 'name' ),
						'loading' => 'eager',
						'sizes'   => '132px',
					)
				);
			} else {
				echo '<span class="rds-brand__text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
			}
			?>
		</a>

		<?php rdsco_render_primary_nav(); ?>

		<div class="rds-header__actions">
			<button class="rds-icon-btn" type="button" data-rds-search aria-expanded="false" aria-controls="rds-search" aria-label="<?php esc_attr_e( 'Search', 'rdsco' ); ?>"><?php echo rdsco_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<?php
			/**
			 * Language switcher slot for a translation plugin.
			 * Example (Polylang): add_action( 'rdsco_header_actions', fn() => pll_the_languages( array( 'dropdown' => 1 ) ) );
			 */
			do_action( 'rdsco_header_actions' );
			?>
			<?php if ( $rdsco_cta_label ) : ?>
				<a class="rds-btn rds-btn--primary rds-header__cta" href="<?php echo esc_url( rdsco_contact_url() ); ?>"><?php echo esc_html( $rdsco_cta_label ); ?></a>
			<?php endif; ?>
			<button class="rds-icon-btn rds-burger" type="button" aria-expanded="false" aria-controls="rds-drawer" aria-label="<?php esc_attr_e( 'Open menu', 'rdsco' ); ?>" data-label-open="<?php esc_attr_e( 'Open menu', 'rdsco' ); ?>" data-label-close="<?php esc_attr_e( 'Close menu', 'rdsco' ); ?>"><?php echo rdsco_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</div>
	</div>

	<div class="rds-search" id="rds-search" hidden>
		<div class="rds-container">
			<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="rds-sr" for="rds-search-input"><?php esc_html_e( 'Search the site', 'rdsco' ); ?></label>
				<input id="rds-search-input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search products, industries, documents…', 'rdsco' ); ?>">
				<button class="rds-btn rds-btn--primary" type="submit"><?php esc_html_e( 'Search', 'rdsco' ); ?></button>
			</form>
		</div>
	</div>

	<div class="rds-drawer" id="rds-drawer" hidden>
		<div class="rds-container rds-drawer__inner">
			<?php rdsco_render_drawer_nav(); ?>
			<?php if ( $rdsco_cta_label ) : ?>
				<a class="rds-btn rds-btn--primary" href="<?php echo esc_url( rdsco_contact_url() ); ?>"><?php echo esc_html( $rdsco_cta_label ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</header>
