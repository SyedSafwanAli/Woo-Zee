<?php
/**
 * [wzp_category_carousel] — Render function and shortcode entry point.
 *
 * Shortcode:  [wzp_category_carousel]
 *             [wzp_category_carousel per_view="6" orderby="name" hide_empty="true"]
 *
 * Displays WooCommerce product categories in a horizontal Swiper carousel
 * with thumbnail image + name per item and prev/next navigation.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wzp_render_category_carousel' ) ) :

/**
 * Build and return the category carousel HTML string.
 *
 * @param array $atts Shortcode attributes.
 * @return string     HTML output; empty string if no categories found.
 */
function wzp_render_category_carousel( $atts ) {

	if ( ! function_exists( 'WC' ) && ! class_exists( 'WooCommerce' ) ) {
		return '';
	}

	// ── Options: saved defaults → shortcode atts override ────────────────────
	$saved = wp_parse_args(
		(array) get_option( 'wzp_cat_carousel_options', array() ),
		array(
			'per_view'   => '7',
			'orderby'    => 'name',
			'hide_empty' => 'true',
			'icon_size'  => '48',
		)
	);

	$opts = shortcode_atts(
		array(
			'per_view'   => $saved['per_view'],
			'orderby'    => $saved['orderby'],
			'hide_empty' => $saved['hide_empty'],
			'icon_size'  => $saved['icon_size'],
		),
		$atts,
		'wzp_category_carousel'
	);

	// ── Validate ──────────────────────────────────────────────────────────────
	$allowed_orderby  = array( 'name', 'count', 'id', 'slug', 'menu_order' );
	$allowed_per_view = array( '4', '5', '6', '7', '8', '9', '10' );

	$orderby    = in_array( $opts['orderby'], $allowed_orderby, true ) ? $opts['orderby'] : 'name';
	$per_view   = in_array( $opts['per_view'], $allowed_per_view, true ) ? (int) $opts['per_view'] : 7;
	$hide_empty = 'false' !== $opts['hide_empty'];
	$icon_size  = min( 96, max( 24, absint( $opts['icon_size'] ) ) );

	// ── Fetch categories ──────────────────────────────────────────────────────
	$terms = get_terms( array(
		'taxonomy'   => 'product_cat',
		'orderby'    => $orderby,
		'order'      => 'ASC',
		'hide_empty' => $hide_empty,
		'number'     => 0, // all
	) );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	// Exclude the built-in "Uncategorized" bucket.
	$terms = array_values( array_filter( $terms, function ( $term ) {
		return 'uncategorized' !== $term->slug;
	} ) );

	if ( empty( $terms ) ) {
		return '';
	}

	// ── Swiper: register once per page ───────────────────────────────────────
	global $wzp_swiper_loaded;
	if ( empty( $wzp_swiper_loaded ) ) {
		if ( ! wp_script_is( 'swiper', 'registered' ) ) {
			wp_register_style(
				'swiper',
				'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
				array(),
				'11'
			);
			wp_register_script(
				'swiper',
				'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
				array(),
				'11',
				true
			);
		}
		wp_enqueue_style( 'swiper' );
		wp_enqueue_script( 'swiper' );
		$wzp_swiper_loaded = true;
	}

	// ── Unique instance ID ────────────────────────────────────────────────────
	$uid     = 'wzp-cat-carousel-' . uniqid();
	$fn_name = 'wzpInitCatCarousel_' . str_replace( '-', '_', $uid );

	// ── Render ────────────────────────────────────────────────────────────────
	ob_start();
	?>
	<div class="wzp-module wzp-module--cat-carousel" data-wzp-module="category-carousel">
		<div class="wzp-cat-carousel-wrap" id="<?php echo esc_attr( $uid ); ?>"
		     style="--wzp-cat-icon-size:<?php echo esc_attr( $icon_size ); ?>px">

			<div class="wzp-cat-carousel swiper">
				<div class="swiper-wrapper">

					<?php foreach ( $terms as $term ) : ?>
						<?php
						$term_link = get_term_link( $term );

						// Priority: 1) plugin-assigned icon (inline SVG or img), 2) WC thumbnail, 3) placeholder.
						$icon_html = wzp_get_category_icon_html( $term->term_id, 'wzp-cat-item__img' );

						if ( ! $icon_html ) {
							$thumbnail_id = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
							if ( $thumbnail_id ) {
								$thumb_url = wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' );
								if ( $thumb_url ) {
									$icon_html = '<img class="wzp-cat-item__img" src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $term->name ) . '" loading="lazy">';
								}
							}
						}
						?>
						<div class="swiper-slide wzp-cat-item">
							<a href="<?php echo esc_url( is_wp_error( $term_link ) ? '#' : $term_link ); ?>"
							   class="wzp-cat-item__link"
							   title="<?php echo esc_attr( $term->name ); ?>">

								<div class="wzp-cat-item__img-wrap">
									<?php if ( $icon_html ) : ?>
										<?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<?php else : ?>
										<span class="wzp-cat-item__placeholder" aria-hidden="true">
											<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
												<circle cx="12" cy="14" r="5"/>
												<path d="M9 14a3 3 0 0 1 6 0"/>
												<path d="M8 9l1.5 2M16 9l-1.5 2"/>
												<path d="M10 7.5C10 6.1 11 5 12 5s2 1.1 2 2.5"/>
											</svg>
										</span>
									<?php endif; ?>
								</div>

								<span class="wzp-cat-item__name">
									<?php echo esc_html( $term->name ); ?>
								</span>

							</a>
						</div>
					<?php endforeach; ?>

				</div>
			</div>

			<button class="wzp-cat-prev" aria-label="<?php esc_attr_e( 'Previous categories', 'woo-zee-plugin' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="15 18 9 12 15 6"/>
				</svg>
			</button>
			<button class="wzp-cat-next" aria-label="<?php esc_attr_e( 'Next categories', 'woo-zee-plugin' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<polyline points="9 18 15 12 9 6"/>
				</svg>
			</button>

		</div>
	</div>

	<script>
	( function () {
		function <?php echo esc_js( $fn_name ); ?>() {
			if ( typeof Swiper === 'undefined' ) { return; }

			new Swiper( '#<?php echo esc_js( $uid ); ?> .wzp-cat-carousel', {
				slidesPerView : 2,
				spaceBetween  : 12,
				grabCursor    : true,
				navigation    : {
					prevEl : '#<?php echo esc_js( $uid ); ?> .wzp-cat-prev',
					nextEl : '#<?php echo esc_js( $uid ); ?> .wzp-cat-next',
				},
				breakpoints : {
					480  : { slidesPerView: 3,  spaceBetween: 14 },
					640  : { slidesPerView: 4,  spaceBetween: 16 },
					900  : { slidesPerView: 6,  spaceBetween: 16 },
					1200 : { slidesPerView: <?php echo (int) $per_view; ?>, spaceBetween: 16 },
				},
				a11y : {
					prevSlideMessage : '<?php echo esc_js( __( 'Previous categories', 'woo-zee-plugin' ) ); ?>',
					nextSlideMessage : '<?php echo esc_js( __( 'Next categories',     'woo-zee-plugin' ) ); ?>',
				},
			} );
		}

		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', <?php echo esc_js( $fn_name ); ?> );
		} else {
			<?php echo esc_js( $fn_name ); ?>();
		}
	} )();
	</script>
	<?php
	return ob_get_clean();
}

endif;

// ── Entry point ───────────────────────────────────────────────────────────────
echo wzp_render_category_carousel( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
