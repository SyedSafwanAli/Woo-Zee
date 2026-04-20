<?php
/**
 * Product Detail Module — Render File
 *
 * Shortcode: [wzp_product_detail]          — uses the current post's product
 *            [wzp_product_detail id="123"] — loads a specific product
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) ) {
	return;
}

// ── SVG icon map ──────────────────────────────────────────────────────────────

if ( ! function_exists( 'wzp_pd_icon_svg' ) ) {
	function wzp_pd_icon_svg( $key ) {
		$a  = 'aria-hidden="true"';
		$s  = 'stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"';
		$b  = 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"';
		$map = array(
			'leaf'    => "<svg $b $s $a><path d='M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z'/><path d='M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12'/></svg>",
			'return'  => "<svg $b $s $a><polyline points='1 4 1 10 7 10'/><path d='M3.51 15a9 9 0 1 0 .49-3.82'/></svg>",
			'diamond' => "<svg $b $s $a><path d='M6 3h12l4 6-10 13L2 9z'/><path d='M11 3L8 9l4 13 4-13-3-6'/><path d='M2 9h20'/></svg>",
			'truck'   => "<svg $b $s $a><rect x='1' y='3' width='15' height='13'/><polygon points='16 8 20 8 23 11 23 16 16 16 16 8'/><circle cx='5.5' cy='18.5' r='2.5'/><circle cx='18.5' cy='18.5' r='2.5'/></svg>",
			'package' => "<svg $b $s $a><line x1='16.5' y1='9.4' x2='7.5' y2='4.21'/><path d='M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'/><polyline points='3.27 6.96 12 12.01 20.73 6.96'/><line x1='12' y1='22.08' x2='12' y2='12'/></svg>",
			'shield'  => "<svg $b $s $a><path d='M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'/></svg>",
			'heart'   => "<svg $b $s $a><path d='M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z'/></svg>",
			'globe'   => "<svg $b $s $a><circle cx='12' cy='12' r='10'/><line x1='2' y1='12' x2='22' y2='12'/><path d='M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z'/></svg>",
			'lock'    => "<svg $b $s $a><rect x='3' y='11' width='18' height='11' rx='2' ry='2'/><path d='M7 11V7a5 5 0 0 1 10 0v4'/></svg>",
			'clock'   => "<svg $b $s $a><circle cx='12' cy='12' r='10'/><polyline points='12 6 12 12 16 14'/></svg>",
			'check'   => "<svg $b $s $a><polyline points='20 6 9 17 4 12'/></svg>",
			'gift'    => "<svg $b $s $a><polyline points='20 12 20 22 4 22 4 12'/><rect x='2' y='7' width='20' height='5'/><line x1='12' y1='22' x2='12' y2='7'/><path d='M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z'/><path d='M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z'/></svg>",
			'star'    => "<svg $b $s $a><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>",
		);
		return $map[ $key ] ?? '';
	}
}

// ── Product ───────────────────────────────────────────────────────────────────

$product_id = ! empty( $atts['id'] ) ? absint( $atts['id'] ) : get_the_ID();
$product    = wc_get_product( $product_id );

if ( ! $product instanceof WC_Product ) {
	return;
}

$product_type = $product->get_type();

// ── Settings from admin ───────────────────────────────────────────────────────

$settings = (array) get_option( 'wzp_product_detail_settings', array() );

$benefits = ( ! empty( $settings['benefits'] ) && is_array( $settings['benefits'] ) )
	? $settings['benefits']
	: array(
		array( 'icon' => 'leaf',    'title' => 'Sustainable Materials', 'subtitle' => 'Thoughtfully sourced, earth-friendly fabrics.' ),
		array( 'icon' => 'return',  'title' => '30 Days Free Returns',  'subtitle' => 'Changed your mind? No problem.' ),
		array( 'icon' => 'diamond', 'title' => 'Premium Quality',       'subtitle' => 'Every piece is handcrafted with care.' ),
		array( 'icon' => 'truck',   'title' => 'Free Shipping',         'subtitle' => 'Complimentary on all orders over $50.' ),
	);

$shipping_lines = ( ! empty( $settings['shipping'] ) && is_array( $settings['shipping'] ) )
	? $settings['shipping']
	: array(
		array( 'icon' => 'truck',   'text' => 'Free standard shipping on orders over $50.' ),
		array( 'icon' => 'package', 'text' => 'Estimated delivery: 3–5 business days.' ),
	);

// ── Images ────────────────────────────────────────────────────────────────────

$main_image_id = $product->get_image_id();
$gallery_ids   = $product->get_gallery_image_ids();
$all_image_ids = array_values( array_filter( array_merge( array( $main_image_id ), $gallery_ids ) ) );

// ── Product data ──────────────────────────────────────────────────────────────

$title       = $product->get_name();
$price_html  = $product->get_price_html();
$short_desc  = $product->get_short_description();
$sku         = $product->get_sku();
$is_in_stock = $product->is_in_stock();
$stock_qty   = $product->get_stock_quantity();
$is_on_sale  = $product->is_on_sale();

$categories = get_the_term_list( $product_id, 'product_cat', '', ', ' );
$tags        = get_the_term_list( $product_id, 'product_tag', '', ', ' );

// ── Variable product ──────────────────────────────────────────────────────────

$attributes = array();
if ( 'variable' === $product_type ) {
	/** @var WC_Product_Variable $product */
	$attributes    = $product->get_variation_attributes();
	$variations_js = wp_json_encode( $product->get_available_variations() );
}

