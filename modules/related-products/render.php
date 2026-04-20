<?php
/**
 * [wzp_related_products] — Related products carousel for product detail pages.
 *
 * Usage: [wzp_related_products count="8" per_view="4" title="You May Also Like"]
 *
 * Attributes:
 *   count    int    Number of related products to fetch (default 8, max 24).
 *   per_view int    Slides visible on desktop (default 4, max 6).
 *   title    string Section heading text (default "You May Also Like").
 *
 * Requires: Swiper.js — enqueued globally by WZP_Assets::enqueue_frontend().
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

require_once WZP_PATH . 'modules/product-grid/card.php';

if ( ! function_exists( 'wzp_render_related_products' ) ) :

/**
 * Build and return the HTML string for the related products carousel.
 *
 * @param array $atts Sanitised shortcode attributes.
 * @return string
 */
function wzp_render_related_products( $atts ) {

	$atts = shortcode_atts(
		array(
			'count'    => '8',
			'per_view' => '4',
			'title'    => '',
		),
		$atts,
		'wzp_related_products'
	);

	$count    = min( 24, max( 1, absint( $atts['count'] ) ) );
	$per_view = min( 6,  max( 1, absint( $atts['per_view'] ) ) );
	$title    = sanitize_text_field( $atts['title'] );

	// ── Detect current product ────────────────────────────────────────────────

	$current_id = 0;

	if ( function_exists( 'WC' ) && is_singular( 'product' ) ) {
		$current_id = get_the_ID();
	} elseif ( function_exists( 'WC' ) && WC()->product ) {
		$current_id = WC()->product->get_id();
	}

	// ── Build query — same categories as current product ─────────────────────

	$query_args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'orderby'             => 'rand',
		'ignore_sticky_posts' => true,
	);

	if ( $current_id ) {
		$query_args['post__not_in'] = array( $current_id );

		$cat_ids = wc_get_product_term_ids( $current_id, 'product_cat' );
		$tag_ids = wc_get_product_term_ids( $current_id, 'product_tag' );

		$tax_queries = array();

		if ( ! empty( $cat_ids ) ) {
			$tax_queries[] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $cat_ids,
			);
		}

		if ( ! empty( $tag_ids ) ) {
			$tax_queries[] = array(
				'taxonomy' => 'product_tag',
				'field'    => 'term_id',
				'terms'    => $tag_ids,
			);
		}

		if ( ! empty( $tax_queries ) ) {
			$query_args['tax_query'] = array_merge(
				array( 'relation' => 'OR' ),
				$tax_queries
			);
		}
	}

	$query = new WP_Query( $query_args );

	if ( ! $query->have_posts() ) {
		return '';
	}

	// ── ItemList JSON-LD ──────────────────────────────────────────────────────
	$list_items = array();
	$position   = 1;
	foreach ( $query->posts as $post ) {
		$rp = wc_get_product( $post->ID );
		if ( ! $rp instanceof WC_Product ) { continue; }
		$rp_img_id  = $rp->get_image_id();
		$rp_img_url = $rp_img_id ? wp_get_attachment_image_url( $rp_img_id, 'large' ) : '';
		$item       = array(
			'@type'    => 'ListItem',
			'position' => $position++,
			'name'     => $rp->get_name(),
			'url'      => get_permalink( $rp->get_id() ),
		);
		if ( $rp_img_url ) {
			$item['image'] = $rp_img_url;
		}
		$list_items[] = $item;
	}

	if ( ! empty( $list_items ) ) {
		$list_schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'ItemList',
			'name'            => $title ?: __( 'You May Also Like', 'woo-zee-plugin' ),
			'itemListElement' => $list_items,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $list_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	// ── Unique instance ID ────────────────────────────────────────────────────

	$uid     = 'wzp-related-' . uniqid();
	$uid_pg  = $uid . '-pg';
	$fn_name = 'wzpInitRelated_' . str_replace( '-', '_', $uid );

	// ── HTML ──────────────────────────────────────────────────────────────────

	ob_start();
	?>
	<div class="wzp-module wzp-related-products" data-wzp-module="related-products">

		<?php if ( $title ) : ?>
		<h2 class="wzp-related-products__title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>

		<div class="wzp-carousel swiper" id="<?php echo esc_attr( $uid ); ?>">
			<div class="swiper-wrapper">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					?>
					<div class="swiper-slide">
						<?php echo wzp_render_product_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
			</div>
		</div>
		<div class="wzp-carousel-pagination" id="<?php echo esc_attr( $uid_pg ); ?>"></div>

	</div>

	<script>
	( function() {
		function <?php echo esc_js( $fn_name ); ?>() {
			if ( typeof Swiper === 'undefined' ) { return; }
			new Swiper( '#<?php echo esc_js( $uid ); ?>', {
				slidesPerView  : 1,
				slidesPerGroup : 1,
				spaceBetween   : 20,
				loop           : true,
				autoplay       : { delay: 4000, disableOnInteraction: false, pauseOnMouseEnter: true },
				pagination     : {
					el        : '#<?php echo esc_js( $uid_pg ); ?>',
					clickable : true
				},
				breakpoints    : {
					480  : { slidesPerView: 2, slidesPerGroup: 2, spaceBetween: 16 },
					768  : { slidesPerView: 3, slidesPerGroup: 3, spaceBetween: 20 },
					1024 : { slidesPerView: <?php echo (int) $per_view; ?>, slidesPerGroup: <?php echo (int) $per_view; ?>, spaceBetween: 20 }
				},
				a11y : {
					prevSlideMessage : '<?php echo esc_js( __( 'Previous slide', 'woo-zee-plugin' ) ); ?>',
					nextSlideMessage : '<?php echo esc_js( __( 'Next slide', 'woo-zee-plugin' ) ); ?>'
				}
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
echo wzp_render_related_products( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
