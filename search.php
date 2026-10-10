<?php
/**
 * Search results.
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
			/* translators: %s: search query */
			'title' => sprintf( __( 'Results for “%s”', 'rdsco' ), get_search_query() ),
		)
	);
	?>
	<section class="rds-section">
		<div class="rds-container rds-stack">
			<form class="rds-searchform" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="rds-sr" for="rds-search-page"><?php esc_html_e( 'Search the site', 'rdsco' ); ?></label>
				<input id="rds-search-page" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>">
				<button class="rds-btn rds-btn--primary" type="submit"><?php esc_html_e( 'Search', 'rdsco' ); ?></button>
			</form>
			<?php get_template_part( 'template-parts/post-list' ); ?>
		</div>
	</section>
</main>
<?php
get_footer();
