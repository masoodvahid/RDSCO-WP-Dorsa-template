<?php
/**
 * Closing call-to-action band and site footer.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

$rdsco_show_cta = (bool) get_theme_mod( 'rdsco_cta_show', '1' ) && rdsco_mod( 'cta_title' );
// Hide the band on the page that already holds the contact form.
if ( is_singular() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'rds_contact_form' ) ) {
	$rdsco_show_cta = false;
}
$rdsco_logo_id = (int) get_theme_mod( 'custom_logo' );
$rdsco_phone   = rdsco_mod( 'phone' );
$rdsco_email   = rdsco_mod( 'email' );
$rdsco_address = rdsco_mod( 'address' );
?>

<?php if ( $rdsco_show_cta ) : ?>
<section class="rds-cta">
	<div class="rds-container rds-cta__inner">
		<div>
			<h2><?php echo esc_html( rdsco_mod( 'cta_title' ) ); ?></h2>
			<?php if ( rdsco_mod( 'cta_text' ) ) : ?>
				<p><?php echo esc_html( rdsco_mod( 'cta_text' ) ); ?></p>
			<?php endif; ?>
		</div>
		<a class="rds-btn rds-btn--light" href="<?php echo esc_url( rdsco_contact_url() ); ?>"><?php echo esc_html( rdsco_mod( 'cta_label', __( 'Contact us', 'rdsco' ) ) ); ?> <?php echo rdsco_icon( 'arrow', 'rds-flip' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
	</div>
</section>
<?php endif; ?>

<footer class="rds-footer">
	<div class="rds-container rds-footer__top">
		<div class="rds-footer__brand">
			<?php
			if ( $rdsco_logo_id ) {
				echo wp_get_attachment_image(
					$rdsco_logo_id,
					'full',
					false,
					array(
						'class'   => 'rds-footer__logo',
						'alt'     => get_bloginfo( 'name' ),
						'loading' => 'lazy',
						'sizes'   => '140px',
					)
				);
			} else {
				echo '<span class="rds-brand__text">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
			}
			?>
			<?php if ( rdsco_mod( 'footer_text' ) ) : ?>
				<p><?php echo esc_html( rdsco_mod( 'footer_text' ) ); ?></p>
			<?php endif; ?>
		</div>

		<?php
		rdsco_render_footer_menu( 'footer_1' );
		rdsco_render_footer_menu( 'footer_2' );
		rdsco_render_footer_menu( 'footer_3' );
		?>

		<?php if ( $rdsco_phone || $rdsco_email || $rdsco_address ) : ?>
		<div class="rds-footer__contact">
			<h2><?php esc_html_e( 'Contact', 'rdsco' ); ?></h2>
			<address>
				<?php if ( $rdsco_address ) : ?>
					<span><?php echo nl2br( esc_html( $rdsco_address ) ); ?></span>
				<?php endif; ?>
				<?php if ( $rdsco_phone ) : ?>
					<a href="<?php echo esc_url( 'tel:' . rdsco_tel( $rdsco_phone ), array( 'tel' ) ); ?>" dir="ltr"><?php echo esc_html( $rdsco_phone ); ?></a>
				<?php endif; ?>
				<?php if ( $rdsco_email ) : ?>
					<a href="<?php echo esc_url( 'mailto:' . $rdsco_email, array( 'mailto' ) ); ?>"><?php echo esc_html( $rdsco_email ); ?></a>
				<?php endif; ?>
			</address>
		</div>
		<?php endif; ?>
	</div>

	<div class="rds-footer__bottom">
		<div class="rds-container">
			<span><?php echo esc_html( str_replace( '{year}', wp_date( 'Y' ), rdsco_mod( 'copyright', '© {year} ' . get_bloginfo( 'name' ) ) ) ); ?></span>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'legal',
					'container'      => false,
					'depth'          => 1,
					'fallback_cb'    => false,
				)
			);
			?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