$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
$permalink    = get_permalink( $product_id );

// ── Main image data ───────────────────────────────────────────────────────────

$first_img_id  = $all_image_ids[0] ?? 0;
$main_img_src  = $first_img_id
	? ( wp_get_attachment_image_url( $first_img_id, 'woocommerce_single' ) ?: wp_get_attachment_image_url( $first_img_id, 'large' ) )
	: wc_placeholder_img_src( 'woocommerce_single' );
$main_img_alt  = $first_img_id
	? trim( wp_strip_all_tags( get_post_meta( $first_img_id, '_wp_attachment_image_alt', true ) ) )
	: esc_attr( $title );

// ── JSON-LD Product schema ─────────────────────────────────────────────────

$raw_price     = (float) $product->get_price();
$price_valid   = $raw_price > 0;
$currency      = get_woocommerce_currency();
$brand_name    = get_bloginfo( 'name' );
$desc_plain    = wp_strip_all_tags( $product->get_description() ?: $short_desc );

$schema = array(
	'@context'    => 'https://schema.org',
	'@type'       => 'Product',
	'name'        => $title,
	'description' => $desc_plain,
	'url'         => $permalink,
);

if ( $sku ) {
	$schema['sku'] = $sku;
}

if ( $brand_name ) {
	$schema['brand'] = array( '@type' => 'Brand', 'name' => $brand_name );
}

// Images
$schema_images = array();
foreach ( $all_image_ids as $img_id ) {
	$img_url = wp_get_attachment_image_url( $img_id, 'full' );
	if ( $img_url ) {
		$schema_images[] = esc_url_raw( $img_url );
	}
}
if ( $schema_images ) {
	$schema['image'] = count( $schema_images ) === 1 ? $schema_images[0] : $schema_images;
}

// Offer
$schema['offers'] = array(
	'@type'           => 'Offer',
	'url'             => $permalink,
	'priceCurrency'   => $currency,
	'availability'    => $is_in_stock
		? 'https://schema.org/InStock'
		: 'https://schema.org/OutOfStock',
	'itemCondition'   => 'https://schema.org/NewCondition',
	'seller'          => array( '@type' => 'Organization', 'name' => $brand_name ),
);
if ( $price_valid ) {
	$schema['offers']['price'] = $raw_price;
}

// AggregateRating from WooCommerce reviews
$avg_rating    = (float) $product->get_average_rating();
$review_count  = (int) $product->get_review_count();
if ( $avg_rating > 0 && $review_count > 0 ) {
	$schema['aggregateRating'] = array(
		'@type'       => 'AggregateRating',
		'ratingValue' => $avg_rating,
		'reviewCount' => $review_count,
	);
}

echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";

// ── BreadcrumbList schema ──────────────────────────────────────────────────

