<?php
/**
 * Theme updates from GitHub Releases, shown in Dashboard → Updates like any other theme.
 *
 * How it works:
 * - style.css declares "Update URI: https://github.com/<owner>/<repo>", so WordPress.org never
 *   offers an update for this theme and WordPress asks the "update_themes_github.com" filter instead.
 * - The filter reads the latest GitHub release. A release asset named RDSCo_fresh_template.zip
 *   (built by .github/workflows/release.yml) is preferred; the source zipball is the fallback.
 * - Optional: define( 'RDSCO_GITHUB_TOKEN', '...' ) in wp-config.php for a private repository
 *   or to avoid GitHub API rate limits.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

const RDSCO_GH_REPO      = 'masoodvahid/RDSCO-WP-Dorsa-template';
const RDSCO_GH_ASSET     = 'RDSCo_fresh_template.zip';
const RDSCO_GH_TRANSIENT = 'rdsco_github_release';

/**
 * Latest release as [ version, package, url ], cached for six hours.
 */
function rdsco_github_release(): ?array {
	$cached = get_site_transient( RDSCO_GH_TRANSIENT );
	if ( is_array( $cached ) ) {
		return $cached ? $cached : null;
	}

	$response = wp_remote_get(
		'https://api.github.com/repos/' . RDSCO_GH_REPO . '/releases/latest',
		array(
			'timeout' => 10,
			'headers' => rdsco_github_headers( 'application/vnd.github+json' ),
		)
	);

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		set_site_transient( RDSCO_GH_TRANSIENT, array(), HOUR_IN_SECONDS ); // Back off for an hour.
		return null;
	}

	$release = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $release['tag_name'] ) ) {
		set_site_transient( RDSCO_GH_TRANSIENT, array(), HOUR_IN_SECONDS );
		return null;
	}

	$package = (string) ( $release['zipball_url'] ?? '' );
	foreach ( (array) ( $release['assets'] ?? array() ) as $asset ) {
		if ( RDSCO_GH_ASSET === ( $asset['name'] ?? '' ) ) {
			$package = (string) $asset['browser_download_url'];
			break;
		}
	}

	$data = array(
		'version' => ltrim( (string) $release['tag_name'], 'vV' ),
		'package' => $package,
		'url'     => (string) ( $release['html_url'] ?? 'https://github.com/' . RDSCO_GH_REPO ),
	);
	set_site_transient( RDSCO_GH_TRANSIENT, $data, 6 * HOUR_IN_SECONDS );
	return $data;
}

function rdsco_github_headers( string $accept ): array {
	$headers = array(
		'Accept'     => $accept,
		'User-Agent' => 'RDSCo-Theme-Updater/' . RDSCO_VERSION,
	);
	if ( defined( 'RDSCO_GITHUB_TOKEN' ) && RDSCO_GITHUB_TOKEN ) {
		$headers['Authorization'] = 'Bearer ' . RDSCO_GITHUB_TOKEN;
	}
	return $headers;
}

// Offer the update to WordPress (WP 6.1+).
add_filter(
	'update_themes_github.com',
	function ( $update, array $theme_data, string $stylesheet ) {
		if ( get_template() !== $stylesheet && wp_get_theme()->get_template() !== $stylesheet ) {
			return $update;
		}
		$release = rdsco_github_release();
		if ( ! $release || ! $release['package'] ) {
			return $update;
		}
		return array(
			'theme'        => $stylesheet,
			'version'      => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'requires'     => '6.5',
			'requires_php' => '8.1',
		);
	},
	10,
	3
);

// "Check again" on Dashboard → Updates also refreshes the GitHub cache.
add_action(
	'load-update-core.php',
	function () {
		if ( isset( $_GET['force-check'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			delete_site_transient( RDSCO_GH_TRANSIENT );
		}
	}
);

// Authenticate downloads from GitHub when a token is configured.
add_filter(
	'http_request_args',
	function ( array $args, string $url ) {
		if ( defined( 'RDSCO_GITHUB_TOKEN' ) && RDSCO_GITHUB_TOKEN && preg_match( '#^https://(api\.)?github\.com/#', $url ) ) {
			$args['headers']                  = isset( $args['headers'] ) ? (array) $args['headers'] : array();
			$args['headers']['Authorization'] = 'Bearer ' . RDSCO_GITHUB_TOKEN;
		}
		return $args;
	},
	10,
	2
);

// A source zipball unpacks to "owner-repo-sha/": rename it to the installed theme folder.
add_filter(
	'upgrader_source_selection',
	function ( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;
		$theme = get_template();
		if ( is_wp_error( $source ) || empty( $hook_extra['theme'] ) || $hook_extra['theme'] !== $theme ) {
			return $source;
		}
		if ( basename( untrailingslashit( $source ) ) === $theme ) {
			return $source;
		}
		$target = trailingslashit( $remote_source ) . $theme . '/';
		if ( $wp_filesystem && $wp_filesystem->move( $source, $target, true ) ) {
			return $target;
		}
		return new WP_Error( 'rdsco_rename_failed', __( 'Could not rename the downloaded theme folder.', 'rdsco' ) );
	},
	10,
	4
);
