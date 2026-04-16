<?php
/**
 * Shortcode registration and dispatch.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

class WZP_Shortcodes {

	/**
	 * Map of shortcode tag => module render file (relative to WZP_PATH).
	 *
	 * @var array<string,string>
	 */
	private static $shortcodes = array(
		'wzp_hero_slider'        => 'modules/hero-slider/render.php',
		'wzp_product_grid'       => 'modules/product-grid/render.php',
		'wzp_product_carousel'   => 'modules/product-carousel/render.php',
		'wzp_category_carousel'  => 'modules/category-carousel/render.php',
		'wzp_banner_cards'       => 'modules/banner-cards/render.php',
		'wzp_single_banner'      => 'modules/single-banner/render.php',
		'wzp_lookbook'           => 'modules/lookbook/render.php',
		'wzp_testimonials'       => 'modules/testimonials/render.php',
		'wzp_instagram_feed'     => 'modules/instagram-feed/render.php',
		'wzp_navbar'             => 'modules/navbar/render.php',
		'wzp_product_detail'     => 'modules/product-detail/render.php',
		'wzp_related_products'   => 'modules/related-products/render.php',
		'wzp_wishlist'           => 'modules/wishlist/render.php',
		'wzp_shop'               => 'modules/shop/render.php',
		'wzp_new_arrivals'       => 'modules/new-arrivals/render.php',
		'wzp_category_products'  => 'modules/category-products/render.php',
		'wzp_my_account'         => 'modules/my-account/render.php',
		'wzp_cart'               => 'modules/cart/render.php',
		'wzp_cart_suggestions'   => 'modules/cart-suggestions/render.php',
		'wzp_checkout'           => 'modules/checkout/render.php',
		'wzp_checkout_form'      => 'modules/checkout/render-form.php',
		'wzp_checkout_summary'   => 'modules/checkout/render-summary.php',
		'wzp_order_received'     => 'modules/order-received/render.php',
		'wzp_search_results'     => 'modules/search/render.php',
		'wzp_newsletter'         => 'modules/newsletter/render.php',
	);

	/**
	 * Register all shortcodes.
	 */
	public static function init() {
		foreach ( array_keys( self::$shortcodes ) as $tag ) {
			add_shortcode( $tag, array( __CLASS__, 'dispatch' ) );
		}
	}

	/**
	 * Universal dispatcher — loads the correct render.php for the shortcode
	 * currently being processed and returns its buffered output.
	 *
	 * Variables available inside each render.php:
	 *   $atts  — sanitised shortcode attributes (array)
	 *   $content — inner content (string|null)
	 *
	 * @param array       $raw_atts   Raw shortcode attributes.
	 * @param string|null $content    Enclosed content (if any).
	 * @param string      $tag        The shortcode tag being processed.
	 * @return string                 HTML output (always escaped inside render files).
	 */
	public static function dispatch( $raw_atts, $content, $tag ) {
		if ( ! isset( self::$shortcodes[ $tag ] ) ) {
			return '';
		}

		$render_file = WZP_PATH . self::$shortcodes[ $tag ];

		if ( ! file_exists( $render_file ) ) {
			return '';
		}

		// Sanitise every attribute value.
		$atts = is_array( $raw_atts )
			? WZP_Helpers::sanitize_array( $raw_atts )
			: array();

		$content = isset( $content ) ? wp_kses_post( $content ) : '';

		ob_start();
		include $render_file;
		return ob_get_clean();
	}
}
