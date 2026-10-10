<?php
/**
 * Single post.
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
				'eyebrow'  => get_the_date(),
				'lead'     => has_excerpt() ? get_the_excerpt() : '',
				'image_id' => (int) get_post_thumbnail_id(),
			)
		);
		?>
		<article class="rds-section">
			<div class="rds-container rds-entry rds-prose">
				<?php
				the_content();
				wp_link_pages();
				?>
			</div>
		</article>
	</main>
	<?php
endwhile;

get_footer();
