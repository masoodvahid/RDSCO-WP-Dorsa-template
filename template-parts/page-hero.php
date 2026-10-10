<?php
/**
 * Inner-page hero: breadcrumbs, eyebrow, title, lead, actions, optional image.
 *
 * @package RDSCo
 *
 * @var array $args {
 *     @type string $title
 *     @type string $lead
 *     @type string $eyebrow
 *     @type int    $image_id
 *     @type array  $actions  List of [ label, url, variant ('light'|'ghost-light'), icon ].
 * }
 */

defined( 'ABSPATH' ) || exit;

$rdsco_args = wp_parse_args(
	$args ?? array(),
	array(
		'title'    => '',
		'lead'     => '',
		'eyebrow'  => '',
		'image_id' => 0,
		'actions'  => array(),
	)
);
?>
<section class="rds-page-hero<?php echo $rdsco_args['image_id'] ? '' : ' rds-page-hero--plain'; ?>">
	<?php
	if ( $rdsco_args['image_id'] ) {
		echo wp_get_attachment_image(
			(int) $rdsco_args['image_id'],
			'rds-hero',
			false,
			array(
				'class'         => 'rds-page-hero__media',
				'alt'           => '',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
				'sizes'         => '(max-width: 860px) 100vw, 52vw',
			)
		);
	}
	?>
	<div class="rds-page-hero__inner">
		<?php rdsco_breadcrumbs(); ?>
		<?php if ( $rdsco_args['eyebrow'] ) : ?>
			<p class="rds-eyebrow rds-eyebrow--light"><?php echo esc_html( $rdsco_args['eyebrow'] ); ?></p>
		<?php endif; ?>
		<h1><?php echo esc_html( $rdsco_args['title'] ); ?></h1>
		<?php if ( $rdsco_args['lead'] ) : ?>
			<p class="rds-lead"><?php echo esc_html( $rdsco_args['lead'] ); ?></p>
		<?php endif; ?>
		<?php if ( $rdsco_args['actions'] ) : ?>
			<div class="rds-actions">
				<?php foreach ( $rdsco_args['actions'] as $rdsco_action ) : ?>
					<a class="rds-btn rds-btn--<?php echo esc_attr( $rdsco_action[2] ?? 'light' ); ?>" href="<?php echo esc_url( $rdsco_action[1] ); ?>"<?php echo str_ends_with( (string) $rdsco_action[1], '.pdf' ) ? ' target="_blank" rel="noopener"' : ''; ?>>
						<?php echo ! empty( $rdsco_action[3] ) ? rdsco_icon( $rdsco_action[3] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo esc_html( $rdsco_action[0] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
