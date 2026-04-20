<?php
/**
 * [wzp_order_tracking] — Premium order tracking shortcode.
 *
 * Usage: [wzp_order_tracking]
 *
 * Features:
 *  - Full-width luxury hero banner
 *  - Two-column result layout (vertical stepper + order details)
 *  - Trust badges, FAQ accordion (SEO)
 *  - BreadcrumbList + FAQPage JSON-LD schemas
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) ) {
	return;
}

if ( ! function_exists( 'wzp_render_order_tracking' ) ) :

function wzp_render_order_tracking( $atts ) {

	// ── Enqueue module CSS ────────────────────────────────────────────────────
	wp_enqueue_style(
		'woo-zee-order-tracking',
		WZP_URL . 'modules/order-tracking/tracking.css',
		array( 'woo-zee-style' ),
		WZP_VERSION
	);

	// ── Status labels ─────────────────────────────────────────────────────────
	$status_labels = array(
		'pending'    => __( 'Payment Pending', 'woo-zee-plugin' ),
		'processing' => __( 'Processing',      'woo-zee-plugin' ),
		'on-hold'    => __( 'On Hold',          'woo-zee-plugin' ),
		'completed'  => __( 'Delivered',        'woo-zee-plugin' ),
		'cancelled'  => __( 'Cancelled',        'woo-zee-plugin' ),
		'refunded'   => __( 'Refunded',         'woo-zee-plugin' ),
		'failed'     => __( 'Payment Failed',   'woo-zee-plugin' ),
	);

	// ── Progress step index (1–4) for happy-path statuses ────────────────────
	$progress_steps = array(
		'pending'    => 1,
		'on-hold'    => 1,
		'processing' => 2,
		'completed'  => 4,
		'cancelled'  => 0,
		'refunded'   => 0,
		'failed'     => 0,
	);

	// ── Per-step descriptions keyed by step number ───────────────────────────
	$step_descs = array(
		1 => array(
			'default' => __( 'Your order has been received and payment confirmed.', 'woo-zee-plugin' ),
			'pending' => __( 'We received your order and are awaiting payment confirmation.', 'woo-zee-plugin' ),
			'on-hold' => __( 'Your order is on hold pending manual review or payment verification.', 'woo-zee-plugin' ),
		),
		2 => __( 'Our team is carefully preparing and packing your jewellery.', 'woo-zee-plugin' ),
		3 => __( 'Your order has been handed to the courier and is on its way.', 'woo-zee-plugin' ),
		4 => __( 'Your jewellery has been delivered. We hope you love it!', 'woo-zee-plugin' ),
	);

	// ── Step definitions ──────────────────────────────────────────────────────
	$steps = array(
		array(
			'label' => __( 'Order Placed', 'woo-zee-plugin' ),
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
		),
		array(
			'label' => __( 'Processing', 'woo-zee-plugin' ),
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>',
		),
		array(
			'label' => __( 'On the Way', 'woo-zee-plugin' ),
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
		),
		array(
			'label' => __( 'Delivered', 'woo-zee-plugin' ),
			'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
		),
	);

	// ── FAQ data (shared: HTML accordion + JSON-LD) ───────────────────────────
	$faqs = array(
		array(
			'q' => __( 'How do I track my order?', 'woo-zee-plugin' ),
			'a' => __( 'Enter your Order ID and the billing email address you used at checkout. Your Order ID can be found in your order confirmation email, prefixed with a # symbol (e.g. #1042).', 'woo-zee-plugin' ),
		),
		array(
			'q' => __( 'How long does delivery take?', 'woo-zee-plugin' ),
			'a' => __( 'Orders are typically processed within 1–2 business days and delivered within 3–7 business days depending on your location. Express shipping options are available at checkout.', 'woo-zee-plugin' ),
		),
		array(
			'q' => __( 'What does "Processing" mean?', 'woo-zee-plugin' ),
			'a' => __( 'Processing means we have confirmed your payment and our team is carefully preparing and packaging your jewellery. This stage usually takes 1–2 business days before dispatch.', 'woo-zee-plugin' ),
		),
		array(
			'q' => __( 'Can I change or cancel my order?', 'woo-zee-plugin' ),
			'a' => __( 'Orders can be modified or cancelled within 2 hours of being placed. Please contact our support team immediately with your Order ID. Once processing begins, changes may not be possible.', 'woo-zee-plugin' ),
		),
		array(
			'q' => __( 'What if I entered the wrong shipping address?', 'woo-zee-plugin' ),
			'a' => __( 'Contact us as soon as possible with your Order ID and the correct address. If the order has not yet been dispatched we can update it at no extra charge.', 'woo-zee-plugin' ),
		),
	);

	// ── GET params + order lookup ─────────────────────────────────────────────
	$order        = null;
	$error        = '';
	$raw_order_id = sanitize_text_field( $_GET['order_id'] ?? '' );
	$b_email      = sanitize_email( $_GET['billing_email'] ?? '' );
	$submitted    = $raw_order_id && $b_email;

	// Resolve numeric ID from plain number OR WC-XXXX-NNN format
	$order_id = 0;
	if ( $raw_order_id ) {
		if ( is_numeric( $raw_order_id ) ) {
			$order_id = absint( $raw_order_id );
		} elseif ( preg_match( '/(\d+)$/', $raw_order_id, $m ) ) {
			$order_id = absint( $m[1] );
		}
	}

	if ( $submitted ) {
		$order = $order_id ? wc_get_order( $order_id ) : null;
		if ( ! $order ) {
			$error = __( 'No order found with that ID. Please double-check and try again.', 'woo-zee-plugin' );
		} elseif ( strtolower( $order->get_billing_email() ) !== strtolower( $b_email ) ) {
			$error = __( 'The email address does not match our records for this order.', 'woo-zee-plugin' );
			$order = null;
		}
	}

	// ── JSON-LD: BreadcrumbList ───────────────────────────────────────────────
	echo '<script type="application/ld+json">' . wp_json_encode( array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => array(
			array( '@type' => 'ListItem', 'position' => 1, 'name' => __( 'Home', 'woo-zee-plugin' ), 'item' => home_url( '/' ) ),
			array( '@type' => 'ListItem', 'position' => 2, 'name' => __( 'Track Your Order', 'woo-zee-plugin' ), 'item' => get_permalink() ?: '' ),
		),
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";

	// ── JSON-LD: FAQPage ──────────────────────────────────────────────────────
	echo '<script type="application/ld+json">' . wp_json_encode( array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map( function ( $faq ) {
			return array(
				'@type'          => 'Question',
				'name'           => $faq['q'],
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $faq['a'] ),
			);
		}, $faqs ),
	), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";

	// ── Contact page link (graceful fallback) ─────────────────────────────────
	$contact_page = get_page_by_path( 'contact-us' ) ?: get_page_by_path( 'contact' );
	$contact_url  = $contact_page ? get_permalink( $contact_page->ID ) : '';

	// ── Render ────────────────────────────────────────────────────────────────
	ob_start();
	?>
	<div class="wzp-ot" data-wzp-module="order-tracking">

		<!-- ── BODY ──────────────────────────────────────────────────────────── -->
		<div class="wzp-ot__body">

			<?php if ( ! $order ) : ?>
			<!-- ─────────────────── FORM STATE ─────────────────────────────── -->
			<div class="wzp-ot__form-outer">

				<div class="wzp-ot__form-card">

					<div class="wzp-ot__form-header">
						<h2 class="wzp-ot__form-title"><?php esc_html_e( 'Track Your Order', 'woo-zee-plugin' ); ?></h2>
						<p class="wzp-ot__form-lead"><?php esc_html_e( 'Enter your Order ID and billing email to get real-time updates on your shipment.', 'woo-zee-plugin' ); ?></p>
					</div>

					<?php if ( $error ) : ?>
					<div class="wzp-ot__alert wzp-ot__alert--error" role="alert">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
						<span><?php echo esc_html( $error ); ?></span>
					</div>
					<?php endif; ?>

					<form class="wzp-ot__form" method="GET" action="<?php echo esc_url( get_permalink() ); ?>">

						<div class="wzp-ot__fields-row">

							<div class="wzp-ot__field">
								<label class="wzp-ot__label" for="wzp-ot-order-id">
									<?php esc_html_e( 'Order ID', 'woo-zee-plugin' ); ?>
								</label>
								<div class="wzp-ot__input-wrap">
									<span class="wzp-ot__input-icon" aria-hidden="true">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
									</span>
									<input type="text"
									       id="wzp-ot-order-id"
									       name="order_id"
									       class="wzp-ot__input"
									       placeholder="<?php esc_attr_e( 'e.g. 378 or WC-5E8A-378', 'woo-zee-plugin' ); ?>"
									       value="<?php echo $raw_order_id ? esc_attr( $raw_order_id ) : ''; ?>"
									       required
									       autocomplete="off">
								</div>
								<span class="wzp-ot__hint"><?php esc_html_e( 'Plain number (378) or full ID (WC-5E8A-378)', 'woo-zee-plugin' ); ?></span>
							</div>

							<div class="wzp-ot__field">
								<label class="wzp-ot__label" for="wzp-ot-email">
									<?php esc_html_e( 'Billing Email', 'woo-zee-plugin' ); ?>
								</label>
								<div class="wzp-ot__input-wrap">
									<span class="wzp-ot__input-icon" aria-hidden="true">
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
									</span>
									<input type="email"
									       id="wzp-ot-email"
									       name="billing_email"
									       class="wzp-ot__input"
									       placeholder="<?php esc_attr_e( 'you@email.com', 'woo-zee-plugin' ); ?>"
									       value="<?php echo $b_email ? esc_attr( $b_email ) : ''; ?>"
									       required
									       autocomplete="email">
								</div>
								<span class="wzp-ot__hint"><?php esc_html_e( 'Email used when placing the order', 'woo-zee-plugin' ); ?></span>
							</div>

						</div>

						<button type="submit" class="wzp-ot__btn">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
							<?php esc_html_e( 'Track My Order', 'woo-zee-plugin' ); ?>
						</button>

					</form>
				</div><!-- /.wzp-ot__form-card -->

				<!-- Trust strip -->
				<div class="wzp-ot__trust" aria-label="<?php esc_attr_e( 'Why shop with us', 'woo-zee-plugin' ); ?>">
					<div class="wzp-ot__trust-item">
						<span class="wzp-ot__trust-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
						</span>
						<div class="wzp-ot__trust-text">
							<strong><?php esc_html_e( 'Secure Lookup', 'woo-zee-plugin' ); ?></strong>
							<span><?php esc_html_e( 'Your data is encrypted', 'woo-zee-plugin' ); ?></span>
						</div>
					</div>
					<div class="wzp-ot__trust-sep" aria-hidden="true"></div>
					<div class="wzp-ot__trust-item">
						<span class="wzp-ot__trust-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
						</span>
						<div class="wzp-ot__trust-text">
							<strong><?php esc_html_e( '24/7 Support', 'woo-zee-plugin' ); ?></strong>
							<span><?php esc_html_e( 'Always here to help', 'woo-zee-plugin' ); ?></span>
						</div>
					</div>
					<div class="wzp-ot__trust-sep" aria-hidden="true"></div>
					<div class="wzp-ot__trust-item">
						<span class="wzp-ot__trust-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
						</span>
						<div class="wzp-ot__trust-text">
							<strong><?php esc_html_e( 'Easy Returns', 'woo-zee-plugin' ); ?></strong>
							<span><?php esc_html_e( 'Hassle-free policy', 'woo-zee-plugin' ); ?></span>
						</div>
					</div>
				</div><!-- /.wzp-ot__trust -->

			</div><!-- /.wzp-ot__form-outer -->

			<?php else :

			// ── Order found: build variables ──────────────────────────────────
			$status       = $order->get_status();
			$status_label = $status_labels[ $status ] ?? ucfirst( $status );
			$step         = $progress_steps[ $status ] ?? 0;
			$is_terminal  = in_array( $status, array( 'cancelled', 'refunded', 'failed' ), true );
			?>

			<!-- ─────────────────── RESULT STATE ───────────────────────────── -->
			<div class="wzp-ot__result-grid">

				<!-- ── LEFT PANEL ────────────────────────────────────────────── -->
				<aside class="wzp-ot__result-left">
					<div class="wzp-ot__left-card">

						<!-- Order number + date -->
						<div class="wzp-ot__order-header">
							<div class="wzp-ot__order-icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
							</div>
							<div class="wzp-ot__order-meta">
								<span class="wzp-ot__order-num">
									<?php printf( esc_html__( 'Order #%s', 'woo-zee-plugin' ), esc_html( $order->get_id() ) ); ?>
								</span>
								<span class="wzp-ot__order-date">
									<?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
								</span>
							</div>
						</div>

						<!-- Status badge -->
						<div class="wzp-ot__status-row">
							<span class="wzp-ot__status-badge wzp-ot__status-badge--<?php echo esc_attr( $status ); ?>">
								<span class="wzp-ot__status-dot" aria-hidden="true"></span>
								<?php echo esc_html( $status_label ); ?>
							</span>
						</div>

						<?php if ( ! $is_terminal ) : ?>
						<!-- Vertical stepper -->
						<div class="wzp-ot__stepper-v" aria-label="<?php esc_attr_e( 'Order progress', 'woo-zee-plugin' ); ?>">
							<?php foreach ( $steps as $i => $s ) :
								$s_num  = $i + 1;
								$done   = $step >= $s_num;
								$active = $step === $s_num;

								$cls = 'wzp-ot__sv-step';
								if ( $done )   { $cls .= ' wzp-ot__sv-step--done'; }
								if ( $active ) { $cls .= ' wzp-ot__sv-step--active'; }

								if ( 1 === $s_num ) {
									$desc = $step_descs[1][ $status ] ?? $step_descs[1]['default'];
								} else {
									$desc = $step_descs[ $s_num ] ?? '';
								}
								$is_last = ( $i === count( $steps ) - 1 );
							?>
							<div class="<?php echo esc_attr( $cls ); ?>"
							     aria-current="<?php echo $active ? 'step' : 'false'; ?>">

								<div class="wzp-ot__sv-track">
									<div class="wzp-ot__sv-icon" aria-hidden="true">
										<?php if ( $done && ! $active ) : ?>
											<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
										<?php else : ?>
											<?php echo $s['icon']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
										<?php endif; ?>
									</div>
									<?php if ( ! $is_last ) : ?>
									<div class="wzp-ot__sv-line<?php echo ( $step > $s_num ) ? ' wzp-ot__sv-line--done' : ''; ?>"
									     aria-hidden="true"></div>
									<?php endif; ?>
								</div>

								<div class="wzp-ot__sv-content">
									<span class="wzp-ot__sv-label"><?php echo esc_html( $s['label'] ); ?></span>
									<?php if ( $done || $active ) : ?>
									<span class="wzp-ot__sv-desc"><?php echo esc_html( $desc ); ?></span>
									<?php endif; ?>
								</div>

							</div>
							<?php endforeach; ?>
						</div>

						<?php else : ?>
						<!-- Terminal status block -->
						<div class="wzp-ot__terminal wzp-ot__terminal--<?php echo esc_attr( $status ); ?>">
							<?php if ( 'refunded' === $status ) : ?>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-4.95"/></svg>
							<?php else : ?>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
							<?php endif; ?>
							<div>
								<strong><?php echo esc_html( $status_label ); ?></strong>
								<span><?php esc_html_e( 'Please contact our support team if you have any questions.', 'woo-zee-plugin' ); ?></span>
							</div>
						</div>
						<?php endif; ?>

						<!-- Left-panel actions -->
						<div class="wzp-ot__left-actions">
							<a href="<?php echo esc_url( get_permalink() ); ?>" class="wzp-ot__action-link wzp-ot__action-link--primary">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
								<?php esc_html_e( 'Track Another Order', 'woo-zee-plugin' ); ?>
							</a>
							<?php if ( $contact_url ) : ?>
							<a href="<?php echo esc_url( $contact_url ); ?>" class="wzp-ot__action-link">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
								<?php esc_html_e( 'Contact Support', 'woo-zee-plugin' ); ?>
							</a>
							<?php endif; ?>
						</div>

					</div><!-- /.wzp-ot__left-card -->
				</aside>

				<!-- ── RIGHT PANEL ───────────────────────────────────────────── -->
				<div class="wzp-ot__result-right">

					<!-- Items ordered -->
					<div class="wzp-ot__card">
						<h2 class="wzp-ot__card-title">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
							<?php esc_html_e( 'Items Ordered', 'woo-zee-plugin' ); ?>
						</h2>
						<div class="wzp-ot__items-list">
							<?php foreach ( $order->get_items() as $item ) :
								/** @var WC_Order_Item_Product $item */
								$product     = $item->get_product();
								$thumb_url   = '';
								if ( $product && $product->get_image_id() ) {
									$thumb_url = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );
								}
								$product_url = $product ? get_permalink( $product->get_id() ) : '';

								// Variation attributes
								$attr_str = '';
								if ( $item->get_variation_id() ) {
									$var = wc_get_product( $item->get_variation_id() );
									if ( $var ) {
										$parts = array();
										foreach ( $var->get_variation_attributes() as $k => $v ) {
											if ( $v ) {
												$parts[] = ucfirst( str_replace( array( 'attribute_pa_', 'attribute_' ), '', $k ) ) . ': ' . $v;
											}
										}
										$attr_str = implode( ' · ', $parts );
									}
								}
							?>
							<div class="wzp-ot__item">
								<div class="wzp-ot__item-img">
									<?php if ( $thumb_url ) : ?>
										<img src="<?php echo esc_url( $thumb_url ); ?>"
										     alt="<?php echo esc_attr( $item->get_name() ); ?>"
										     loading="lazy" width="80" height="80">
									<?php else : ?>
										<div class="wzp-ot__item-placeholder" aria-hidden="true">
											<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
										</div>
									<?php endif; ?>
									<span class="wzp-ot__item-qty-badge" aria-label="<?php printf( esc_attr__( 'Qty %d', 'woo-zee-plugin' ), $item->get_quantity() ); ?>">
										<?php echo esc_html( $item->get_quantity() ); ?>
									</span>
								</div>
								<div class="wzp-ot__item-info">
									<?php if ( $product_url ) : ?>
										<a href="<?php echo esc_url( $product_url ); ?>" class="wzp-ot__item-name">
											<?php echo esc_html( $item->get_name() ); ?>
										</a>
									<?php else : ?>
										<span class="wzp-ot__item-name"><?php echo esc_html( $item->get_name() ); ?></span>
									<?php endif; ?>
									<?php if ( $attr_str ) : ?>
										<span class="wzp-ot__item-variant"><?php echo esc_html( $attr_str ); ?></span>
									<?php endif; ?>
								</div>
								<div class="wzp-ot__item-price">
									<?php echo wp_kses_post( wc_price( $item->get_total() ) ); ?>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					</div>

					<!-- Order summary -->
					<div class="wzp-ot__card">
						<h2 class="wzp-ot__card-title">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
							<?php esc_html_e( 'Order Summary', 'woo-zee-plugin' ); ?>
						</h2>
						<div class="wzp-ot__summary">
							<div class="wzp-ot__summary-row">
								<span><?php esc_html_e( 'Subtotal', 'woo-zee-plugin' ); ?></span>
								<span><?php echo wp_kses_post( wc_price( $order->get_subtotal() ) ); ?></span>
							</div>
							<div class="wzp-ot__summary-row">
								<span><?php esc_html_e( 'Shipping', 'woo-zee-plugin' ); ?></span>
								<?php if ( $order->get_shipping_total() > 0 ) : ?>
								<span><?php echo wp_kses_post( wc_price( $order->get_shipping_total() ) ); ?></span>
								<?php else : ?>
								<span class="wzp-ot__free"><?php esc_html_e( 'Free', 'woo-zee-plugin' ); ?></span>
								<?php endif; ?>
							</div>
							<?php if ( $order->get_discount_total() > 0 ) : ?>
							<div class="wzp-ot__summary-row wzp-ot__summary-row--discount">
								<span><?php esc_html_e( 'Discount', 'woo-zee-plugin' ); ?></span>
								<span>−<?php echo wp_kses_post( wc_price( $order->get_discount_total() ) ); ?></span>
							</div>
							<?php endif; ?>
							<div class="wzp-ot__summary-row wzp-ot__summary-row--total">
								<span><?php esc_html_e( 'Total', 'woo-zee-plugin' ); ?></span>
								<span><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span>
							</div>
						</div>
					</div>

					<!-- Shipping address -->
					<?php $ship_address = $order->get_formatted_shipping_address(); ?>
					<?php if ( $ship_address ) : ?>
					<div class="wzp-ot__card">
						<h2 class="wzp-ot__card-title">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
							<?php esc_html_e( 'Shipping To', 'woo-zee-plugin' ); ?>
						</h2>
						<address class="wzp-ot__address">
							<?php echo wp_kses_post( $ship_address ); ?>
						</address>
					</div>
					<?php endif; ?>

				</div><!-- /.wzp-ot__result-right -->

			</div><!-- /.wzp-ot__result-grid -->
			<?php endif; ?>

			<!-- ── FAQ (always visible — SEO) ────────────────────────────────── -->
			<section class="wzp-ot__faq" aria-labelledby="wzp-ot-faq-heading">
				<div class="wzp-ot__faq-inner">
					<h2 class="wzp-ot__faq-heading" id="wzp-ot-faq-heading">
						<?php esc_html_e( 'Frequently Asked Questions', 'woo-zee-plugin' ); ?>
					</h2>
					<div class="wzp-ot__faq-list">
						<?php foreach ( $faqs as $faq ) : ?>
						<details class="wzp-ot__faq-item">
							<summary class="wzp-ot__faq-q">
								<span><?php echo esc_html( $faq['q'] ); ?></span>
								<span class="wzp-ot__faq-chevron" aria-hidden="true">
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
								</span>
							</summary>
							<div class="wzp-ot__faq-a">
								<?php echo esc_html( $faq['a'] ); ?>
							</div>
						</details>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

		</div><!-- /.wzp-ot__body -->
	</div><!-- /.wzp-ot -->
	<?php
	return ob_get_clean();
}

endif;

echo wzp_render_order_tracking( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
