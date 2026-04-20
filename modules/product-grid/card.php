<?php
/**
 * [wzp_product_grid] — Single product card.
 *
 * Public API:
 *   wzp_render_product_card( int $product_id, array $options = [] ) : string
 *
 * $options keys (all optional):
 *   'show_rating'   bool   – show star rating row          (default true)
 *   'show_category' bool   – show category label           (default true)
 *   'show_wishlist' bool   – show wishlist heart button     (default true)
 *   'show_badge'    bool   – show SALE / NEW badge          (default true)
 *   'show_quickadd' bool   – show quick-add bar             (default true)
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wzp_render_product_card' ) ) :

/**
 * Build and return the HTML string for a single product card.
 *
 * @param int   $product_id  WooCommerce product post ID.
 * @param array $options     Optional display flags (see file header).
 * @return string            Escaped HTML; empty string on failure.
 */
function wzp_render_product_card( $product_id, $options = array() ) {

	$product = wc_get_product( absint( $product_id ) );

	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	// ── Options — merge caller args with saved card style settings ────────────
	$saved = wp_parse_args(
		(array) get_option( 'wzp_card_style_options', array() ),
		array(
			'show_rating'   => '1',
			'show_category' => '1',
			'show_wishlist' => '1',
			'show_badge'    => '1',
			'show_quickadd' => '1',
		)
	);

	$opts = wp_parse_args(
		$options,
		array(
			'show_rating'   => (bool) $saved['show_rating'],
			'show_category' => (bool) $saved['show_category'],
			'show_wishlist' => (bool) $saved['show_wishlist'],
			'show_badge'    => (bool) $saved['show_badge'],
			'show_quickadd' => (bool) $saved['show_quickadd'],
		)
	);

	// ── Product data ──────────────────────────────────────────────────────────
	$id          = $product->get_id();
	$name        = $product->get_name();
	$product_url = get_permalink( $id );
	$sku         = $product->get_sku();
	$cart_url    = $product->add_to_cart_url();

	// ── Images ────────────────────────────────────────────────────────────────
	$primary_img_id   = $product->get_image_id();
	$primary_img_data = $primary_img_id ? wp_get_attachment_image_src( $primary_img_id, 'large' ) : null;
	$primary_img_src  = $primary_img_data ? $primary_img_data[0] : wc_placeholder_img_src( 'large' );
	$primary_img_w    = $primary_img_data ? (int) $primary_img_data[1] : 0;
	$primary_img_h    = $primary_img_data ? (int) $primary_img_data[2] : 0;

	$gallery_ids        = $product->get_gallery_image_ids();
	$secondary_img_data = ! empty( $gallery_ids ) ? wp_get_attachment_image_src( $gallery_ids[0], 'large' ) : null;
	$secondary_img_src  = $secondary_img_data ? $secondary_img_data[0] : '';

	// ── Badge ─────────────────────────────────────────────────────────────────
	$is_on_sale   = $product->is_on_sale();
	$is_new       = false;
	$is_out_stock = ! $product->is_in_stock();

	if ( ! $is_on_sale && ! $is_out_stock ) {
		$date_created = $product->get_date_created();
		if ( $date_created instanceof WC_DateTime ) {
			$is_new = ( time() - $date_created->getTimestamp() ) <= ( 30 * DAY_IN_SECONDS );
		}
	}

	// ── Category ──────────────────────────────────────────────────────────────
	$category_name = '';
	if ( $opts['show_category'] ) {
		$cat_ids = $product->get_category_ids();
		if ( ! empty( $cat_ids ) ) {
			$term = get_term( absint( $cat_ids[0] ), 'product_cat' );
			if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
				$category_name = $term->name;
			}
		}
	}

	// ── Build HTML ────────────────────────────────────────────────────────────
	ob_start();
	?>
	<div class="wzp-product-card" data-product-id="<?php echo esc_attr( $id ); ?>">

		<?php /* ── Media area ─────────────────────────────────────────── */ ?>
		<div class="wzp-product-card__media">

			<?php /* Image + badges + action buttons */ ?>
			<div class="wzp-product-card__media-img">

				<?php if ( $is_out_stock ) : ?>
				<div class="wzp-product-card__oos-overlay" aria-hidden="true"></div>
				<?php endif; ?>

				<?php if ( $opts['show_badge'] && ( $is_out_stock || $is_on_sale || $is_new ) ) : ?>
				<div class="wzp-product-card__badges">
					<?php if ( $is_out_stock ) : ?>
					<span class="wzp-badge wzp-badge--oos"><?php esc_html_e( 'Out of Stock', 'woo-zee-plugin' ); ?></span>
					<?php elseif ( $is_on_sale ) : ?>
					<span class="wzp-badge wzp-badge--sale"><?php esc_html_e( 'Sale', 'woo-zee-plugin' ); ?></span>
					<?php elseif ( $is_new ) : ?>
					<span class="wzp-badge wzp-badge--new"><?php esc_html_e( 'New', 'woo-zee-plugin' ); ?></span>
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<a href="<?php echo esc_url( $product_url ); ?>"
				   aria-label="<?php echo esc_attr( $name ); ?>">
					<img class="wzp-product-card__img wzp-product-card__img--primary"
					     src="<?php echo esc_url( $primary_img_src ); ?>"
					     alt="<?php echo esc_attr( $name ); ?>"
					     loading="lazy"
					     decoding="async"
					     <?php if ( $primary_img_w && $primary_img_h ) : ?>
					     width="<?php echo esc_attr( $primary_img_w ); ?>"
					     height="<?php echo esc_attr( $primary_img_h ); ?>"
					     <?php endif; ?>>
					<?php if ( $secondary_img_src ) : ?>
					<img class="wzp-product-card__img wzp-product-card__img--secondary"
					     src="<?php echo esc_url( $secondary_img_src ); ?>"
					     alt="<?php echo esc_attr( $name ); ?>"
					     loading="lazy"
					     decoding="async"
					     <?php if ( $secondary_img_data ) : ?>
					     width="<?php echo esc_attr( (int) $secondary_img_data[1] ); ?>"
					     height="<?php echo esc_attr( (int) $secondary_img_data[2] ); ?>"
					     <?php endif; ?>>
					<?php endif; ?>
				</a>

				<?php /* Stacked action buttons — always visible */ ?>
				<div class="wzp-product-card__actions">
					<a href="<?php echo esc_url( $product_url ); ?>"
					   class="wzp-product-card__action-btn wzp-product-card__quickview"
					   aria-label="<?php esc_attr_e( 'Quick view', 'woo-zee-plugin' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
							<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
							<circle cx="12" cy="12" r="3"/>
						</svg>
					</a>
					<?php if ( $opts['show_wishlist'] ) : ?>
					<button class="wzp-product-card__action-btn wzp-product-card__wishlist"
					        data-product-id="<?php echo esc_attr( $id ); ?>"
					        data-product-name="<?php echo esc_attr( $name ); ?>"
					        data-cart-url="<?php echo esc_attr( $cart_url ); ?>"
					        aria-label="<?php esc_attr_e( 'Add to wishlist', 'woo-zee-plugin' ); ?>"
					        type="button">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true">
							<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06
							         a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23
							         l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
						</svg>
					</button>
					<?php endif; ?>
				</div>

			</div><?php /* /.wzp-product-card__media-img */ ?>

			<?php if ( $opts['show_quickadd'] ) : ?>
			<div class="wzp-product-card__quickadd">
				<?php if ( $is_out_stock ) : ?>
				<span class="wzp-btn wzp-product-card__quickadd-oos">
					<?php esc_html_e( 'Out of Stock', 'woo-zee-plugin' ); ?>
				</span>
				<?php else : ?>
				<a href="<?php echo esc_url( $cart_url ); ?>"
				   class="wzp-btn ajax_add_to_cart add_to_cart_button"
				   data-product_id="<?php echo esc_attr( $id ); ?>"
				   data-product_sku="<?php echo esc_attr( $sku ); ?>"
				   data-quantity="1"
				   rel="nofollow">
					<?php esc_html_e( 'Quick Add', 'woo-zee-plugin' ); ?>
				</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

		</div><?php /* /.wzp-product-card__media */ ?>

		<?php /* ── Card body ──────────────────────────────────────────── */ ?>
		<div class="wzp-product-card__body">

			<?php if ( $category_name ) : ?>
			<span class="wzp-product-card__category"><?php echo esc_html( $category_name ); ?></span>
			<?php endif; ?>

			<h3 class="wzp-product-card__title">
				<a href="<?php echo esc_url( $product_url ); ?>"><?php echo esc_html( $name ); ?></a>
			</h3>

			<?php if ( $opts['show_rating'] ) : ?>
			<div class="wzp-product-card__rating">
				<?php
				// Always render 5 stars — gray when no reviews, filled by WC when rated.
				$avg   = (float) $product->get_average_rating();
				$width = round( $avg / 5 * 100 );
				printf(
					'<span class="star-rating" role="img" aria-label="%s"><span style="width:%d%%"></span></span>',
					esc_attr( sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $avg ?: '0' ) ),
					$width
				);
				?>
			</div>
			<?php endif; ?>

			<div class="wzp-product-card__price">
				<?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>

		</div><?php /* /.wzp-product-card__body */ ?>

	</div><?php /* /.wzp-product-card */ ?>
	<?php
	return ob_get_clean();
}

endif;
