<?php
/**
 * [wzp_product_carousel] — Render function and shortcode entry point.
 *
 * Shortcode: [wzp_product_carousel category="" count="8" autoplay="true" speed="3000" per_view="3"]
 *
 * Inline attributes override the saved dashboard defaults stored in:
 *   wp_options key: wzp_product_carousel_options
 *
 * Requires: Swiper.js — enqueued globally by WZP_Assets::enqueue_frontend().
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

require_once WZP_PATH . 'modules/product-grid/card.php';

if ( ! function_exists( 'wzp_render_product_carousel' ) ) :

/**
 * Build and return the HTML string for the product carousel.
 *
 * @param array $atts Raw (already text-sanitised) shortcode attributes.
 * @return string     Escaped HTML + inline Swiper init script.
 */
function wzp_render_product_carousel( $atts ) {

	// ── Step 1: Merge saved dashboard defaults with inline atts ──────────────
	$saved = (array) get_option( 'wzp_product_carousel_options', array() );

	$option_defaults = wp_parse_args(
		$saved,
		array(
			'category' => '',
			'count'    => '10',
			'per_view' => '5',
			'autoplay' => 'true',
			'speed'    => '3000',
		)
	);

	// Inline shortcode atts win over saved defaults.
	$atts = shortcode_atts( $option_defaults, $atts, 'wzp_product_carousel' );

	// ── Sanitise each value with strict types ─────────────────────────────────
	$category = sanitize_text_field( $atts['category'] );
	$count    = min( 24, max( 1, absint( $atts['count'] ) ) );
	$per_view = min( 6,  max( 1, absint( $atts['per_view'] ) ) );
	$autoplay = ( 'true' === $atts['autoplay'] ) ? 'true' : 'false';
	$speed    = min( 10000, max( 500, absint( $atts['speed'] ) ) );

	// ── Step 2: WP_Query — same pattern as product-grid ──────────────────────
	$query_args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => $count,
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
	);

	if ( ! empty( $category ) ) {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $category,
			),
		);
	}

	$query = new WP_Query( $query_args );

	if ( ! $query->have_posts() ) {
		return '';
	}

	// ── Step 3: Unique instance ID ────────────────────────────────────────────
	$uid    = 'wzp-carousel-' . uniqid();
	$uid_pg = $uid . '-pg'; // external pagination element ID

	// ── Step 4 + 5: Buffer HTML + inline Swiper init ──────────────────────────
	ob_start();
	?>
	<div class="wzp-module" data-wzp-module="product-carousel">
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

	<?php
	$autoplay_config = ( 'true' === $autoplay )
		? '{ delay: ' . $speed . ', disableOnInteraction: false, pauseOnMouseEnter: true }'
		: 'false';
	?>
	<script>
	( function() {
		function wzpInitCarousel_<?php echo esc_js( str_replace( '-', '_', $uid ) ); ?>() {
			if ( typeof Swiper === 'undefined' ) { return; }
			new Swiper( '#<?php echo esc_js( $uid ); ?>', {
				slidesPerView  : 1,
				slidesPerGroup : 1,
				spaceBetween   : 20,
				loop           : true,
				autoplay       : <?php echo $autoplay_config; // phpcs:ignore — typed above ?>,
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
			document.addEventListener( 'DOMContentLoaded', wzpInitCarousel_<?php echo esc_js( str_replace( '-', '_', $uid ) ); ?> );
		} else {
			wzpInitCarousel_<?php echo esc_js( str_replace( '-', '_', $uid ) ); ?>();
		}
	} )();
	</script>
	<?php
	return ob_get_clean();
}

endif; // function_exists

// ── Entry point ───────────────────────────────────────────────────────────────
echo wzp_render_product_carousel( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
