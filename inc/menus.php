<?php
/**
 * Header navigation renderer (desktop mega menu + mobile drawer) built from a WP menu.
 *
 * Menu conventions (Appearance → Menus, enable "CSS Classes" in Screen Options):
 * - Top-level item with class "mega" opens a full-width panel; its children become columns.
 * - Column children are the links. Add class "mega-wide" to a column to span two tracks,
 *   "mega-steps" to number its links, "mega-feature" on a column item to show the linked
 *   post as an image card.
 * - Any other top-level item with children opens a small dropdown.
 *
 * @package RDSCo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu items for a location as a tree (each item gets a ->kids array).
 *
 * @return array<int, WP_Post>
 */
function rdsco_menu_tree( string $location ): array {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return array();
	}
	$items = wp_get_nav_menu_items( $locations[ $location ], array( 'update_post_term_cache' => false ) );
	if ( ! $items ) {
		return array();
	}
	$items = array_filter( $items, fn( $i ) => ! ( 'post_type' === $i->type && 'publish' !== get_post_status( (int) $i->object_id ) ) );
	$by_id = array();
	foreach ( $items as $item ) {
		$item->kids               = array();
		$by_id[ (int) $item->ID ] = $item;
	}
	$tree = array();
	foreach ( $by_id as $item ) {
		$parent = (int) $item->menu_item_parent;
		if ( $parent && isset( $by_id[ $parent ] ) ) {
			$by_id[ $parent ]->kids[] = $item;
		} else {
			$tree[] = $item;
		}
	}
	return $tree;
}

function rdsco_item_has_class( WP_Post $item, string $class ): bool {
	return in_array( $class, (array) $item->classes, true );
}

function rdsco_item_is_link( WP_Post $item ): bool {
	return '' !== $item->url && '#' !== $item->url;
}

function rdsco_current_url(): string {
	global $wp;
	return trailingslashit( home_url( $wp->request ?? '' ) );
}

