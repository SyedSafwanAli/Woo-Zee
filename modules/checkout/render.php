<?php
/**
 * [wzp_checkout] — Custom checkout page layout.
 *
 * Wraps the native [woocommerce_checkout] shortcode with a Shopify-style
 * two-column layout: left (contact / delivery / payment) + right (order summary).
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) ) { return; }

// ── Flag so filters know we're inside our checkout render ─────────────
$GLOBALS['_wzp_checkout_rendering'] = true;

// ── 1. Reorder billing fields: email + phone first ────────────────────
$wzp_reorder_billing = function ( $fields ) {
	if ( ! isset( $fields['billing'] ) ) { return $fields; }

	$b     = $fields['billing'];
	$email = isset( $b['billing_email'] ) ? $b['billing_email'] : null;
	$phone = isset( $b['billing_phone'] ) ? $b['billing_phone'] : null;

	unset( $b['billing_email'], $b['billing_phone'] );

	$new = [];
	if ( $email ) { $new['billing_email'] = $email; }
	if ( $phone ) { $new['billing_phone'] = $phone; }

	$fields['billing'] = array_merge( $new, $b );
	return $fields;
};
add_filter( 'woocommerce_checkout_fields', $wzp_reorder_billing, 5 );

// ── 2. Inject thumbnail + qty badge into checkout order review table ──
$wzp_thumb_filter = function ( $name, $cart_item, $cart_item_key ) {
	if ( empty( $GLOBALS['_wzp_checkout_rendering'] ) ) { return $name; }

	$product   = $cart_item['data'];
	$qty       = absint( $cart_item['quantity'] );
	$thumbnail = $product->get_image( 'thumbnail', [ 'class' => 'wzp-ckout-thumb' ] );

	return '<span class="wzp-ckout-item-img">' .
				$thumbnail .
				'<span class="wzp-ckout-item-badge">' . $qty . '</span>' .
			'</span>' .
			'<span class="wzp-ckout-item-name">' . $name . '</span>';
};
add_filter( 'woocommerce_cart_item_name', $wzp_thumb_filter, 20, 3 );

// ── Render ─────────────────────────────────────────────────────────────
?>
<div class="wzp-checkout-page">
	<?php echo do_shortcode( '[woocommerce_checkout]' ); ?>
</div>
<?php

// ── Cleanup filters ────────────────────────────────────────────────────
remove_filter( 'woocommerce_checkout_fields', $wzp_reorder_billing, 5 );
remove_filter( 'woocommerce_cart_item_name', $wzp_thumb_filter, 20 );
unset( $GLOBALS['_wzp_checkout_rendering'] );
