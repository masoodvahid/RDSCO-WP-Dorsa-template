<?php
/**
 * Products archive: grouped by product family.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

get_header();

$rdsco_terms = get_terms(
	array(
		'taxonomy'   => 'rds_product_cat',
		'hide_empty' => true,
		'parent'     => 0,
	)
);
if ( is_wp_error( $rdsco_terms ) ) {
	$rdsco_terms = array();
}
usort( $rdsco_terms, fn( $a, $b ) => (int) get_term_meta( $a->term_id, 'rds_order', true ) <=> (int) get_term_meta( $b->term_id, 'rds_order', true ) );
?>
<main id="main">
	<?php
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'title' => post_type_archive_title( '', false ),
			'lead'  => __( 'Inspection, measurement and grading systems built on machine vision and AI.', 'rdsco' ),
		)
	);
	?>
	<?php foreach ( $rdsco_terms as $rdsco_i => $rdsco_term ) : ?>
		<section class="rds-section<?php echo $rdsco_i % 2 ? ' rds-section--surface' : ''; ?>" id="<?php echo esc_attr( $rdsco_term->slug ); ?>">
			<div class="rds-container">
				<div class="rds-section-head rds-section-head--split">
					<div>
						<p class="rds-eyebrow"><?php esc_html_e( 'Product family', 'rdsco' ); ?></p>
						<h2><a class="rds-plain" href="<?php echo esc_url( get_term_link( $rdsco_term ) ); ?>"><?php echo esc_html( $rdsco_term->name ); ?></a></h2>
					</div>
					<?php if ( $rdsco_term->description ) : ?>
						<p class="rds-lead"><?php echo esc_html( $rdsco_term->description ); ?></p>
					<?php endif; ?>
				</div>
				<?php echo do_shortcode( '[rds_products columns="3" limit="24" category="' . esc_attr( $rdsco_term->slug ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</section>
	<?php endforeach; ?>
</main>
<?php
get_footer();
