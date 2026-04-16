<?php
/**
 * [wzp_cart] — Custom Cart page.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) ) { return; }

$cart       = WC()->cart;
$cart_items = $cart ? $cart->get_cart() : array();
$is_empty   = ! $cart_items;
?>

<div class="wzp-cart" data-wzp-module="cart">

<?php if ( $is_empty ) : ?>

	<div class="wzp-cart-empty">
		<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<path d="M8 8h4l5.6 28H48l5-20H17"/>
			<circle cx="24" cy="54" r="3"/>
			<circle cx="44" cy="54" r="3"/>
		</svg>
		<h2><?php esc_html_e( 'Your cart is empty', 'woo-zee-plugin' ); ?></h2>
		<p><?php esc_html_e( "Looks like you haven't added anything yet.", 'woo-zee-plugin' ); ?></p>
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wzp-cart-empty__btn">
			<?php esc_html_e( 'Continue Shopping', 'woo-zee-plugin' ); ?>
		</a>
	</div>

<?php else : ?>

	<!-- Header -->
	<div class="wzp-cart-hd">
		<h1 class="wzp-cart-hd__title"><?php esc_html_e( 'Shopping Cart', 'woo-zee-plugin' ); ?></h1>
		<span class="wzp-cart-hd__count">
			<?php printf(
				esc_html( _n( '%d item', '%d items', count( $cart_items ), 'woo-zee-plugin' ) ),
				count( $cart_items )
			); ?>
		</span>
	</div>

	<!-- Two-column layout -->
	<div class="wzp-cart-body">

		<!-- LEFT: items -->
		<div class="wzp-cart-items">

			<!-- Table head -->
			<div class="wzp-cart-items__head">
				<span class="wzp-ci-col wzp-ci-col--product"><?php esc_html_e( 'Product', 'woo-zee-plugin' ); ?></span>
				<span class="wzp-ci-col wzp-ci-col--price"><?php esc_html_e( 'Price', 'woo-zee-plugin' ); ?></span>
				<span class="wzp-ci-col wzp-ci-col--qty"><?php esc_html_e( 'Qty', 'woo-zee-plugin' ); ?></span>
				<span class="wzp-ci-col wzp-ci-col--total"><?php esc_html_e( 'Total', 'woo-zee-plugin' ); ?></span>
				<span class="wzp-ci-col wzp-ci-col--rm"></span>
			</div>

			<!-- Rows -->
			<?php foreach ( $cart_items as $cart_item_key => $cart_item ) :
				$product    = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				if ( ! $product || ! $product->exists() || 0 === $cart_item['quantity'] ) { continue; }

				$product_name = apply_filters( 'woocommerce_cart_item_name', $product->get_name(), $cart_item, $cart_item_key );
				$product_url  = apply_filters( 'woocommerce_cart_item_permalink', $product->is_visible() ? $product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				$thumbnail    = apply_filters( 'woocommerce_cart_item_thumbnail', $product->get_image( 'thumbnail' ), $cart_item, $cart_item_key );
				$price        = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $product ), $cart_item, $cart_item_key );
				$subtotal     = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
				$remove_url   = wc_get_cart_remove_url( $cart_item_key );
				$item_data    = wc_get_formatted_cart_item_data( $cart_item );
			?>
			<div class="wzp-cart-row" data-key="<?php echo esc_attr( $cart_item_key ); ?>">

				<!-- Product -->
				<div class="wzp-ci-col wzp-ci-col--product">
					<div class="wzp-cart-row__product">
						<div class="wzp-cart-row__img">
							<?php if ( $product_url ) : ?><a href="<?php echo esc_url( $product_url ); ?>"><?php endif; ?>
								<?php echo $thumbnail; // phpcs:ignore ?>
							<?php if ( $product_url ) : ?></a><?php endif; ?>
						</div>
						<div class="wzp-cart-row__info">
							<?php if ( $product_url ) : ?>
								<a href="<?php echo esc_url( $product_url ); ?>" class="wzp-cart-row__name"><?php echo wp_kses_post( $product_name ); ?></a>
							<?php else : ?>
								<span class="wzp-cart-row__name"><?php echo wp_kses_post( $product_name ); ?></span>
							<?php endif; ?>
							<?php if ( $item_data ) : ?>
								<div class="wzp-cart-row__meta"><?php echo wp_kses_post( $item_data ); ?></div>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- Price -->
				<div class="wzp-ci-col wzp-ci-col--price wzp-cart-row__price">
					<?php echo wp_kses_post( $price ); ?>
				</div>

				<!-- Qty -->
				<div class="wzp-ci-col wzp-ci-col--qty wzp-cart-row__qty">
					<div class="wzp-stepper">
						<button class="wzp-stepper__btn wzp-stepper__btn--minus" type="button" aria-label="<?php esc_attr_e( 'Decrease', 'woo-zee-plugin' ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
						</button>
						<input class="wzp-stepper__input" type="number"
							value="<?php echo esc_attr( $cart_item['quantity'] ); ?>"
							min="0" step="1"
							data-key="<?php echo esc_attr( $cart_item_key ); ?>"
							aria-label="<?php esc_attr_e( 'Quantity', 'woo-zee-plugin' ); ?>">
						<button class="wzp-stepper__btn wzp-stepper__btn--plus" type="button" aria-label="<?php esc_attr_e( 'Increase', 'woo-zee-plugin' ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
						</button>
					</div>
				</div>

				<!-- Row total -->
				<div class="wzp-ci-col wzp-ci-col--total wzp-cart-row__subtotal">
					<?php echo wp_kses_post( $subtotal ); ?>
				</div>

				<!-- Remove -->
				<div class="wzp-ci-col wzp-ci-col--rm">
					<a href="<?php echo esc_url( $remove_url ); ?>"
					   class="wzp-cart-row__remove"
					   data-product_id="<?php echo esc_attr( $product_id ); ?>"
					   data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>"
					   aria-label="<?php esc_attr_e( 'Remove item', 'woo-zee-plugin' ); ?>">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
					</a>
				</div>

			</div>
			<?php endforeach; ?>

			<!-- Continue shopping -->
			<div class="wzp-cart-continue">
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="wzp-cart-continue__link">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
					<?php esc_html_e( 'Continue Shopping', 'woo-zee-plugin' ); ?>
				</a>
			</div>

		</div><!-- /.wzp-cart-items -->

		<!-- RIGHT: summary -->
		<aside class="wzp-cart-summary">

			<h2 class="wzp-cart-summary__title"><?php esc_html_e( 'Order Summary', 'woo-zee-plugin' ); ?></h2>

			<!-- Coupon -->
			<?php if ( wc_coupons_enabled() ) : ?>
			<div class="wzp-cart-coupon">
				<button class="wzp-cart-coupon__toggle" type="button" aria-expanded="false">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
					<span><?php esc_html_e( 'Have a coupon?', 'woo-zee-plugin' ); ?></span>
					<svg class="wzp-cart-coupon__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
				</button>
				<div class="wzp-cart-coupon__panel" hidden>
					<?php woocommerce_coupon_form(); ?>
				</div>
			</div>
			<?php endif; ?>

			<!-- Totals -->
			<div class="wzp-cart-totals">

				<div class="wzp-cart-totals__row">
					<span><?php esc_html_e( 'Subtotal', 'woo-zee-plugin' ); ?></span>
					<span><?php echo wp_kses_post( $cart->get_cart_subtotal() ); ?></span>
				</div>

				<?php if ( $cart->get_discount_total() ) : ?>
				<div class="wzp-cart-totals__row wzp-cart-totals__row--discount">
					<span><?php esc_html_e( 'Discount', 'woo-zee-plugin' ); ?></span>
					<span>-<?php echo wp_kses_post( wc_price( $cart->get_discount_total() ) ); ?></span>
				</div>
				<?php endif; ?>

				<?php foreach ( $cart->get_fees() as $fee ) : ?>
				<div class="wzp-cart-totals__row">
					<span><?php echo esc_html( $fee->name ); ?></span>
					<span><?php echo wp_kses_post( wc_price( $fee->total ) ); ?></span>
				</div>
				<?php endforeach; ?>

				<?php if ( wc_tax_enabled() && ! $cart->display_prices_including_tax() ) : ?>
					<?php foreach ( $cart->get_tax_totals() as $tax ) : ?>
					<div class="wzp-cart-totals__row">
						<span><?php echo esc_html( $tax->label ); ?></span>
						<span><?php echo wp_kses_post( $tax->formatted_amount ); ?></span>
					</div>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
				<div class="wzp-cart-totals__row">
					<span><?php esc_html_e( 'Shipping', 'woo-zee-plugin' ); ?></span>
					<span><?php woocommerce_shipping_calculator(); ?></span>
				</div>
				<?php endif; ?>

				<div class="wzp-cart-totals__row wzp-cart-totals__row--grand">
					<span><?php esc_html_e( 'Total', 'woo-zee-plugin' ); ?></span>
					<span><?php echo wp_kses_post( $cart->get_total() ); ?></span>
				</div>

			</div>

			<!-- Checkout CTA -->
			<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="wzp-cart-checkout-btn">
				<?php esc_html_e( 'Proceed to Checkout', 'woo-zee-plugin' ); ?>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</a>

			<!-- Trust badges -->
			<div class="wzp-cart-trust">
				<div class="wzp-cart-trust__item">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
					<?php esc_html_e( 'Secure Checkout', 'woo-zee-plugin' ); ?>
				</div>
				<div class="wzp-cart-trust__item">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
					<?php esc_html_e( 'Safe Payment', 'woo-zee-plugin' ); ?>
				</div>
				<div class="wzp-cart-trust__item">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
					<?php esc_html_e( 'Free Delivery', 'woo-zee-plugin' ); ?>
				</div>
			</div>

		</aside>

	</div><!-- /.wzp-cart-body -->

<?php endif; ?>

</div><!-- /.wzp-cart -->

<script>
(function(){
	var wrap = document.querySelector('[data-wzp-module="cart"]');
	if(!wrap) return;

	var nonce = '<?php echo esc_js( wp_create_nonce( 'woocommerce-cart' ) ); ?>';

	/* ── Stepper buttons ── */
	wrap.addEventListener('click', function(e){
		var btn = e.target.closest('.wzp-stepper__btn');
		if(!btn) return;
		var input = btn.closest('.wzp-stepper').querySelector('.wzp-stepper__input');
		var val   = parseInt(input.value, 10) || 0;
		input.value = btn.classList.contains('wzp-stepper__btn--plus')
			? val + 1
			: Math.max(0, val - 1);
		doUpdate(input);
	});

	/* ── Manual qty input ── */
	wrap.addEventListener('change', function(e){
		if(e.target.classList.contains('wzp-stepper__input')) doUpdate(e.target);
	});

	function doUpdate(input){
		var key = input.dataset.key;
		var qty = parseInt(input.value, 10);
		if(isNaN(qty) || qty < 0) return;
		setLoading(true);
		fetch('<?php echo esc_url( wc_get_cart_url() ); ?>', {
			method:  'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			body:    new URLSearchParams({
				'update_cart': '1',
				'woocommerce-cart-nonce': nonce,
				['cart[' + key + '][qty]']: qty
			})
		})
		.then(function(){ window.location.reload(); })
		.catch(function(){ setLoading(false); });
	}

	/* ── Coupon toggle ── */
	var couponBtn = wrap.querySelector('.wzp-cart-coupon__toggle');
	if(couponBtn){
		couponBtn.addEventListener('click', function(){
			var panel   = wrap.querySelector('.wzp-cart-coupon__panel');
			var isOpen  = !panel.hasAttribute('hidden');
			if(isOpen){
				panel.setAttribute('hidden','');
				couponBtn.setAttribute('aria-expanded','false');
				couponBtn.classList.remove('is-open');
			} else {
				panel.removeAttribute('hidden');
				couponBtn.setAttribute('aria-expanded','true');
				couponBtn.classList.add('is-open');
				var inp = panel.querySelector('input[name="coupon_code"]');
				if(inp) inp.focus();
			}
		});
	}

	function setLoading(on){
		wrap.style.opacity = on ? '0.6' : '1';
		wrap.style.pointerEvents = on ? 'none' : '';
	}
})();
</script>
