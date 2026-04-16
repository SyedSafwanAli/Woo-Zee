<?php
/**
 * [wzp_cart_suggestions] — "You May Also Like" product grid for the cart page.
 *
 * Strategy (priority order):
 *   1. Products from the same categories as cart items (related), excluding
 *      products already in the cart.
 *   2. If cart is empty or yields < 8 results, fills up with random products.
 *
 * Attributes:
 *   count   – total products to show  (default 8)
 *   columns – grid columns            (default 4)
 *   title   – section heading         (default "You May Also Like")
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) ) { return; }

// ── Load the shared card renderer ─────────────────────────────────────────────
require_once WZP_PATH . 'modules/product-grid/card.php';

// ── Resolve attributes ────────────────────────────────────────────────────────
$atts = shortcode_atts(
	array(
		'count'   => '8',
		'columns' => '4',
	),
	$atts,
	'wzp_cart_suggestions'
);

$count   = min( 16, max( 1, absint( $atts['count'] ) ) );
$columns = in_array( (int) $atts['columns'], array( 2, 3, 4, 5 ), true ) ? (int) $atts['columns'] : 4;

// ── Gather cart data ──────────────────────────────────────────────────────────
$cart        = WC()->cart;
$cart_items  = $cart ? $cart->get_cart() : array();
$in_cart_ids = array();
$cat_ids     = array();

foreach ( $cart_items as $item ) {
	$product_id      = absint( $item['product_id'] );
	$in_cart_ids[]   = $product_id;

	// Collect all category IDs from cart products
	$product = wc_get_product( $product_id );
	if ( $product instanceof WC_Product ) {
		$cat_ids = array_merge( $cat_ids, $product->get_category_ids() );
	}
}

$in_cart_ids = array_unique( $in_cart_ids );
$cat_ids     = array_unique( $cat_ids );

// ── Build query ───────────────────────────────────────────────────────────────
$base_args = array(
	'post_type'           => 'product',
	'post_status'         => 'publish',
	'posts_per_page'      => $count,
	'ignore_sticky_posts' => true,
	'post__not_in'        => $in_cart_ids,
	'fields'              => 'ids',
);

$product_ids = array();

// Step 1 — category-related products
if ( ! empty( $cat_ids ) ) {
	$related_args               = $base_args;
	$related_args['orderby']    = 'rand';
	$related_args['tax_query']  = array(
		array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $cat_ids,
			'operator' => 'IN',
		),
	);

	$related_query = new WP_Query( $related_args );
	$product_ids   = $related_query->posts;
	wp_reset_postdata();
}

// Step 2 — fill remaining slots with random products
if ( count( $product_ids ) < $count ) {
	$exclude                    = array_merge( $in_cart_ids, $product_ids );
	$filler_args                = $base_args;
	$filler_args['orderby']     = 'rand';
	$filler_args['posts_per_page'] = $count - count( $product_ids );
	$filler_args['post__not_in']   = $exclude;
	unset( $filler_args['fields'] );
	$filler_args['fields']      = 'ids';

	$filler_query = new WP_Query( $filler_args );
	$product_ids  = array_merge( $product_ids, $filler_query->posts );
	wp_reset_postdata();
}

if ( empty( $product_ids ) ) { return; }

// ── Render ────────────────────────────────────────────────────────────────────
?>
<section class="wzp-cart-suggestions wzp-module" data-wzp-module="cart-suggestions">


	<div class="wzp-product-grid wzp-product-grid--cols-<?php echo esc_attr( $columns ); ?>">
		<?php foreach ( $product_ids as $pid ) :
			echo wzp_render_product_card( absint( $pid ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		endforeach; ?>
	</div>

</section>
