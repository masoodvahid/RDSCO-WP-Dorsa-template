<?php
/**
 * Not found.
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
			'title'   => __( 'Page not found', 'rdsco' ),
			'lead'    => __( 'The page may have moved. Try the search, or start from the home page.', 'rdsco' ),
			'actions' => array( array( __( 'Go to the home page', 'rdsco' ), home_url( '/' ), 'light' ) ),
		)
	);
	?>
</main>
<?php
get_footer();
