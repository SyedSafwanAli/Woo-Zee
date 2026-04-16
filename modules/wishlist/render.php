<?php
/**
 * [wzp_wishlist] — Wishlist page shortcode.
 *
 * Renders a container that JS populates via AJAX from localStorage.
 *
 * Usage: [wzp_wishlist empty_text="Your wishlist is empty."]
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

$atts = shortcode_atts(
	array(
		'empty_text' => __( 'Your wishlist is empty.', 'woo-zee-plugin' ),
	),
	$atts,
	'wzp_wishlist'
);

$empty_text = esc_html( sanitize_text_field( $atts['empty_text'] ) );
?>

<div class="wzp-module wzp-wishlist-page"
     data-wzp-module="wishlist"
     data-empty="<?php echo $empty_text; ?>">

	<div class="wzp-wishlist-page__wrap">
		<!-- Populated via JS / AJAX on page load -->
		<div class="wzp-wishlist-page__loading">
			<span></span><span></span><span></span>
		</div>
	</div>

</div>
