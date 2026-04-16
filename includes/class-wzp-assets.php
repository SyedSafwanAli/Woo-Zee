<?php
/**
 * Asset registration and enqueueing (frontend).
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

class WZP_Assets {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend' ) );
		add_action( 'wp_footer',          array( __CLASS__, 'render_mobile_nav' ) );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 * Uses a global flag to prevent Swiper being registered more than once
	 * (e.g. if another theme/plugin already loaded it).
	 */
	public static function enqueue_frontend() {
		global $wzp_swiper_loaded;

		// ── Swiper.js (CDN) ───────────────────────────────────────────────────
		if ( empty( $wzp_swiper_loaded ) && ! wp_script_is( 'swiper', 'registered' ) ) {
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
				true // load in footer
			);
			$wzp_swiper_loaded = true;
		}

		wp_enqueue_style( 'swiper' );
		wp_enqueue_script( 'swiper' );

		// ── Plugin stylesheet ─────────────────────────────────────────────────
		wp_enqueue_style(
			'woo-zee-style',
			WZP_URL . 'assets/css/woo-zee-style.css',
			array( 'swiper' ),
			WZP_VERSION
		);

		// ── Shop page stylesheet ──────────────────────────────────────────────
		wp_enqueue_style(
			'woo-zee-shop',
			WZP_URL . 'assets/css/woo-zee-shop.css',
			array( 'woo-zee-style' ),
			WZP_VERSION
		);

		// ── My Account stylesheet ─────────────────────────────────────────────
		wp_enqueue_style(
			'woo-zee-account',
			WZP_URL . 'modules/my-account/account.css',
			array( 'woo-zee-style' ),
			WZP_VERSION
		);

		// ── Cart stylesheet (WC native cart override) ────────────────────────
		wp_enqueue_style(
			'woo-zee-cart',
			WZP_URL . 'assets/css/woo-zee-cart.css',
			array( 'woo-zee-style' ),
			WZP_VERSION
		);

		// ── Checkout stylesheet ───────────────────────────────────────────────
		wp_enqueue_style(
			'woo-zee-checkout',
			WZP_URL . 'assets/css/woo-zee-checkout.css',
			array( 'woo-zee-style' ),
			WZP_VERSION
		);

		// ── Order received stylesheet ─────────────────────────────────────────
		wp_enqueue_style(
			'woo-zee-order-received',
			WZP_URL . 'assets/css/woo-zee-order-received.css',
			array( 'woo-zee-style' ),
			WZP_VERSION
		);

		// ── Mobile bottom nav stylesheet ──────────────────────────────────────
		wp_enqueue_style(
			'woo-zee-mobile-nav',
			WZP_URL . 'assets/css/woo-zee-mobile-nav.css',
			array( 'woo-zee-style' ),
			WZP_VERSION
		);

		// ── Plugin script (footer) ────────────────────────────────────────────
		wp_enqueue_script(
			'woo-zee-script',
			WZP_URL . 'assets/js/woo-zee-script.js',
			array( 'jquery', 'swiper' ),
			WZP_VERSION,
			true // load in footer
		);

		// ── Card Style — output saved colours as CSS custom properties ────────
		$cs = wp_parse_args(
			(array) get_option( 'wzp_card_style_options', array() ),
			array(
				'primary'     => '#1a1a1a',
				'secondary'   => '#4a4a4a',
				'accent'      => '#c9a96e',
				'surface'     => '#ffffff',
				'surface_alt' => '#f7f6f4',
				'border'      => '#e8e4df',
				'title_size'  => 15,
				'price_size'  => 14,
				'cat_size'    => 10,
			)
		);

		$inline_css = sprintf(
			':root{--wzp-primary:%s;--wzp-secondary:%s;--wzp-accent:%s;--wzp-surface:%s;--wzp-surface-alt:%s;--wzp-border:%s;--wzp-card-title-size:%dpx;--wzp-card-price-size:%dpx;--wzp-card-cat-size:%dpx;}',
			esc_attr( $cs['primary'] ),
			esc_attr( $cs['secondary'] ),
			esc_attr( $cs['accent'] ),
			esc_attr( $cs['surface'] ),
			esc_attr( $cs['surface_alt'] ),
			esc_attr( $cs['border'] ),
			absint( $cs['title_size'] ),
			absint( $cs['price_size'] ),
			absint( $cs['cat_size'] )
		);

		wp_add_inline_style( 'woo-zee-style', $inline_css );

		// Pass mobile nav URLs to JS for active-state detection.
		wp_localize_script(
			'woo-zee-script',
			'wzpMobileNav',
			array(
				'homeUrl'     => esc_url( home_url( '/' ) ),
				'shopUrl'     => esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ),
				'wishlistUrl' => esc_url( home_url( '/wishlist/' ) ),
				'accountUrl'  => esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ) ),
			)
		);

		// Pass useful data to JS.
		wp_localize_script(
			'woo-zee-script',
			'wzpData',
			array(
				'ajaxUrl'         => esc_url( admin_url( 'admin-ajax.php' ) ),
				'nonce'           => wp_create_nonce( 'wzp_nonce' ),
				'cartNonce'       => wp_create_nonce( 'wzp_cart_nonce' ),
				'storeApiNonce'   => wp_create_nonce( 'wc_store_api' ),
				'storeApiUrl'     => esc_url( rest_url( 'wc/store/v1/' ) ),
				'cartUrl'         => function_exists( 'wc_get_cart_url' ) ? esc_url( wc_get_cart_url() ) : esc_url( home_url( '/cart/' ) ),
				'shopUrl'         => function_exists( 'wc_get_page_permalink' ) ? esc_url( wc_get_page_permalink( 'shop' ) ) : esc_url( home_url( '/shop/' ) ),
				'viewCartText'    => __( 'View Cart', 'woo-zee-plugin' ),
				'loginUrl'        => esc_url( wc_get_page_permalink( 'myaccount' ) ),
				'noResultsText'   => __( 'No products found', 'woo-zee-plugin' ),
				'viewAllText'     => __( 'View all %d results', 'woo-zee-plugin' ),
			)
		);
	}

	/**
	 * Output the mobile bottom navigation bar in wp_footer.
	 */
	public static function render_mobile_nav() {
		if ( is_admin() ) { return; }

		$home_url     = esc_url( home_url( '/' ) );
		$shop_url     = esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) );
		$wishlist_url = esc_url( home_url( '/wishlist/' ) );
		$account_url  = esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ) );

		// Wishlist count (YITH Wishlist or session-based).
		$wishlist_count = 0;
		if ( function_exists( 'YITH_WCWL' ) ) {
			$wishlist_count = YITH_WCWL()->count_products();
		} elseif ( isset( $_SESSION['wzp_wishlist'] ) && is_array( $_SESSION['wzp_wishlist'] ) ) {
			$wishlist_count = count( $_SESSION['wzp_wishlist'] );
		}
		?>
		<nav class="wzp-mobile-nav" aria-label="<?php esc_attr_e( 'Mobile navigation', 'woo-zee-plugin' ); ?>">
			<ul class="wzp-mobile-nav__list">

				<!-- Home -->
				<li class="wzp-mobile-nav__item">
					<a href="<?php echo $home_url; ?>" class="wzp-mobile-nav__link" data-nav="home">
						<span class="wzp-mobile-nav__icon">
							<svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<path d="M3 9.5L12 3l9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9.5z"/>
								<polyline points="9 21 9 12 15 12 15 21"/>
							</svg>
						</span>
						<span><?php esc_html_e( 'Home', 'woo-zee-plugin' ); ?></span>
					</a>
				</li>

				<!-- Shopping -->
				<li class="wzp-mobile-nav__item">
					<a href="<?php echo $shop_url; ?>" class="wzp-mobile-nav__link" data-nav="shop">
						<span class="wzp-mobile-nav__icon">
							<svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<rect x="3" y="3" width="7" height="7" rx="1"/>
								<rect x="14" y="3" width="7" height="7" rx="1"/>
								<rect x="3" y="14" width="7" height="7" rx="1"/>
								<rect x="14" y="14" width="7" height="7" rx="1"/>
							</svg>
						</span>
						<span><?php esc_html_e( 'Shopping', 'woo-zee-plugin' ); ?></span>
					</a>
				</li>

				<!-- Wishlist -->
				<li class="wzp-mobile-nav__item">
					<a href="<?php echo $wishlist_url; ?>" class="wzp-mobile-nav__link" data-nav="wishlist">
						<span class="wzp-mobile-nav__icon">
							<svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
							</svg>
							<span class="wzp-mobile-nav__badge wzp-nb__wishlist-count"></span>
						</span>
						<span><?php esc_html_e( 'Wishlist', 'woo-zee-plugin' ); ?></span>
					</a>
				</li>

				<!-- Account -->
				<li class="wzp-mobile-nav__item">
					<a href="<?php echo $account_url; ?>" class="wzp-mobile-nav__link" data-nav="account">
						<span class="wzp-mobile-nav__icon">
							<svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
								<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
								<circle cx="12" cy="7" r="4"/>
							</svg>
						</span>
						<span><?php esc_html_e( 'Account', 'woo-zee-plugin' ); ?></span>
					</a>
				</li>

			</ul>
		</nav>
		<?php
	}
}
