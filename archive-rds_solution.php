<?php
/**
 * Solutions archive: industry grid.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main">
	<?php
	get_template_part(
		'template-parts/page-hero',
		null,
		array(
			'title' => post_type_archive_title( '', false ),
			'lead'  => __( 'Each industry page maps our systems to the stages of your process, from raw material to finished product.', 'rdsco' ),
		)
	);
	?>
	<section class="rds-section rds-section--dark">
		<div class="rds-container">
			<?php echo do_shortcode( '[rds_solutions featured="first"]' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>
</main>
<?php
get_footer();
