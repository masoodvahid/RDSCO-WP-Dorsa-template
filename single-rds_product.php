<?php
/**
 * Single product.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$rdsco_terms    = get_the_terms( get_the_ID(), 'rds_product_cat' );
	$rdsco_term     = ( $rdsco_terms && ! is_wp_error( $rdsco_terms ) ) ? $rdsco_terms[0] : null;
	$rdsco_app      = (string) get_post_meta( get_the_ID(), 'rds_application', true );
	$rdsco_brochure = (int) get_post_meta( get_the_ID(), 'rds_brochure', true );
	$rdsco_actions  = array( array( __( 'Request a proposal', 'rdsco' ), rdsco_contact_url(), 'light' ) );
	if ( $rdsco_brochure && wp_get_attachment_url( $rdsco_brochure ) ) {
		$rdsco_actions[] = array( __( 'Datasheet (PDF)', 'rdsco' ), wp_get_attachment_url( $rdsco_brochure ), 'ghost-light', 'download' );
	}
	?>
	<main id="main">
		<?php
		get_template_part(
			'template-parts/page-hero',
			null,
			array(
				'title'    => get_the_title(),
				'lead'     => has_excerpt() ? get_the_excerpt() : '',
				'eyebrow'  => implode( ' · ', array_filter( array( $rdsco_term ? $rdsco_term->name : '', $rdsco_app ) ) ),
				'image_id' => (int) get_post_thumbnail_id(),
				'actions'  => $rdsco_actions,
			)
		);
		?>
		<nav class="rds-subnav" aria-label="<?php esc_attr_e( 'On this page', 'rdsco' ); ?>" hidden><div class="rds-container"><ul></ul></div></nav>
		<div class="rds-entry rds-entry--sections">
			<?php the_content(); ?>
		</div>

		<?php
		if ( $rdsco_term ) :
			$rdsco_related = do_shortcode( sprintf( '[rds_products category="%s" exclude="%d" limit="3" columns="3"]', $rdsco_term->slug, get_the_ID() ) );
			if ( $rdsco_related ) :
				?>
				<section class="rds-section rds-section--surface">
					<div class="rds-container">
						<div class="rds-section-head">
							<p class="rds-eyebrow"><?php esc_html_e( 'Related', 'rdsco' ); ?></p>
							<h2><?php echo esc_html( sprintf( /* translators: %s: product family */ __( 'More in %s', 'rdsco' ), $rdsco_term->name ) ); ?></h2>
						</div>
						<?php echo $rdsco_related; // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</section>
				<?php
			endif;
		endif;
		?>
	</main>
	<?php
endwhile;

get_footer();