$crumb_items = array(
	array( 'name' => __( 'Home', 'woo-zee-plugin' ), 'url' => home_url( '/' ) ),
);

$cat_terms = get_the_terms( $product_id, 'product_cat' );
if ( $cat_terms && ! is_wp_error( $cat_terms ) ) {
	foreach ( $cat_terms as $_cat ) {
		if ( 'uncategorized' !== $_cat->slug ) {
			$_cat_url = get_term_link( $_cat );
			if ( ! is_wp_error( $_cat_url ) ) {
				$crumb_items[] = array( 'name' => $_cat->name, 'url' => $_cat_url );
			}
			break;
		}
	}
}

$crumb_items[] = array( 'name' => $title, 'url' => $permalink );

$breadcrumb_schema = array(
	'@context'        => 'https://schema.org',
	'@type'           => 'BreadcrumbList',
	'itemListElement' => array(),
);
foreach ( $crumb_items as $i => $crumb ) {
	$breadcrumb_schema['itemListElement'][] = array(
		'@type'    => 'ListItem',
		'position' => $i + 1,
		'name'     => $crumb['name'],
		'item'     => $crumb['url'],
	);
}

echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";

?>
<section class="wzp-pd"
         data-product-id="<?php echo esc_attr( $product_id ); ?>"
         data-product-type="<?php echo esc_attr( $product_type ); ?>">
	<div class="wzp-pd__inner">

		<!-- ── Gallery ──────────────────────────────────────────────────── -->
		<div class="wzp-pd__gallery">

			<!-- Thumbnails -->
			<?php if ( count( $all_image_ids ) > 1 ) : ?>
				<div class="wzp-pd__thumbs">
					<?php foreach ( $all_image_ids as $i => $img_id ) :
						$thumb_url = wp_get_attachment_image_url( $img_id, 'thumbnail' );
						$full_url  = wp_get_attachment_image_url( $img_id, 'woocommerce_single' ) ?: wp_get_attachment_image_url( $img_id, 'large' );
						if ( ! $thumb_url ) { continue; }
					?>
						<button type="button"
						        class="wzp-pd__thumb<?php echo 0 === $i ? ' wzp-pd__thumb--active' : ''; ?>"
						        data-full="<?php echo esc_url( $full_url ); ?>"
						        aria-label="<?php printf( esc_attr__( 'View image %d', 'woo-zee-plugin' ), $i + 1 ); ?>">
							<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" loading="lazy">
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- Main image -->
			<div class="wzp-pd__main-img-wrap">
				<img class="wzp-pd__main-img"
				     src="<?php echo esc_url( $main_img_src ); ?>"
				     alt="<?php echo esc_attr( $main_img_alt ); ?>">
				<?php if ( $is_on_sale ) : ?>
					<span class="wzp-pd__sale-badge"><?php esc_html_e( 'Sale', 'woo-zee-plugin' ); ?></span>
				<?php endif; ?>
			</div>

		</div>
		<!-- /gallery -->

		<!-- ── Info ─────────────────────────────────────────────────────── -->
		<div class="wzp-pd__info">

			<!-- Category breadcrumb -->
			<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
				<nav class="wzp-pd__breadcrumb" aria-label="<?php esc_attr_e( 'Product category', 'woo-zee-plugin' ); ?>">
					<?php echo wp_kses_post( $categories ); ?>
				</nav>
			<?php endif; ?>

			<!-- Title -->
			<h1 class="wzp-pd__title"><?php echo esc_html( $title ); ?></h1>

			<!-- Price -->
			<div class="wzp-pd__price"><?php echo wp_kses_post( $price_html ); ?></div>

			<!-- Short description -->
			<?php if ( $short_desc ) : ?>
				<div class="wzp-pd__short-desc"><?php echo wp_kses_post( $short_desc ); ?></div>
			<?php endif; ?>

			<!-- ── Add to cart form ─────────────────────────────────────── -->
			<form class="wzp-pd__form cart"
			      method="post"
			      action="<?php echo esc_url( $permalink ); ?>"
			      enctype="multipart/form-data"
			      data-product-id="<?php echo esc_attr( $product_id ); ?>"
			      data-product-type="<?php echo esc_attr( $product_type ); ?>"
			      <?php if ( 'variable' === $product_type ) : ?>
			      data-product_id="<?php echo esc_attr( $product_id ); ?>"
			      data-product_variations="<?php echo esc_attr( $variations_js ); ?>"
			      <?php endif; ?>>

				<?php if ( 'variable' === $product_type && ! empty( $attributes ) ) : ?>
					<!-- Variation selects -->
					<div class="wzp-pd__variations">
						<?php foreach ( $attributes as $attr_name => $options ) :
							$attr_label  = wc_attribute_label( $attr_name );
							$attr_field  = 'attribute_' . sanitize_title( $attr_name );
							$is_taxonomy = taxonomy_exists( $attr_name );
							$terms       = $is_taxonomy
								? wc_get_product_terms( $product_id, $attr_name, array( 'fields' => 'names' ) )
								: $options;
						?>
							<div class="wzp-pd__variation-row">
								<label class="wzp-pd__variation-label"
								       for="<?php echo esc_attr( $attr_field . '_' . $product_id ); ?>">
									<?php echo esc_html( $attr_label ); ?>
								</label>
								<select name="<?php echo esc_attr( $attr_field ); ?>"
								        id="<?php echo esc_attr( $attr_field . '_' . $product_id ); ?>"
								        class="wzp-pd__variation-select"
								        data-attribute_name="<?php echo esc_attr( 'attribute_' . sanitize_title( $attr_name ) ); ?>">
									<option value="">
										<?php
										/* translators: %s: attribute label */
										printf( esc_html__( 'Choose %s', 'woo-zee-plugin' ), esc_html( $attr_label ) );
										?>
									</option>
									<?php foreach ( $terms as $term_name ) : ?>
										<option value="<?php echo esc_attr( $term_name ); ?>">
											<?php echo esc_html( $term_name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endforeach; ?>
					</div>
					<input type="hidden" name="variation_id" value="">
					<p class="wzp-pd__variation-msg" hidden></p>
				<?php endif; ?>

				<!-- Quantity + Add to cart -->
				<div class="wzp-pd__atc-row">
					<div class="wzp-pd__qty">
						<button type="button" class="wzp-pd__qty-btn" data-action="minus"
						        aria-label="<?php esc_attr_e( 'Decrease quantity', 'woo-zee-plugin' ); ?>">−</button>
						<input type="number"
						       class="wzp-pd__qty-input qty"
						       name="quantity"
						       value="1"
						       min="1"
						       <?php if ( $stock_qty ) : ?>max="<?php echo esc_attr( $stock_qty ); ?>"<?php endif; ?>
						       aria-label="<?php esc_attr_e( 'Quantity', 'woo-zee-plugin' ); ?>">
						<button type="button" class="wzp-pd__qty-btn" data-action="plus"
						        aria-label="<?php esc_attr_e( 'Increase quantity', 'woo-zee-plugin' ); ?>">+</button>
					</div>

					<button type="submit"
					        name="add-to-cart"
					        value="<?php echo esc_attr( $product_id ); ?>"
					        class="wzp-pd__atc-btn single_add_to_cart_button<?php echo ! $is_in_stock ? ' disabled' : ''; ?>"
					        <?php echo ! $is_in_stock ? 'disabled aria-disabled="true"' : ''; ?>>
						<?php echo $is_in_stock
							? esc_html__( 'Add To Bag', 'woo-zee-plugin' )
							: esc_html__( 'Out of Stock', 'woo-zee-plugin' ); ?>
					</button>

					<!-- Wishlist heart -->
					<button type="button"
					        class="wzp-pd__wishlist wzp-wishlist"
					        data-product-id="<?php echo esc_attr( $product_id ); ?>"
					        aria-pressed="false"
					        aria-label="<?php esc_attr_e( 'Add to wishlist', 'woo-zee-plugin' ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
					</button>
				</div>

				<!-- Buy It Now -->
				<?php if ( $is_in_stock ) : ?>
					<button type="button"
					        class="wzp-pd__buy-now"
					        data-checkout="<?php echo esc_url( $checkout_url ); ?>">
						<?php esc_html_e( 'Buy It Now', 'woo-zee-plugin' ); ?>
					</button>
				<?php endif; ?>

			</form>

			<!-- ── Meta ─────────────────────────────────────────────────── -->
			<dl class="wzp-pd__meta">
				<div class="wzp-pd__meta-row<?php echo $is_in_stock ? ' wzp-pd__meta-row--in-stock' : ' wzp-pd__meta-row--out-stock'; ?>">
					<dt><?php esc_html_e( 'Availability:', 'woo-zee-plugin' ); ?></dt>
					<dd><?php echo $is_in_stock ? esc_html__( 'In Stock', 'woo-zee-plugin' ) : esc_html__( 'Out of Stock', 'woo-zee-plugin' ); ?></dd>
				</div>

				<?php if ( $sku ) : ?>
					<div class="wzp-pd__meta-row">
						<dt><?php esc_html_e( 'SKU:', 'woo-zee-plugin' ); ?></dt>
						<dd><?php echo esc_html( $sku ); ?></dd>
					</div>
				<?php endif; ?>

				<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
					<div class="wzp-pd__meta-row">
						<dt><?php esc_html_e( 'Category:', 'woo-zee-plugin' ); ?></dt>
						<dd><?php echo wp_kses_post( $categories ); ?></dd>
					</div>
				<?php endif; ?>

				<?php if ( $tags && ! is_wp_error( $tags ) ) : ?>
					<div class="wzp-pd__meta-row">
						<dt><?php esc_html_e( 'Tags:', 'woo-zee-plugin' ); ?></dt>
						<dd><?php echo wp_kses_post( $tags ); ?></dd>
					</div>
				<?php endif; ?>
			</dl>

			<!-- ── Share ────────────────────────────────────────────────── -->
			<div class="wzp-pd__share">
				<button type="button"
				        class="wzp-pd__share-btn"
				        data-url="<?php echo esc_url( $permalink ); ?>"
				        data-title="<?php echo esc_attr( $title ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
					<?php esc_html_e( 'Share', 'woo-zee-plugin' ); ?>
				</button>
				<span class="wzp-pd__share-confirm" hidden>
					<?php esc_html_e( 'Link copied!', 'woo-zee-plugin' ); ?>
				</span>
			</div>

			<!-- ── Shipping info ─────────────────────────────────────────── -->
			<?php if ( ! empty( $shipping_lines ) ) : ?>
				<div class="wzp-pd__shipping">
					<?php foreach ( $shipping_lines as $line ) :
						if ( empty( $line['text'] ) ) { continue; }
					?>
						<div class="wzp-pd__shipping-item">
							<?php $ship_svg = wzp_pd_icon_svg( $line['icon'] ?? '' ); if ( $ship_svg ) : ?>
								<span class="wzp-pd__shipping-icon"><?php echo $ship_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<?php endif; ?>
							<span class="wzp-pd__shipping-text"><?php echo esc_html( $line['text'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- ── Benefits ──────────────────────────────────────────────── -->
			<?php if ( ! empty( $benefits ) ) : ?>
				<div class="wzp-pd__benefits">
					<h3 class="wzp-pd__benefits-heading"><?php esc_html_e( 'Benefits of Choosing Us', 'woo-zee-plugin' ); ?></h3>
					<div class="wzp-pd__benefits-grid">
						<?php foreach ( $benefits as $benefit ) :
							if ( empty( $benefit['title'] ) ) { continue; }
						?>
							<div class="wzp-pd__benefit">
								<?php $ben_svg = wzp_pd_icon_svg( $benefit['icon'] ?? '' ); if ( $ben_svg ) : ?>
									<div class="wzp-pd__benefit-icon"><?php echo $ben_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
								<?php endif; ?>
								<div class="wzp-pd__benefit-body">
									<strong class="wzp-pd__benefit-title"><?php echo esc_html( $benefit['title'] ); ?></strong>
									<?php if ( ! empty( $benefit['subtitle'] ) ) : ?>
										<p class="wzp-pd__benefit-subtitle"><?php echo esc_html( $benefit['subtitle'] ); ?></p>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

		</div>
		<!-- /info -->

	</div>
</section>
