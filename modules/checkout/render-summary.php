<?php
/**
 * [wzp_checkout_summary] — Order summary panel (right column).
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'WC' ) ) { return; }

$cart = WC()->cart;
if ( ! $cart ) { return; }

$cart_items = $cart->get_cart();
if ( empty( $cart_items ) ) { return; }

// Total item count
$total_qty = 0;
foreach ( $cart_items as $item ) {
	$total_qty += absint( $item['quantity'] );
}

// Shipping cost
$shipping_html = '';
$packages      = WC()->shipping()->get_packages();
$chosen        = WC()->session ? WC()->session->get( 'chosen_shipping_methods' ) : array();
foreach ( $packages as $i => $pkg ) {
	if ( ! empty( $chosen[ $i ] ) && isset( $pkg['rates'][ $chosen[ $i ] ] ) ) {
		$rate          = $pkg['rates'][ $chosen[ $i ] ];
		$cost          = (float) $rate->get_cost();
		$shipping_html = $cost > 0 ? wc_price( $cost ) : '<span class="wzp-summary-free">Free</span>';
		break;
	}
}
if ( ! $shipping_html ) {
	$shipping_html = '<span class="wzp-summary-muted">Enter shipping address</span>';
}

?>
<div class="wzp-summary">

	<!-- Products -->
	<div class="wzp-summary__items">
		<?php foreach ( $cart_items as $item ) :
			$product = $item['data'];
			if ( ! $product instanceof WC_Product ) { continue; }

			$qty       = absint( $item['quantity'] );
			$name      = $product->get_name();
			$line_price = wc_price( (float) $product->get_price() * $qty );
			$thumb_url  = get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' );
			if ( ! $thumb_url ) {
				$thumb_url = wc_placeholder_img_src( 'thumbnail' );
			}

			// Variation attributes
			$variation_label = '';
			if ( ! empty( $item['variation'] ) ) {
				$parts = array();
				foreach ( $item['variation'] as $attr => $val ) {
					if ( $val ) { $parts[] = $val; }
				}
				$variation_label = implode( ', ', $parts );
			}
		?>
		<div class="wzp-summary__item">
			<div class="wzp-summary__img-wrap">
				<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" class="wzp-summary__thumb">
				<span class="wzp-summary__badge"><?php echo esc_html( $qty ); ?></span>
			</div>
			<div class="wzp-summary__info">
				<span class="wzp-summary__name"><?php echo esc_html( $name ); ?></span>
				<?php if ( $variation_label ) : ?>
				<span class="wzp-summary__variant"><?php echo esc_html( $variation_label ); ?></span>
				<?php endif; ?>
			</div>
			<div class="wzp-summary__price"><?php echo $line_price; // phpcs:ignore ?></div>
		</div>
		<?php endforeach; ?>
	</div>

	<!-- Totals -->
	<div class="wzp-summary__totals">
		<div class="wzp-summary__row">
			<span class="wzp-summary__row-label">
				<?php echo esc_html__( 'Subtotal', 'woo-zee-plugin' ); ?>
				<span class="wzp-summary__item-count"> &middot; <?php echo esc_html( $total_qty ); ?> <?php echo $total_qty === 1 ? esc_html__( 'item', 'woo-zee-plugin' ) : esc_html__( 'items', 'woo-zee-plugin' ); ?></span>
			</span>
			<span class="wzp-summary__row-value"><?php echo WC()->cart->get_cart_subtotal(); // phpcs:ignore ?></span>
		</div>
		<div class="wzp-summary__row">
			<span class="wzp-summary__row-label"><?php esc_html_e( 'Shipping', 'woo-zee-plugin' ); ?></span>
			<span class="wzp-summary__row-value"><?php echo $shipping_html; // phpcs:ignore ?></span>
		</div>
		<div class="wzp-summary__row wzp-summary__row--total">
			<span class="wzp-summary__row-label"><?php esc_html_e( 'Total', 'woo-zee-plugin' ); ?></span>
			<span class="wzp-summary__row-value">
				<span class="wzp-summary__currency">USD</span><?php echo WC()->cart->get_total(); // phpcs:ignore ?>
			</span>
		</div>
	</div>

</div>