function rdsco_item_is_current( WP_Post $item ): bool {
	$url = strtok( (string) $item->url, '#' );
	if ( $url && trailingslashit( $url ) === rdsco_current_url() && ! str_contains( (string) $item->url, '#' ) ) {
		return true;
	}
	foreach ( $item->kids as $kid ) {
		if ( rdsco_item_is_current( $kid ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Small label under a link: product code or the item description.
 */
function rdsco_item_note( WP_Post $item ): string {
	if ( 'rds_product' === $item->object ) {
		return (string) get_post_meta( (int) $item->object_id, 'rds_code', true );
	}
	return (string) $item->description;
}

function rdsco_link_attrs( WP_Post $item ): string {
	$attrs = ' href="' . esc_url( $item->url ) . '"';
	if ( $item->target ) {
		$attrs .= ' target="' . esc_attr( $item->target ) . '" rel="noopener"';
	}
	return $attrs;
}

/**
 * Desktop navigation.
 */
function rdsco_render_primary_nav(): void {
	$tree = rdsco_menu_tree( 'primary' );
	if ( ! $tree ) {
		return;
	}
	echo '<nav class="rds-nav" aria-label="' . esc_attr__( 'Main', 'rdsco' ) . '"><ul class="rds-nav__list">';
	foreach ( $tree as $item ) {
		$current = rdsco_item_is_current( $item ) ? ' is-current' : '';
		$title   = esc_html( $item->title );
		if ( ! $item->kids ) {
			printf( '<li class="rds-nav__item"><a class="rds-nav__link%s"%s>%s</a></li>', esc_attr( $current ), rdsco_link_attrs( $item ), $title ); // phpcs:ignore WordPress.Security.EscapeOutput
			continue;
		}
		$panel_id = 'rds-sub-' . (int) $item->ID;
		$is_mega  = rdsco_item_has_class( $item, 'mega' );
		printf(
			'<li class="rds-nav__item%1$s"><button class="rds-nav__link%2$s" type="button" aria-expanded="false" aria-controls="%3$s">%4$s %5$s</button>',
			$is_mega ? '' : ' rds-nav__item--drop',
			esc_attr( $current ),
			esc_attr( $panel_id ),
			$title, // phpcs:ignore WordPress.Security.EscapeOutput
			rdsco_icon( 'chev' ) // phpcs:ignore WordPress.Security.EscapeOutput
		);
		if ( $is_mega ) {
			rdsco_render_mega( $item, $panel_id );
		} else {
			echo '<div class="rds-drop" id="' . esc_attr( $panel_id ) . '">';
			if ( rdsco_item_is_link( $item ) ) {
				echo '<a class="rds-drop__parent"' . rdsco_link_attrs( $item ) . '>' . $title . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			foreach ( $item->kids as $kid ) {
				echo '<a' . rdsco_link_attrs( $kid ) . '>' . esc_html( $kid->title ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</div>';
		}
		echo '</li>';
	}
	echo '</ul></nav>';
}

function rdsco_render_mega( WP_Post $item, string $panel_id ): void {
	$tracks = 0;
	foreach ( $item->kids as $col ) {
		$tracks += rdsco_item_has_class( $col, 'mega-wide' ) ? 2 : 1;
	}
	printf( '<div class="rds-mega" id="%s"><div class="rds-mega__grid" style="--rds-cols:%d">', esc_attr( $panel_id ), (int) $tracks );

	foreach ( $item->kids as $col ) {
		if ( rdsco_item_has_class( $col, 'mega-feature' ) ) {
			$post_id = 'post_type' === $col->type ? (int) $col->object_id : 0;
			$img     = $post_id ? get_the_post_thumbnail( $post_id, 'rds-card', array( 'loading' => 'lazy', 'alt' => '', 'sizes' => '320px' ) ) : '';
			$text    = $col->description ? $col->description : ( $post_id ? get_the_excerpt( $post_id ) : '' );
			printf(
				'<a class="rds-mega__feature"%1$s>%2$s<div><strong>%3$s</strong>%4$s</div></a>',
				rdsco_link_attrs( $col ), // phpcs:ignore WordPress.Security.EscapeOutput
				$img, // phpcs:ignore WordPress.Security.EscapeOutput
				esc_html( $col->title ),
				esc_html( wp_trim_words( $text, 18 ) )
			);
			continue;
		}

		$wide = rdsco_item_has_class( $col, 'mega-wide' );
		echo '<div class="rds-mega__col' . ( $wide ? ' rds-mega__col--wide' : '' ) . '">';
		echo '<p class="rds-mega__title">';
		echo rdsco_item_is_link( $col ) ? '<a' . rdsco_link_attrs( $col ) . '>' . esc_html( $col->title ) . '</a>' : esc_html( $col->title ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</p>';

		if ( rdsco_item_has_class( $col, 'mega-steps' ) ) {
			echo '<div class="rds-mega__steps">';
			foreach ( $col->kids as $link ) {
				echo '<a' . rdsco_link_attrs( $link ) . '>' . esc_html( $link->title ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</div>';
		} elseif ( $col->kids && 'rds_solution' === $col->kids[0]->object ) {
			echo '<div class="rds-mega__industries">';
			foreach ( $col->kids as $link ) {
				$badge = get_post_meta( (int) $link->object_id, 'rds_badge', true );
				echo '<a' . rdsco_link_attrs( $link ) . '>' . rdsco_badge( (string) $badge ) . esc_html( $link->title ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</div>';
		} else {
			echo '<ul class="rds-mega__list">';
			foreach ( $col->kids as $link ) {
				$note = rdsco_item_note( $link );
				echo '<li><a' . rdsco_link_attrs( $link ) . '>' . esc_html( $link->title ) . ( $note ? '<small>' . esc_html( $note ) . '</small>' : '' ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			echo '</ul>';
		}
		echo '</div>';
	}
	echo '</div></div>';
}

/**
 * Mobile drawer (same menu, accordion).
 */
function rdsco_render_drawer_nav(): void {
	foreach ( rdsco_menu_tree( 'primary' ) as $item ) {
		if ( ! $item->kids ) {
			echo '<a class="rds-drawer__link"' . rdsco_link_attrs( $item ) . '>' . esc_html( $item->title ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
			continue;
		}
		echo '<details><summary>' . esc_html( $item->title ) . ' ' . rdsco_icon( 'chev' ) . '</summary><ul>'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( rdsco_item_is_link( $item ) ) {
			echo '<li><a' . rdsco_link_attrs( $item ) . '>' . esc_html( sprintf( /* translators: %s: menu item */ __( 'All %s', 'rdsco' ), $item->title ) ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		foreach ( $item->kids as $kid ) {
			if ( rdsco_item_has_class( $kid, 'mega-feature' ) ) {
				continue;
			}
			if ( $kid->kids ) {
				echo '<li class="rds-drawer__group">' . esc_html( $kid->title ) . '</li>';
				foreach ( $kid->kids as $link ) {
					$note = rdsco_item_note( $link );
					echo '<li><a' . rdsco_link_attrs( $link ) . '>' . esc_html( $link->title ) . ( $note ? ' · ' . esc_html( $note ) : '' ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
			} else {
				echo '<li><a' . rdsco_link_attrs( $kid ) . '>' . esc_html( $kid->title ) . '</a></li>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
		echo '</ul></details>';
	}
}

/**
 * Simple footer column: heading from the menu name, then links.
 */
function rdsco_render_footer_menu( string $location ): void {
	$locations = get_nav_menu_locations();
	if ( empty( $locations[ $location ] ) ) {
		return;
	}
	$menu = wp_get_nav_menu_object( $locations[ $location ] );
	echo '<div class="rds-footer__col">';
	if ( $menu && 'legal' !== $location ) {
		echo '<h2>' . esc_html( $menu->name ) . '</h2>';
	}
	wp_nav_menu(
		array(
			'theme_location' => $location,
			'container'      => false,
			'depth'          => 1,
			'fallback_cb'    => false,
		)
	);
	echo '</div>';
}
