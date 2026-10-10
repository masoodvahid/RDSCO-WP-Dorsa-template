<?php
/**
 * Fallback template: blog index and generic archives.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( is_home() && ! is_front_page() ) {
	$rdsco_title = get_the_title( (int) get_option( 'page_for_posts' ) );
} elseif ( is_archive() ) {
	$rdsco_title = wp_strip_all_tags( get_the_archive_title() );
} else {
	$rdsco_title = get_bloginfo( 'name' );
}
?>
<main id="main">
	<?php get_template_part( 'template-parts/page-hero', null, array( 'title' => $rdsco_title, 'lead' => is_archive() ? wp_strip_all_tags( get_the_archive_description() ) : '' ) ); ?>
	<section class="rds-section">
		<div class="rds-container">
			<?php get_template_part( 'template-parts/post-list' ); ?>
		</div>
	</section>
</main>
<?php
get_footer();
