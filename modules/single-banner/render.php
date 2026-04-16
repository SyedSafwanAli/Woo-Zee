<?php
/**
 * [wzp_single_banner] — Render function and shortcode entry point.
 *
 * Shortcode:  [wzp_single_banner]
 * Data source: get_option('wzp_single_banner_options', [])
 *
 * Options structure:
 *   image_id    (int)    — WP attachment ID for the background image
 *   label       (string) — small eyebrow text
 *   heading     (string) — main bold heading
 *   description (string) — short description paragraph
 *   btn_text    (string) — CTA button label
 *   btn_url     (string) — CTA button URL
 *   align       (string) — content alignment: left | center | right (default: left)
 *   height      (int)    — banner height in px (default: 420)
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wzp_render_single_banner' ) ) :

/**
 * Build and return the single banner HTML string.
 *
 * @param array $atts Shortcode attributes (currently unused).
 * @return string     Escaped HTML; empty string if no image and no heading.
 */
function wzp_render_single_banner( $atts ) {

	$opts = (array) get_option( 'wzp_single_banner_options', array() );

	$image_id = absint( $opts['image_id'] ?? 0 );
	$label    = sanitize_text_field( $opts['label']       ?? '' );
	$heading  = sanitize_text_field( $opts['heading']     ?? '' );
	$desc     = sanitize_textarea_field( $opts['description'] ?? '' );
	$btn_text = sanitize_text_field( $opts['btn_text']    ?? '' );
	$btn_url  = esc_url( $opts['btn_url']    ?? '' );
	$align    = in_array( $opts['align'] ?? 'left', array( 'left', 'center', 'right' ), true )
	            ? $opts['align']
	            : 'left';
	$height   = absint( $opts['height'] ?? 420 );
	if ( $height < 200 ) { $height = 200; }
	if ( $height > 900 ) { $height = 900; }

	$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';

	if ( ! $image_url && ! $heading ) {
		return '';
	}

	$align_cls = 'wzp-banner--' . esc_attr( $align );

	ob_start();
	?>
	<div class="wzp-module" data-wzp-module="single-banner">
		<div class="wzp-banner <?php echo esc_attr( $align_cls ); ?>"
		     style="height:<?php echo esc_attr( $height ); ?>px;<?php echo $image_url ? 'background-image:url(' . esc_url( $image_url ) . ')' : ''; ?>">

			<div class="wzp-banner__overlay"></div>

			<div class="wzp-banner__content">

				<?php if ( $label ) : ?>
					<span class="wzp-banner__label">
						<?php echo esc_html( $label ); ?>
					</span>
				<?php endif; ?>

				<?php if ( $heading ) : ?>
					<h2 class="wzp-banner__heading">
						<?php echo esc_html( $heading ); ?>
					</h2>
				<?php endif; ?>

				<?php if ( $desc ) : ?>
					<p class="wzp-banner__desc">
						<?php echo esc_html( $desc ); ?>
					</p>
				<?php endif; ?>

				<?php if ( $btn_text && $btn_url ) : ?>
					<a class="wzp-banner__btn"
					   href="<?php echo esc_url( $btn_url ); ?>">
						<?php echo esc_html( $btn_text ); ?>
					</a>
				<?php endif; ?>

			</div>

		</div>
	</div>
	<?php
	return ob_get_clean();
}

endif; // function_exists

echo wzp_render_single_banner( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
