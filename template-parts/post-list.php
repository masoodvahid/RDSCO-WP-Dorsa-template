<?php
/**
 * Loop of posts as cards (blog, archives, search).
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

if ( have_posts() ) :
	?>
	<div class="rds-cards rds-cards--3">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'rds-card rds-card--media' ); ?>>
				<div class="rds-card__img">
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'rds-card', array( 'loading' => 'lazy', 'alt' => '', 'sizes' => '(max-width: 600px) 100vw, 400px' ) );
					} else {
						echo '<span class="rds-card__ph">' . rdsco_icon( 'paper' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</div>
				<div class="rds-card__body">
					<span class="rds-card__code"><?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ); ?></span>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<p><?php echo esc_html( get_the_excerpt() ); ?></p>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
	<div class="rds-pagination">
		<?php
		the_posts_pagination(
			array(
				'prev_text' => __( 'Previous', 'rdsco' ),
				'next_text' => __( 'Next', 'rdsco' ),
			)
		);
		?>
	</div>
	<?php
else :
	?>
	<p class="rds-lead"><?php esc_html_e( 'Nothing found.', 'rdsco' ); ?></p>
	<?php
endif;
