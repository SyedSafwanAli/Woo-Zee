<?php
/**
 * [wzp_testimonials] — Render function and shortcode entry point.
 *
 * Shortcode:  [wzp_testimonials]
 * Data source: get_option('wzp_testimonials_data', [])
 *
 * Each entry:
 *   avatar_id (int)    — WP attachment ID
 *   name      (string) — reviewer name
 *   location  (string) — reviewer location
 *   review    (string) — review body text
 *
 * Returns '' when no testimonials are saved.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wzp_render_testimonials' ) ) :

/**
 * Build and return the testimonials HTML string.
 *
 * @param array $atts Shortcode attributes (currently unused).
 * @return string     Escaped HTML + inline Swiper init; '' if no data.
 */
function wzp_render_testimonials( $atts ) {

	// ── Load data ─────────────────────────────────────────────────────────────
	$raw = get_option( 'wzp_testimonials_data', array() );

	if ( ! is_array( $raw ) || empty( $raw ) ) {
		return '';
	}

	// Filter out corrupt entries — require at least a name or a review.
	$entries = array_values(
		array_filter( $raw, function ( $entry ) {
			return is_array( $entry )
				&& ( ! empty( $entry['name'] ) || ! empty( $entry['review'] ) );
		} )
	);

	if ( empty( $entries ) ) {
		return '';
	}

	// ── Review + AggregateRating JSON-LD ─────────────────────────────────────
	$review_items = array();
	foreach ( $entries as $entry ) {
		$r_name   = sanitize_text_field( $entry['name']   ?? '' );
		$r_review = sanitize_textarea_field( $entry['review'] ?? '' );
		if ( ! $r_name || ! $r_review ) { continue; }
		$review_items[] = array(
			'@type'         => 'Review',
			'reviewRating'  => array(
				'@type'       => 'Rating',
				'ratingValue' => '5',
				'bestRating'  => '5',
				'worstRating' => '1',
			),
			'author'        => array( '@type' => 'Person', 'name' => $r_name ),
			'reviewBody'    => $r_review,
		);
	}

	if ( ! empty( $review_items ) ) {
		$review_schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'LocalBusiness',
			'name'            => get_bloginfo( 'name' ),
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => '5',
				'bestRating'  => '5',
				'reviewCount' => count( $review_items ),
			),
			'review'          => $review_items,
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $review_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	// ── Render ────────────────────────────────────────────────────────────────
	ob_start();
	?>
	<div class="wzp-module" data-wzp-module="testimonials">
		<section class="wzp-testimonials"
		         aria-label="<?php esc_attr_e( 'Customer reviews', 'woo-zee-plugin' ); ?>">

			<div class="wzp-testimonials__header">
				<!-- <span class="wzp-label">
					<?php esc_html_e( 'TESTIMONIALS', 'woo-zee-plugin' ); ?>
				</span> -->
				<!-- <h2><?php esc_html_e( 'Our Customers Reviews', 'woo-zee-plugin' ); ?></h2> -->
			</div>

			<div class="swiper wzp-testimonials__slider"
			     id="wzp-testimonials-slider">

				<div class="swiper-wrapper">
					<?php foreach ( $entries as $entry ) : ?>
						<?php
						// Sanitise at render time.
						$avatar_id = absint( $entry['avatar_id'] ?? 0 );
						$name      = sanitize_text_field( $entry['name']     ?? '' );
						$location  = sanitize_text_field( $entry['location'] ?? '' );
						$review    = sanitize_textarea_field( $entry['review']   ?? '' );

						$avatar_url = $avatar_id
							? wp_get_attachment_image_url( $avatar_id, 'thumbnail' )
							: get_avatar_url( 0, array( 'size' => 64, 'default' => 'mysteryman' ) );
						?>
						<div class="swiper-slide">
							<div class="wzp-testimonial-card">

								<div class="wzp-testimonial-card__stars" aria-label="<?php esc_attr_e( '5 out of 5 stars', 'woo-zee-plugin' ); ?>">
									<span aria-hidden="true">★★★★★</span>
								</div>

								<?php if ( $review ) : ?>
									<p class="wzp-testimonial-card__review">
										<?php echo esc_html( $review ); ?>
									</p>
								<?php endif; ?>

								<div class="wzp-testimonial-card__author">
									<img src="<?php echo esc_url( $avatar_url ); ?>"
									     alt="<?php echo esc_attr( $name ); ?>"
									     class="wzp-testimonial-card__avatar"
									     width="48"
									     height="48"
									     loading="lazy">
									<div class="wzp-testimonial-card__author-info">
										<?php if ( $name ) : ?>
											<strong class="wzp-testimonial-card__name">
												<?php echo esc_html( $name ); ?>
											</strong>
										<?php endif; ?>
										<?php if ( $location ) : ?>
											<span class="wzp-testimonial-card__location">
												<?php echo esc_html( $location ); ?>
											</span>
										<?php endif; ?>
									</div>
								</div>

							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="swiper-pagination"></div>

			</div><?php /* /.wzp-testimonials__slider */ ?>

		</section>
	</div>

	<?php
	/*
	 * Inline Swiper init — IIFE-scoped.
	 * Pagination selector is scoped to #wzp-testimonials-slider to prevent
	 * conflicts if multiple Swiper instances appear on the same page.
	 */
	?>
	<script>
	( function () {
		function wzpInitTestimonialsSlider() {
			if ( typeof Swiper === 'undefined' ) { return; }
			new Swiper( '#wzp-testimonials-slider', {
				slidesPerView  : 1,
				spaceBetween   : 20,
				pagination     : {
					el        : '#wzp-testimonials-slider .swiper-pagination',
					clickable : true
				},
				breakpoints    : {
					640  : { slidesPerView: 2 },
					1024 : { slidesPerView: 4 }
				},
				a11y           : {
					prevSlideMessage : '<?php echo esc_js( __( 'Previous review', 'woo-zee-plugin' ) ); ?>',
					nextSlideMessage : '<?php echo esc_js( __( 'Next review',     'woo-zee-plugin' ) ); ?>'
				}
			} );
		}

		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', wzpInitTestimonialsSlider );
		} else {
			wzpInitTestimonialsSlider();
		}
	} )();
	</script>
	<?php
	return ob_get_clean();
}

endif; // function_exists

// ── Entry point ───────────────────────────────────────────────────────────────
echo wzp_render_testimonials( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
