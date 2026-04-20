<?php
/**
 * [wzp_hero_slider] — Render function and shortcode entry point.
 *
 * Shortcode:  [wzp_hero_slider]
 * Data source: get_option('wzp_hero_slides', [])
 *
 * Slide structure stored in wp_options:
 *   image_id    (int)    — WP attachment ID
 *   label       (string) — small eyebrow text above heading
 *   heading     (string) — main H1
 *   description (string) — short paragraph
 *   btn_text    (string) — CTA button label
 *   btn_url     (string) — CTA button URL
 *
 * Returns '' when no slides are saved (no markup emitted).
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wzp_render_hero_slider' ) ) :

/**
 * Build and return the hero-slider HTML string.
 *
 * @param array $atts Shortcode attributes (currently unused — all data from options).
 * @return string     Escaped HTML + inline Swiper init; empty string if no slides.
 */
function wzp_render_hero_slider( $atts ) {

	// ── Load slides from wp_options ───────────────────────────────────────────
	$raw_slides = get_option( 'wzp_hero_slides', array() );

	if ( ! is_array( $raw_slides ) || empty( $raw_slides ) ) {
		return '';
	}

	// Filter out any empty/corrupt entries saved before data was added.
	$slides = array_values(
		array_filter( $raw_slides, function ( $slide ) {
			return is_array( $slide )
				&& ( ! empty( $slide['image_id'] ) || ! empty( $slide['heading'] ) );
		} )
	);

	if ( empty( $slides ) ) {
		return '';
	}

	// ── Render ────────────────────────────────────────────────────────────────
	ob_start();
	?>
	<div class="wzp-module" data-wzp-module="hero-slider">
		<section class="wzp-hero swiper" id="wzp-hero-slider"
		         aria-label="<?php esc_attr_e( 'Hero image slider', 'woo-zee-plugin' ); ?>">

			<div class="swiper-wrapper">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<?php
					// ── Sanitise each field at render time ─────────────────────
					$image_id   = absint( $slide['image_id']    ?? 0 );
					$label      = sanitize_text_field( $slide['label']       ?? '' );
					$heading    = sanitize_text_field( $slide['heading']     ?? '' );
					$desc       = sanitize_textarea_field( $slide['description'] ?? '' );
					$btn_text   = trim( preg_replace( '/[\x{2197}\x{2192}\x{279C}\x{27A4}\x{2191}\x{2198}↗→]+\s*$/u', '', sanitize_text_field( $slide['btn_text'] ?? '' ) ) );
					$btn_url    = esc_url( $slide['btn_url']    ?? '' );

					$image_url = $image_id
						? wp_get_attachment_image_url( $image_id, 'full' )
						: '';
					?>
					<div class="swiper-slide wzp-hero__slide"
					     role="group"
					     aria-label="<?php echo esc_attr( sprintf(
					     	/* translators: 1: current slide, 2: total slides */
					     	__( 'Slide %1$d of %2$d', 'woo-zee-plugin' ),
					     	$index + 1,
					     	count( $slides )
					     ) ); ?>">

						<?php if ( $image_url ) : ?>
							<div class="wzp-hero__bg"
							     style="background-image:url(<?php echo esc_url( $image_url ); ?>)"
							     aria-hidden="true">
							</div>
							<?php if ( 0 === $index ) : ?>
								<img src="<?php echo esc_url( $image_url ); ?>"
								     alt="<?php echo esc_attr( $heading ?: $label ); ?>"
								     fetchpriority="high"
								     decoding="async"
								     class="wzp-hero__lcp-img"
								     style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;overflow:hidden;">
							<?php else : ?>
								<img src="<?php echo esc_url( $image_url ); ?>"
								     alt="<?php echo esc_attr( $heading ?: $label ); ?>"
								     loading="lazy"
								     decoding="async"
								     class="wzp-hero__lcp-img"
								     style="position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;overflow:hidden;">
							<?php endif; ?>
						<?php endif; ?>

						<div class="wzp-hero__overlay" aria-hidden="true"></div>

						<div class="wzp-hero__content">
							<?php if ( $label ) : ?>
								<span class="wzp-hero__label">
									<?php echo esc_html( $label ); ?>
								</span>
							<?php endif; ?>

							<?php if ( $heading ) : ?>
								<h1 class="wzp-hero__heading">
									<?php echo esc_html( $heading ); ?>
								</h1>
							<?php endif; ?>

							<?php if ( $desc ) : ?>
								<p class="wzp-hero__desc">
									<?php echo esc_html( $desc ); ?>
								</p>
							<?php endif; ?>

							<?php if ( $btn_text && $btn_url ) : ?>
								<a class="wzp-btn wzp-hero__btn"
								   href="<?php echo esc_url( $btn_url ); ?>">
									<?php echo esc_html( $btn_text ); ?>
									<span aria-hidden="true">↗</span>
								</a>
							<?php endif; ?>
						</div>

					</div>
				<?php endforeach; ?>
			</div>

			<div class="swiper-pagination"></div>

		</section>
	</div>

	<?php
	/*
	 * Inline Swiper init — scoped inside an IIFE.
	 * The hero slider uses a fixed ID (#wzp-hero-slider) because only one
	 * instance is expected per page. Pagination/navigation selectors are
	 * scoped to that ID to be safe.
	 */
	?>
	<script>
	( function () {
		function wzpInitHeroSlider() {
			if ( typeof Swiper === 'undefined' ) { return; }
			new Swiper( '#wzp-hero-slider', {
				effect     : 'fade',
				loop       : true,
				autoplay   : { delay: 5000, disableOnInteraction: false },
				pagination : {
					el        : '#wzp-hero-slider .swiper-pagination',
					clickable : true
				},
				a11y       : {
					prevSlideMessage : '<?php echo esc_js( __( 'Previous slide', 'woo-zee-plugin' ) ); ?>',
					nextSlideMessage : '<?php echo esc_js( __( 'Next slide',     'woo-zee-plugin' ) ); ?>'
				}
			} );
		}

		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', wzpInitHeroSlider );
		} else {
			wzpInitHeroSlider();
		}
	} )();
	</script>
	<?php
	return ob_get_clean();
}

endif; // function_exists

// ── Entry point (called by WZP_Shortcodes::dispatch) ─────────────────────────
echo wzp_render_hero_slider( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
