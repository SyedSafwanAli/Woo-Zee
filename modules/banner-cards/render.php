<?php
/**
 * [wzp_banner_cards] — 4-card banner grid.
 *
 * Included by WZP_Shortcodes::dispatch(). Variables in scope:
 *   $atts    — shortcode attributes (array)
 *   $content — enclosed content (string)
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

$cards = (array) get_option( 'wzp_banner_cards', array() );

// Ensure exactly 4 slots.
$cards = array_replace(
	array_fill( 0, 4, array() ),
	array_slice( $cards, 0, 4 )
);

// Bail if no card has an image.
$has_content = false;
foreach ( $cards as $card ) {
	if ( ! empty( $card['image_id'] ) ) {
		$has_content = true;
		break;
	}
}

if ( ! $has_content ) {
	return;
}
?>
<div class="wzp-module wzp-module--banner-cards">
	<div class="wzp-banner-cards">

		<?php foreach ( $cards as $card ) :
			$image_id = absint( $card['image_id'] ?? 0 );
			$heading  = $card['heading']  ?? '';
			$btn_text = $card['btn_text'] ?? '';
			$btn_url  = $card['btn_url']  ?? '';
			$btn_icon = $card['btn_icon'] ?? '↗';

			if ( ! $image_id ) { continue; }

			$img_url = wp_get_attachment_image_url( $image_id, 'large' );
			if ( ! $img_url ) { continue; }
		?>
		<div class="wzp-banner-card">

			<div class="wzp-banner-card__bg"
			     style="background-image:url('<?php echo esc_url( $img_url ); ?>');"
			     role="img"
			     aria-label="<?php echo $heading ? esc_attr( $heading ) : esc_attr__( 'Banner image', 'woo-zee-plugin' ); ?>">
			</div>

			<div class="wzp-banner-card__overlay" aria-hidden="true"></div>

			<div class="wzp-banner-card__body">
				<?php if ( $heading ) : ?>
				<h3 class="wzp-banner-card__heading"><?php echo esc_html( $heading ); ?></h3>
				<?php endif; ?>

				<?php if ( $btn_text && $btn_url ) : ?>
				<a href="<?php echo esc_url( $btn_url ); ?>"
				   class="wzp-banner-card__link">
					<?php echo esc_html( $btn_text ); ?>
					<?php if ( $btn_icon ) : ?>
					<span class="wzp-banner-card__arrow" aria-hidden="true"><?php echo esc_html( $btn_icon ); ?></span>
					<?php endif; ?>
				</a>
				<?php endif; ?>
			</div>

		</div>
		<?php endforeach; ?>

	</div>
</div>
