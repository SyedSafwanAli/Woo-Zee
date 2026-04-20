<?php
/**
 * [wzp_lookbook] — Render function and shortcode entry point.
 *
 * Shortcode:  [wzp_lookbook]
 * Data source: get_option('wzp_lookbook_options', [])
 *
 * Options structure:
 *   image_id    (int)    — WP attachment ID for the background image
 *   label       (string) — eyebrow text
 *   heading     (string) — section heading
 *   description (string) — body paragraph
 *   btn_text    (string) — CTA button label
 *   btn_url     (string) — CTA button URL
 *   hotspots    (array)  — [{x, y, product_id}, ...]
 *
 * Returns '' when no image and no heading are configured.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wzp_render_lookbook' ) ) :

/**
 * Build and return the lookbook HTML string.
 *
 * @param array $atts Shortcode attributes (currently unused).
 * @return string     Escaped HTML; empty string if no content is configured.
 */
function wzp_render_lookbook( $atts ) {

	// ── Load options ──────────────────────────────────────────────────────────
	$opts = (array) get_option( 'wzp_lookbook_options', array() );

	// ── Sanitise at render time ───────────────────────────────────────────────
	$image_id   = absint( $opts['image_id']    ?? 0 );
	$label      = sanitize_text_field( $opts['label']       ?? '' );
	$heading    = sanitize_text_field( $opts['heading']     ?? '' );
	$desc       = sanitize_textarea_field( $opts['description'] ?? '' );
	$btn_text   = sanitize_text_field( $opts['btn_text']    ?? '' );
	$btn_url    = esc_url( $opts['btn_url']    ?? '' );
	$hotspots   = ( isset( $opts['hotspots'] ) && is_array( $opts['hotspots'] ) )
	              ? $opts['hotspots']
	              : array();

	// Early exit: require at least an image or a heading.
	$image_url = $image_id
		? wp_get_attachment_image_url( $image_id, 'large' )
		: '';

	if ( ! $image_url && ! $heading ) {
		return '';
	}

	// ── Build product list ────────────────────────────────────────────────────
	$spot_num   = 0;
	$spot_items = array();

	foreach ( $hotspots as $hs ) {
		if ( ! is_array( $hs ) ) { continue; }
		$pid = absint( $hs['product_id'] ?? 0 );
		if ( ! $pid ) { continue; }
		$product = wc_get_product( $pid );
		if ( ! $product instanceof WC_Product ) { continue; }
		$spot_num++;
		$thumb_id    = $product->get_image_id();
		$thumb_src   = $thumb_id
			? wp_get_attachment_image_url( $thumb_id, 'thumbnail' )
			: wc_placeholder_img_src( 'thumbnail' );
		$is_simple    = $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock();
		$spot_items[] = array(
			'num'        => $spot_num,
			'x'          => min( 100, max( 0, (float) ( $hs['x'] ?? 0 ) ) ),
			'y'          => min( 100, max( 0, (float) ( $hs['y'] ?? 0 ) ) ),
			'name'       => $product->get_name(),
			'price_html' => $product->get_price_html(),
			'thumb'      => $thumb_src,
			'url'        => get_permalink( $product->get_id() ),
			'id'         => $product->get_id(),
			'sku'        => $product->get_sku(),
			'cart_url'   => $product->add_to_cart_url(),
			'is_simple'  => $is_simple,
		);
	}

	// ── Render ────────────────────────────────────────────────────────────────
	ob_start();
	?>
	<div class="wzp-module" data-wzp-module="lookbook">
		<section class="wzp-lookbook"
		         aria-label="<?php esc_attr_e( 'Lookbook', 'woo-zee-plugin' ); ?>">

			<?php /* ── Left: copy + product list ──────────────────────────── */ ?>
			<div class="wzp-lookbook__info-panel">

				<?php if ( $label ) : ?>
					<span class="wzp-lookbook__label">
						<?php echo esc_html( $label ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $heading ) : ?>
					<h2 class="wzp-lookbook__heading">
						<?php echo esc_html( $heading ); ?>
					</h2>
				<?php endif; ?>

				<?php if ( $desc ) : ?>
					<p class="wzp-lookbook__description">
						<?php echo esc_html( $desc ); ?>
					</p>
				<?php endif; ?>

				<?php if ( $spot_items ) : ?>
					<div class="wzp-lookbook__list">
						<?php foreach ( $spot_items as $i => $item ) : ?>
							<div class="wzp-lookbook__item<?php echo 0 === $i ? ' is-active' : ''; ?>"
							     data-spot="<?php echo esc_attr( $item['num'] ); ?>">

								<!-- Number badge -->
								<span class="wzp-lookbook__item-num">
									<?php echo esc_html( $item['num'] ); ?>
								</span>

								<!-- Text block: name + price + cart button -->
								<div class="wzp-lookbook__item-body">
									<a href="<?php echo esc_url( $item['url'] ); ?>"
									   class="wzp-lookbook__item-name">
										<?php echo esc_html( $item['name'] ); ?>
									</a>
									<span class="wzp-lookbook__item-price">
										<?php echo $item['price_html']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
									</span>
									<?php if ( $item['is_simple'] ) : ?>
									<a href="<?php echo esc_url( $item['cart_url'] ); ?>"
									   class="wzp-lookbook__add-btn ajax_add_to_cart add_to_cart_button"
									   data-product_id="<?php echo esc_attr( $item['id'] ); ?>"
									   data-product_sku="<?php echo esc_attr( $item['sku'] ); ?>"
									   data-quantity="1"
									   rel="nofollow">
										<?php esc_html_e( 'Add to Cart', 'woo-zee-plugin' ); ?>
									</a>
									<?php else : ?>
									<a href="<?php echo esc_url( $item['url'] ); ?>"
									   class="wzp-lookbook__add-btn wzp-lookbook__add-btn--select">
										<?php esc_html_e( 'Select Options', 'woo-zee-plugin' ); ?>
									</a>
									<?php endif; ?>
								</div>

								<!-- Thumbnail -->
								<a href="<?php echo esc_url( $item['url'] ); ?>"
								   class="wzp-lookbook__item-img-link"
								   tabindex="-1" aria-hidden="true">
									<img src="<?php echo esc_url( $item['thumb'] ); ?>"
									     alt="<?php echo esc_attr( $item['name'] ); ?>"
									     class="wzp-lookbook__item-img"
									     loading="lazy"
									     width="80"
									     height="80">
								</a>

							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<?php if ( $btn_text && $btn_url ) : ?>
					<a class="wzp-btn wzp-btn--dark wzp-lookbook__cta"
					   href="<?php echo esc_url( $btn_url ); ?>">
						<?php echo esc_html( $btn_text ); ?>
						<span aria-hidden="true">↗</span>
					</a>
				<?php endif; ?>

			</div>

			<?php /* ── Right: image + numbered dots ──────────────────────── */ ?>
			<div class="wzp-lookbook__image-panel">

				<?php if ( $image_url ) : ?>
					<img src="<?php echo esc_url( $image_url ); ?>"
					     alt="<?php echo esc_attr( $heading ); ?>"
					     class="wzp-lookbook__img"
					     loading="eager"
					     fetchpriority="high"
					     decoding="async">
				<?php endif; ?>

				<?php foreach ( $spot_items as $i => $item ) : ?>
					<div class="wzp-hotspot<?php echo 0 === $i ? ' is-active' : ''; ?>"
					     style="left:<?php echo esc_attr( $item['x'] ); ?>%;top:<?php echo esc_attr( $item['y'] ); ?>%"
					     data-spot="<?php echo esc_attr( $item['num'] ); ?>">
						<button class="wzp-hotspot__dot"
						        type="button"
						        aria-label="<?php echo esc_attr( sprintf(
						        	/* translators: 1: number 2: product name */
						        	__( 'View product %1$d: %2$s', 'woo-zee-plugin' ),
						        	$item['num'],
						        	$item['name']
						        ) ); ?>">
							<?php echo esc_html( $item['num'] ); ?>
						</button>
					</div>
				<?php endforeach; ?>

			</div><?php /* /.wzp-lookbook__image-panel */ ?>

		</section>
	</div>

	<script>
	( function () {
		var wrap  = document.querySelector( '[data-wzp-module="lookbook"]' );
		if ( ! wrap ) { return; }
		var items = wrap.querySelectorAll( '.wzp-lookbook__item' );
		var dots  = wrap.querySelectorAll( '.wzp-hotspot' );

		function activate( num ) {
			items.forEach( function ( el ) {
				el.classList.toggle( 'is-active', String( el.dataset.spot ) === String( num ) );
			} );
			dots.forEach( function ( el ) {
				el.classList.toggle( 'is-active', String( el.dataset.spot ) === String( num ) );
			} );
		}

		dots.forEach( function ( dot ) {
			dot.addEventListener( 'mouseenter', function () {
				activate( this.dataset.spot );
			} );
		} );

		items.forEach( function ( item ) {
			item.addEventListener( 'mouseenter', function () {
				activate( this.dataset.spot );
			} );
		} );
	} )();
	</script>
	<?php
	return ob_get_clean();
}

endif; // function_exists

// ── Entry point ───────────────────────────────────────────────────────────────
echo wzp_render_lookbook( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
