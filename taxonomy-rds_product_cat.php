<?php
/**
 * Product family archive.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

get_header();

$rdsco_term = get_queried_object();
?>
<main id="main">
	<?php
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'title'    => single_term_title( '', false ),
			'lead'     => $rdsco_term->description,
			'eyebrow'  => __( 'Product family', 'rdsco' ),
			'image_id' => (int) get_term_meta( $rdsco_term->term_id, 'rds_image', true ),
		)
	);
	?>
	<section class="rds-section">
		<div class="rds-container">
			<?php
			if ( have_posts() ) {
				echo '<div class="rds-cards rds-cards--3">';
				while ( have_posts() ) {
					the_post();
					echo rdsco_product_card( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				echo '</div>';
			} else {
				echo '<p>' . esc_html__( 'No products in this family yet.', 'rdsco' ) . '</p>';
			}
			?>
		</div>
	</section>
</main>
<?php
get_footer();
