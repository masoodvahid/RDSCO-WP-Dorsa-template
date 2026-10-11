<?php
/**
 * RDSCo Fresh Template — bootstrap.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

define( 'RDSCO_VERSION', '1.1.1' );
define( 'RDSCO_DIR', get_template_directory() );
define( 'RDSCO_URI', get_template_directory_uri() );

require RDSCO_DIR . '/inc/helpers.php';
require RDSCO_DIR . '/inc/setup.php';
require RDSCO_DIR . '/inc/enqueue.php';
require RDSCO_DIR . '/inc/post-types.php';
require RDSCO_DIR . '/inc/meta.php';
require RDSCO_DIR . '/inc/customizer.php';
require RDSCO_DIR . '/inc/menus.php';
require RDSCO_DIR . '/inc/shortcodes.php';
require RDSCO_DIR . '/inc/contact-form.php';
require RDSCO_DIR . '/inc/updater.php';
require RDSCO_DIR . '/inc/slab-inspection.php';

// One-time project seeding (only active while the /setup folder exists).
if ( is_dir( RDSCO_DIR . '/setup' ) ) {
	require RDSCO_DIR . '/inc/seed.php';
}
