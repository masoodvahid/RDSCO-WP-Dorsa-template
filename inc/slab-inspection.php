<?php
/**
 * [rds_slab_inspection] — looping slab inspection animation (canvas, no libraries).
 *
 * Attributes (all optional):
 *   eyebrow, title, text   Small heading block above the animation.
 *   note                   Caption under the animation.
 *
 * The script loads only on pages that use the shortcode. Canvas labels are passed
 * through __() so a translation plugin can translate them.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		$file = RDSCO_DIR . '/assets/js/slab-inspection.js';
		wp_register_script(
			'rdsco-slab-inspection',
			RDSCO_URI . '/assets/js/slab-inspection.js',
			array(),
			(string) filemtime( $file ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
);

add_shortcode(
	'rds_slab_inspection',
	function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'eyebrow' => '',
				'title'   => '',
				'text'    => '',
				'note'    => __( 'Illustrative simulation. Slab IDs, defects and values are generated for demonstration.', 'rdsco' ),
			),
			$atts,
			'rds_slab_inspection'
		);

		wp_enqueue_script( 'rdsco-slab-inspection' );

		$i18n = array(
			'quality'    => __( 'SURFACE QUALITY', 'rdsco' ),
			'scanning'   => __( 'SCANNING', 'rdsco' ),
			'grade'      => __( 'GRADE', 'rdsco' ),
			'severity'   => __( 'Defect severity', 'rdsco' ),
			'reject'     => __( 'Reject limit', 'rdsco' ),
			'position'   => __( 'Position from slab head', 'rdsco' ),
			'scanned'    => __( 'SCANNED', 'rdsco' ),
			'defects'    => __( 'DEFECTS', 'rdsco' ),
			'qi'         => __( 'QUALITY INDEX', 'rdsco' ),
			'last'       => __( 'LAST SLABS', 'rdsco' ),
			'defect1'    => __( 'defect', 'rdsco' ),
			'defectN'    => __( 'defects', 'rdsco' ),
			'inspecting' => __( 'INSPECTING', 'rdsco' ),
			'pause'      => __( 'Pause animation', 'rdsco' ),
			'play'       => __( 'Play animation', 'rdsco' ),
			'lcrack'     => __( 'Longitudinal crack', 'rdsco' ),
			'tcrack'     => __( 'Transverse crack', 'rdsco' ),
			'corner'     => __( 'Corner crack', 'rdsco' ),
			'scale'      => __( 'Scale patch', 'rdsco' ),
			'slag'       => __( 'Slag inclusion', 'rdsco' ),
			'pin'        => __( 'Pinholes', 'rdsco' ),
		);

		$heading_id = wp_unique_id( 'rds-slab-' );
		ob_start();
		?>
		<div class="rds-slab" data-rds-slab data-i18n="<?php echo esc_attr( wp_json_encode( $i18n ) ); ?>">
			<?php if ( $atts['eyebrow'] || $atts['title'] || $atts['text'] ) : ?>
				<div class="rds-slab__head">
					<?php if ( $atts['eyebrow'] ) : ?>
						<p class="rds-eyebrow rds-eyebrow--light"><?php echo esc_html( $atts['eyebrow'] ); ?></p>
					<?php endif; ?>
					<?php if ( $atts['title'] ) : ?>
						<h3 id="<?php echo esc_attr( $heading_id ); ?>"><?php echo esc_html( $atts['title'] ); ?></h3>
					<?php endif; ?>
					<?php if ( $atts['text'] ) : ?>
						<p class="rds-lead"><?php echo esc_html( $atts['text'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<div class="rds-slab__stage">
				<canvas role="img" aria-label="<?php esc_attr_e( 'Animation: hot steel slabs move along a roller table under a DORSA inspection gantry. As each slab is scanned, its surface defects are outlined and a live quality chart is drawn, ending with a grade for the slab.', 'rdsco' ); ?>"></canvas>
			</div>
			<div class="rds-slab__controls">
				<?php if ( $atts['note'] ) : ?>
					<p class="rds-slab__note"><?php echo esc_html( $atts['note'] ); ?></p>
				<?php endif; ?>
				<button class="rds-slab__btn" type="button" aria-pressed="false"><?php esc_html_e( 'Pause animation', 'rdsco' ); ?></button>
			</div>
		</div>
		<?php
		return trim( preg_replace( '/>\s+</', '><', (string) ob_get_clean() ) );
	}
);
