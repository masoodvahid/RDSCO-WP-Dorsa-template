<?php
/**
 * Contact form: [rds_contact_form] posts to admin-post.php, stores a Message and emails it.
 *
 * Protection: nonce, honeypot field, minimum fill time, 5 submissions per IP per hour.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

function rdsco_form_options(): array {
	return array(
		'industry' => array( 'Steel', 'Copper', 'Aluminium', 'Mining', 'Packaging', 'Food', 'Petrochemical', 'Paper', 'Other' ),
		'topic'    => array(
			__( 'New system / proposal', 'rdsco' ),
			__( 'Support for an installed system', 'rdsco' ),
			__( 'Training', 'rdsco' ),
			__( 'Partnership', 'rdsco' ),
			__( 'Other', 'rdsco' ),
		),
	);
}

add_shortcode(
	'rds_contact_form',
	function () {
		$status = isset( $_GET['rds_form'] ) ? sanitize_key( wp_unslash( $_GET['rds_form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$opts   = rdsco_form_options();
		ob_start();
		?>
		<form class="rds-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="rds-contact-form">
			<?php if ( 'sent' === $status ) : ?>
				<p class="rds-note rds-note--ok rds-form__msg" role="status"><?php esc_html_e( 'Thank you. Your message has been received and our team will reply by email.', 'rdsco' ); ?></p>
			<?php elseif ( 'error' === $status ) : ?>
				<p class="rds-note rds-form__msg" role="alert"><?php esc_html_e( 'Your message could not be sent. Please check the required fields and try again, or email us directly.', 'rdsco' ); ?></p>
			<?php elseif ( 'limit' === $status ) : ?>
				<p class="rds-note rds-form__msg" role="alert"><?php esc_html_e( 'Too many messages were sent from your connection. Please try again later or email us directly.', 'rdsco' ); ?></p>
			<?php endif; ?>
			<input type="hidden" name="action" value="rdsco_contact">
			<input type="hidden" name="rds_ts" value="<?php echo esc_attr( (string) time() ); ?>">
			<input type="hidden" name="rds_back" value="<?php echo esc_url( get_permalink() ); ?>">
			<?php wp_nonce_field( 'rdsco_contact', 'rds_nonce' ); ?>
			<div class="rds-hp" aria-hidden="true"><label for="cf-website">Website</label><input id="cf-website" name="website" tabindex="-1" autocomplete="off"></div>
			<div class="rds-field"><label for="cf-name"><?php esc_html_e( 'Full name', 'rdsco' ); ?></label><input id="cf-name" name="name" autocomplete="name" required maxlength="120"></div>
			<div class="rds-field"><label for="cf-company"><?php esc_html_e( 'Company', 'rdsco' ); ?></label><input id="cf-company" name="company" autocomplete="organization" required maxlength="160"></div>
			<div class="rds-field"><label for="cf-email"><?php esc_html_e( 'Email', 'rdsco' ); ?></label><input id="cf-email" name="email" type="email" autocomplete="email" required maxlength="160"></div>
			<div class="rds-field"><label for="cf-phone"><?php esc_html_e( 'Phone', 'rdsco' ); ?></label><input id="cf-phone" name="phone" type="tel" autocomplete="tel" maxlength="40"></div>
			<div class="rds-field"><label for="cf-industry"><?php esc_html_e( 'Industry', 'rdsco' ); ?></label>
				<select id="cf-industry" name="industry">
					<?php foreach ( $opts['industry'] as $o ) : ?>
						<option><?php echo esc_html( $o ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="rds-field"><label for="cf-topic"><?php esc_html_e( 'Topic', 'rdsco' ); ?></label>
				<select id="cf-topic" name="topic">
					<?php foreach ( $opts['topic'] as $o ) : ?>
						<option><?php echo esc_html( $o ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="rds-field rds-field--full"><label for="cf-msg"><?php esc_html_e( 'Message', 'rdsco' ); ?></label><textarea id="cf-msg" name="message" required maxlength="5000" placeholder="<?php esc_attr_e( 'Line type, product width, line speed, the problem you want to solve…', 'rdsco' ); ?>"></textarea></div>
			<div class="rds-form__foot">
				<small><?php esc_html_e( 'All fields except phone are required.', 'rdsco' ); ?></small>
				<button class="rds-btn rds-btn--primary" type="submit"><?php esc_html_e( 'Send message', 'rdsco' ); ?> <?php echo rdsco_icon( 'arrow', 'rds-flip' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
		</form>
		<?php
		return trim( (string) ob_get_clean() );
	}
);

add_action( 'admin_post_nopriv_rdsco_contact', 'rdsco_handle_contact' );
add_action( 'admin_post_rdsco_contact', 'rdsco_handle_contact' );

function rdsco_handle_contact(): void {
	$back = isset( $_POST['rds_back'] ) ? esc_url_raw( wp_unslash( $_POST['rds_back'] ) ) : home_url( '/' );
	$back = wp_validate_redirect( $back, home_url( '/' ) );
	$done = function ( string $status ) use ( $back ): void {
		wp_safe_redirect( add_query_arg( 'rds_form', $status, $back ) . '#rds-contact-form' );
	};

	if ( ! isset( $_POST['rds_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rds_nonce'] ) ), 'rdsco_contact' ) ) {
		$done( 'error' );
	}

	// Bots: honeypot filled or submitted faster than a human could type.
	$ts = isset( $_POST['rds_ts'] ) ? absint( $_POST['rds_ts'] ) : 0;
	if ( ! empty( $_POST['website'] ) || ( time() - $ts ) < 3 ) {
		$done( 'sent' );
	}

	$ip_key = 'rdsco_cf_' . md5( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 5 ) {
		$done( 'limit' );
	}

	$f = array();
	foreach ( array( 'name', 'company', 'email', 'phone', 'industry', 'topic' ) as $key ) {
		$f[ $key ] = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}
	$f['email']   = sanitize_email( $f['email'] );
	$f['message'] = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	if ( '' === $f['name'] || '' === $f['company'] || ! is_email( $f['email'] ) || '' === $f['message'] ) {
		$done( 'error' );
	}

	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );

	$lines = array(
		__( 'Name', 'rdsco' ) . ': ' . $f['name'],
		__( 'Company', 'rdsco' ) . ': ' . $f['company'],
		__( 'Email', 'rdsco' ) . ': ' . $f['email'],
		__( 'Phone', 'rdsco' ) . ': ' . $f['phone'],
		__( 'Industry', 'rdsco' ) . ': ' . $f['industry'],
		__( 'Topic', 'rdsco' ) . ': ' . $f['topic'],
		__( 'Page', 'rdsco' ) . ': ' . $back,
		'',
		$f['message'],
	);
	$body = implode( "\n", $lines );

	wp_insert_post(
		array(
			'post_type'    => 'rds_message',
			'post_status'  => 'private',
			'post_title'   => sprintf( '%s — %s (%s)', $f['name'], $f['company'], $f['topic'] ),
			'post_content' => $body,
		)
	);

	$to = rdsco_mod( 'form_email' ) ? rdsco_mod( 'form_email' ) : get_option( 'admin_email' );
	wp_mail(
		$to,
		sprintf( '[%s] %s: %s', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $f['topic'], $f['company'] ),
		$body,
		array( 'Reply-To: ' . $f['name'] . ' <' . $f['email'] . '>' )
	);

	$done( 'sent' );
}
