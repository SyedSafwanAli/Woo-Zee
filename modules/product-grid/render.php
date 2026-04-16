<?php
/**
 * [wzp_product_grid] — Render function and shortcode entry point.
 *
 * Shortcode: [wzp_product_grid grid_id="abc123"]
 *   or inline: [wzp_product_grid category="rings,anklets" columns="4" count="8" orderby="popularity"]
 *
 * Priority (highest → lowest):
 *   1. Inline shortcode attributes
 *   2. Saved grid config (if grid_id is provided)
 *   3. Hard-coded fallbacks
 *
 * Supported orderby values:
 *   date        → newest first
 *   popularity  → total_sales meta (desc)
 *   rating      → _wc_average_rating meta (desc)
 *   price       → _price meta (asc)
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/card.php';

if ( ! function_exists( 'wzp_render_product_grid' ) ) :

/**
 * Build and return the HTML string for the product grid.
 *
 * @param array $atts Raw (already text-sanitised) shortcode attributes.
 * @return string     Escaped HTML; empty string on WC unavailability.
 */
function wzp_render_product_grid( $atts ) {

	// ── Step 1: Resolve base defaults ────────────────────────────────────────
	$base_defaults = array(
		'grid_id'  => '',
		'category' => '',   // comma-separated slugs (also accepts single slug)
		'columns'  => '3',
		'count'    => '8',
		'orderby'  => 'date',
	);

	// shortcode_atts: inline $atts win over $base_defaults.
	$atts = shortcode_atts( $base_defaults, $atts, 'wzp_product_grid' );

	// ── Step 2: If grid_id is set, load saved grid config ────────────────────
	$grid_id = sanitize_key( $atts['grid_id'] );

	if ( $grid_id ) {
		$saved_grids = (array) get_option( 'wzp_saved_grids', array() );
		foreach ( $saved_grids as $grid ) {
			if ( isset( $grid['id'] ) && $grid['id'] === $grid_id ) {
				// Only apply saved value when the inline attr was not explicitly set
				// (shortcode_atts already defaulted empty strings for unset attrs).
				// We load all saved fields and let caller-set inline attrs take priority.
				if ( empty( $atts['category'] ) && ! empty( $grid['categories'] ) ) {
					$atts['category'] = is_array( $grid['categories'] )
						? implode( ',', $grid['categories'] )
						: (string) $grid['categories'];
				}
				if ( '3' === $atts['columns'] && ! empty( $grid['columns'] ) ) {
					$atts['columns'] = $grid['columns'];
				}
				if ( '8' === $atts['count'] && ! empty( $grid['count'] ) ) {
					$atts['count'] = $grid['count'];
				}
				if ( 'date' === $atts['orderby'] && ! empty( $grid['orderby'] ) ) {
					$atts['orderby'] = $grid['orderby'];
				}
				break;
			}
		}
	}

	// ── Sanitise individual values ────────────────────────────────────────────
	// Category: supports comma-separated slugs for multi-category queries.
	$category_raw = sanitize_text_field( $atts['category'] );
	$cat_slugs    = array_values( array_filter(
		array_map( 'sanitize_key', explode( ',', $category_raw ) )
	) );

	$columns = absint( $atts['columns'] );
	$count   = min( 24, max( 1, absint( $atts['count'] ) ) );
	$orderby = sanitize_key( $atts['orderby'] );

	if ( ! in_array( $columns, array( 2, 3, 4, 5 ), true ) ) {
		$columns = 3;
	}

	// ── Step 3: Build WP_Query args ───────────────────────────────────────────
	$orderby_map = array(
		'date'       => array( 'orderby' => 'date',           'order' => 'DESC' ),
		'popularity' => array( 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => 'total_sales' ),
		'rating'     => array( 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => '_wc_average_rating' ),
		'price'      => array( 'orderby' => 'meta_value_num', 'order' => 'ASC',  'meta_key' => '_price' ),
	);

	$order_args = isset( $orderby_map[ $orderby ] ) ? $orderby_map[ $orderby ] : $orderby_map['date'];

	$query_args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'orderby'             => $order_args['orderby'],
		'order'               => $order_args['order'],
		'ignore_sticky_posts' => true,
	);

	if ( ! empty( $order_args['meta_key'] ) ) {
		$query_args['meta_key'] = $order_args['meta_key'];
	}

	// Multi-category tax_query.
	if ( ! empty( $cat_slugs ) ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $cat_slugs,
				'operator' => 'IN',
			),
		);
	}

	$query = new WP_Query( $query_args );

	// ── Step 4: Render ────────────────────────────────────────────────────────
	ob_start();

	echo '<div class="wzp-module" data-wzp-module="product-grid">';
	echo '<div class="wzp-product-grid wzp-product-grid--cols-' . esc_attr( $columns ) . '">';

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			echo wzp_render_product_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		wp_reset_postdata();
	} else {
		echo '<p class="wzp-no-products">';
		esc_html_e( 'No products found.', 'woo-zee-plugin' );
		echo '</p>';
	}

	echo '</div>';
	echo '</div>';

	return ob_get_clean();
}

endif; // function_exists

// ── Entry point ───────────────────────────────────────────────────────────────
echo wzp_render_product_grid( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
