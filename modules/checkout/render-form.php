<?php
/**
 * [wzp_checkout_form] — Full WC checkout form, order review hidden via CSS.
 * Uses native [woocommerce_checkout] so all payment/shipping hooks fire correctly.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'WC' ) ) { return; }

// ── Move email + phone to top ─────────────────────────────────────────
add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
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
}, 5 );

// ── Remove order notes field ──────────────────────────────────────────
add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );

// ── Inject section headings before each billing/payment section ───────
add_action( 'woocommerce_checkout_before_customer_details', function () {
	$login_url = function_exists( 'wc_get_page_permalink' )
		? wc_get_page_permalink( 'myaccount' )
		: wp_login_url();
	echo '<div class="wzp-ckout-section-head">'
		. '<span class="wzp-ckout-section-head__title" data-step="1">' . esc_html__( 'Contact', 'woo-zee-plugin' ) . '</span>';
	if ( ! is_user_logged_in() ) {
		echo '<a href="' . esc_url( $login_url ) . '" class="wzp-ckout-section-head__link">'
			. esc_html__( 'Sign in', 'woo-zee-plugin' ) . '</a>';
	}
	echo '</div>';
}, 5 );

add_action( 'woocommerce_checkout_billing', function () {
	echo '<div class="wzp-ckout-section-head wzp-ckout-section-head--delivery">'
		. '<span class="wzp-ckout-section-head__title" data-step="2">' . esc_html__( 'Delivery', 'woo-zee-plugin' ) . '</span>'
		. '</div>';
}, 5 );

add_action( 'woocommerce_checkout_before_order_review', function () {
	echo '<div class="wzp-ckout-section-head wzp-ckout-section-head--payment">'
		. '<span class="wzp-ckout-section-head__title" data-step="3">' . esc_html__( 'Payment', 'woo-zee-plugin' ) . '</span>'
		. '<span class="wzp-ckout-section-head__note">' . esc_html__( 'Secure &amp; encrypted', 'woo-zee-plugin' ) . '</span>'
		. '</div>';
} );

// Also hook into woocommerce_review_order_before_payment as a fallback
add_action( 'woocommerce_review_order_before_payment', function () {
	// Payment heading already injected above — no duplicate needed
} );

$output = do_shortcode( '[woocommerce_checkout]' );

// Cleanup
remove_filter( 'woocommerce_enable_order_notes_field', '__return_false' );
remove_all_actions( 'woocommerce_checkout_before_customer_details', 5 );

?>
<div class="wzp-checkout-form-wrap">
	<?php echo $output; // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
