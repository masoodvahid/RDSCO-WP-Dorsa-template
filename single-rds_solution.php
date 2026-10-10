<?php
/**
 * Single solution (industry).
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="main">
		<?php
		get_template_part(
			'template-parts/page-hero',
			null,
			array(
				'title'    => get_the_title(),
				'lead'     => has_excerpt() ? get_the_excerpt() : '',
				'eyebrow'  => __( 'Solutions', 'rdsco' ),
				'image_id' => (int) get_post_thumbnail_id(),
				'actions'  => array( array( __( 'Discuss your line', 'rdsco' ), rdsco_contact_url(), 'light' ) ),
			)
		);
		?>
		<nav class="rds-subnav" aria-label="<?php esc_attr_e( 'On this page', 'rdsco' ); ?>" hidden><div class="rds-container"><ul></ul></div></nav>
		<div class="rds-entry rds-entry--sections">
			<?php the_content(); ?>
		</div>
	</main>
	<?php
endwhile;

get_footer();
