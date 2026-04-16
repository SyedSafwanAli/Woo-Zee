<?php
/**
 * [wzp_order_received] — Custom order confirmation / thank you page.
 *
 * Usage: Replace page content with [wzp_order_received]
 * WC will pass ?key= and order-received URL param automatically.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;
if ( ! function_exists( 'WC' ) ) { return; }

// ── Get order from URL ────────────────────────────────────────────────
$order_id  = absint( get_query_var( 'order-received' ) );
$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

if ( ! $order_id ) { return; }

$order = wc_get_order( $order_id );

if ( ! $order || ! $order->key_is_valid( $order_key ) ) {
	echo '<p>' . esc_html__( 'Invalid order.', 'woo-zee-plugin' ) . '</p>';
	return;
}

// ── Trigger WC thank you action ───────────────────────────────────────
do_action( 'woocommerce_thankyou', $order_id );

$billing  = $order->get_address( 'billing' );
$shipping = $order->get_address( 'shipping' );
$items    = $order->get_items();

$date_str = $order->get_date_created()
	? $order->get_date_created()->date_i18n( 'F j, Y' )
	: '—';

?>
<div class="wzp-thankyou">

	<!-- ── Header ─────────────────────────────────────────────────── -->
	<div class="wzp-thankyou__header">
		<div class="wzp-thankyou__check">
			<svg viewBox="0 0 52 52" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
				<circle cx="26" cy="26" r="25" fill="#1a1a1a" stroke="none"/>
				<polyline points="14,27 22,35 38,19"/>
			</svg>
		</div>
		<h1 class="wzp-thankyou__title"><?php esc_html_e( 'Thank you for your order!', 'woo-zee-plugin' ); ?></h1>
		<p class="wzp-thankyou__subtitle">
			<?php esc_html_e( 'Your order has been received and is now being processed.', 'woo-zee-plugin' ); ?>
		</p>
	</div>

	<!-- ── Order meta strip ───────────────────────────────────────── -->
	<div class="wzp-thankyou__meta">
		<div class="wzp-thankyou__meta-item">
			<span class="wzp-thankyou__meta-label"><?php esc_html_e( 'Order number', 'woo-zee-plugin' ); ?></span>
			<span class="wzp-thankyou__meta-value">#<?php echo esc_html( $order->get_order_number() ); ?></span>
		</div>
		<div class="wzp-thankyou__meta-item">
			<span class="wzp-thankyou__meta-label"><?php esc_html_e( 'Date', 'woo-zee-plugin' ); ?></span>
			<span class="wzp-thankyou__meta-value"><?php echo esc_html( $date_str ); ?></span>
		</div>
		<div class="wzp-thankyou__meta-item">
			<span class="wzp-thankyou__meta-label"><?php esc_html_e( 'Email', 'woo-zee-plugin' ); ?></span>
			<span class="wzp-thankyou__meta-value"><?php echo esc_html( $billing['email'] ); ?></span>
		</div>
		<div class="wzp-thankyou__meta-item">
			<span class="wzp-thankyou__meta-label"><?php esc_html_e( 'Total', 'woo-zee-plugin' ); ?></span>
			<span class="wzp-thankyou__meta-value"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
		</div>
		<div class="wzp-thankyou__meta-item">
			<span class="wzp-thankyou__meta-label"><?php esc_html_e( 'Payment method', 'woo-zee-plugin' ); ?></span>
			<span class="wzp-thankyou__meta-value"><?php echo esc_html( $order->get_payment_method_title() ); ?></span>
		</div>
	</div>

	<!-- ── Main two-column layout ─────────────────────────────────── -->
	<div class="wzp-thankyou__body">

		<!-- Left: order details -->
		<div class="wzp-thankyou__left">

			<!-- Order items table -->
			<div class="wzp-thankyou__section">
				<h2 class="wzp-thankyou__section-title"><?php esc_html_e( 'Order details', 'woo-zee-plugin' ); ?></h2>
				<div class="wzp-thankyou__table-wrap">
					<table class="wzp-thankyou__table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Product', 'woo-zee-plugin' ); ?></th>
								<th><?php esc_html_e( 'Total', 'woo-zee-plugin' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $items as $item ) :
							$product     = $item->get_product();
							$product_url = $product ? get_permalink( $product->get_id() ) : '';
							$thumb_url   = $product ? get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ) : wc_placeholder_img_src();
						?>
							<tr>
								<td class="wzp-thankyou__product-cell">
									<?php if ( $thumb_url ) : ?>
									<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $item->get_name() ); ?>" class="wzp-thankyou__product-thumb">
									<?php endif; ?>
									<div class="wzp-thankyou__product-info">
										<?php if ( $product_url ) : ?>
										<a href="<?php echo esc_url( $product_url ); ?>" class="wzp-thankyou__product-name"><?php echo esc_html( $item->get_name() ); ?></a>
										<?php else : ?>
										<span class="wzp-thankyou__product-name"><?php echo esc_html( $item->get_name() ); ?></span>
										<?php endif; ?>
										<span class="wzp-thankyou__product-qty">× <?php echo esc_html( $item->get_quantity() ); ?></span>
									</div>
								</td>
								<td class="wzp-thankyou__product-total"><?php echo wp_kses_post( $order->get_formatted_line_subtotal( $item ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
						<tfoot>
							<tr>
								<th><?php esc_html_e( 'Subtotal:', 'woo-zee-plugin' ); ?></th>
								<td><?php echo wp_kses_post( wc_price( $order->get_subtotal() ) ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Shipping:', 'woo-zee-plugin' ); ?></th>
								<td><?php echo wp_kses_post( $order->get_shipping_to_display() ); ?></td>
							</tr>
							<?php if ( $order->get_discount_total() ) : ?>
							<tr>
								<th><?php esc_html_e( 'Discount:', 'woo-zee-plugin' ); ?></th>
								<td>−<?php echo wp_kses_post( wc_price( $order->get_discount_total() ) ); ?></td>
							</tr>
							<?php endif; ?>
							<tr class="wzp-thankyou__total-row">
								<th><?php esc_html_e( 'Total:', 'woo-zee-plugin' ); ?></th>
								<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Payment method:', 'woo-zee-plugin' ); ?></th>
								<td><?php echo esc_html( $order->get_payment_method_title() ); ?></td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>

		</div>

		<!-- Right: addresses -->
		<div class="wzp-thankyou__right">

			<!-- Billing address -->
			<div class="wzp-thankyou__section">
				<h2 class="wzp-thankyou__section-title"><?php esc_html_e( 'Billing address', 'woo-zee-plugin' ); ?></h2>
				<address class="wzp-thankyou__address">
					<?php echo wp_kses_post( $order->get_formatted_billing_address( esc_html__( 'N/A', 'woo-zee-plugin' ) ) ); ?>
					<?php if ( ! empty( $billing['phone'] ) ) : ?>
					<span class="wzp-thankyou__address-phone">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.92a16 16 0 0 0 6.16 6.16l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.92 16.92z"/></svg>
						<?php echo esc_html( $billing['phone'] ); ?>
					</span>
					<?php endif; ?>
					<?php if ( ! empty( $billing['email'] ) ) : ?>
					<span class="wzp-thankyou__address-email">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
						<?php echo esc_html( $billing['email'] ); ?>
					</span>
					<?php endif; ?>
				</address>
			</div>

			<!-- Shipping address -->
			<?php
			$ship_addr = $order->get_formatted_shipping_address();
			if ( $ship_addr ) :
			?>
			<div class="wzp-thankyou__section">
				<h2 class="wzp-thankyou__section-title"><?php esc_html_e( 'Shipping address', 'woo-zee-plugin' ); ?></h2>
				<address class="wzp-thankyou__address">
					<?php echo wp_kses_post( $ship_addr ); ?>
				</address>
			</div>
			<?php endif; ?>

			<!-- CTA -->
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wzp-thankyou__cta">
				<?php esc_html_e( 'Continue Shopping', 'woo-zee-plugin' ); ?>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</a>

		</div>

	</div>

</div>
