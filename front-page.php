<?php
/**
 * Front page: the page content is the layout (built from section blocks).
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

if ( 'posts' === get_option( 'show_on_front' ) ) {
	require __DIR__ . '/index.php';
	return;
}

get_header();
?>
<main id="main" class="rds-entry rds-entry--sections">
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</main>
<?php
get_footer();
