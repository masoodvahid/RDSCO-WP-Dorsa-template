<?php
/**
 * Default page: hero + readable text column.
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
				'image_id' => (int) get_post_thumbnail_id(),
			)
		);
		?>
		<div class="rds-section">
			<div class="rds-container rds-entry rds-prose">
				<?php the_content(); ?>
			</div>
		</div>
	</main>
	<?php
endwhile;

get_footer();
