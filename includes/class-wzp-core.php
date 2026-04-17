<?php
/**
 * Core loader — bootstraps all plugin modules.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

class WZP_Core {

	/**
	 * Initialise all modules.
	 * Called once on plugins_loaded (after WooCommerce check).
	 */
	public static function init() {
		// Load text domain for translations.
		load_plugin_textdomain(
			'woo-zee-plugin',
			false,
			dirname( WZP_BASENAME ) . '/languages'
		);

		// Override WooCommerce currency symbol to "Rs" (Pakistani Rupee).
		add_filter( 'woocommerce_currency_symbol', array( __CLASS__, 'set_currency_symbol' ), 10, 2 );

		WZP_Assets::init();
		WZP_Admin::init();
		WZP_Shortcodes::init();

		// AJAX product search
		add_action( 'wp_ajax_wzp_search',        array( __CLASS__, 'ajax_search' ) );
		add_action( 'wp_ajax_nopriv_wzp_search',  array( __CLASS__, 'ajax_search' ) );

		// Newsletter subscribe (public — logged-in and guests).
		add_action( 'wp_ajax_wzp_newsletter_subscribe',        array( __CLASS__, 'ajax_newsletter_subscribe' ) );
		add_action( 'wp_ajax_nopriv_wzp_newsletter_subscribe', array( __CLASS__, 'ajax_newsletter_subscribe' ) );

		// Ensure the newsletter table exists (safe to call every request; uses version flag).
		self::maybe_create_newsletter_table();
	}

	/**
	 * Create the wzp_newsletter_emails table if it doesn't exist yet.
	 * Uses a db-version option so dbDelta only runs once (or on upgrade).
	 */
	public static function maybe_create_newsletter_table() {
		$db_version = '1.0';

		if ( get_option( 'wzp_newsletter_db_version' ) === $db_version ) {
			return;
		}

		global $wpdb;

		$table_name      = $wpdb->prefix . 'wzp_newsletter_emails';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id          bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email       varchar(200)        NOT NULL,
			status      varchar(20)         NOT NULL DEFAULT 'subscribed',
			subscribed_at datetime           NOT NULL DEFAULT CURRENT_TIMESTAMP,
			ip_address  varchar(45)         NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			UNIQUE KEY email (email)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'wzp_newsletter_db_version', $db_version );
	}

	/**
	 * AJAX: newsletter subscription.
	 * Validates the email, prevents duplicates, stores in custom table.
	 */
	public static function ajax_newsletter_subscribe() {
		check_ajax_referer( 'wzp_newsletter_nonce', 'nonce' );

		// Honeypot — bots fill hidden fields, humans leave them empty.
		// Return fake success so bots think they succeeded.
		if ( ! empty( $_POST['wzp_confirm_email'] ) ) {
			wp_send_json_success( array( 'message' => __( 'Thank you for subscribing!', 'woo-zee-plugin' ) ) );
		}

		// Rate-limit: max 3 attempts per IP per hour.
		$ip         = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$rate_key   = 'wzp_nl_rate_' . md5( $ip );
		$rate_count = (int) get_transient( $rate_key );
		if ( $rate_count >= 3 ) {
			wp_send_json_error( array( 'message' => __( 'Too many attempts. Please try again later.', 'woo-zee-plugin' ) ) );
		}
		set_transient( $rate_key, $rate_count + 1, HOUR_IN_SECONDS );

		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'woo-zee-plugin' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'wzp_newsletter_emails';

		// Check for duplicate.
		$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s LIMIT 1", $email ) );
		if ( $existing ) {
			wp_send_json_error( array( 'message' => __( 'This email is already subscribed.', 'woo-zee-plugin' ) ) );
		}

		$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );

		$inserted = $wpdb->insert(
			$table,
			array(
				'email'         => $email,
				'status'        => 'subscribed',
				'subscribed_at' => current_time( 'mysql' ),
				'ip_address'    => $ip,
			),
			array( '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			wp_send_json_error( array( 'message' => __( 'Could not save your subscription. Please try again.', 'woo-zee-plugin' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Thank you for subscribing!', 'woo-zee-plugin' ) ) );
	}

	/**
	 * AJAX: live product search — returns JSON array of product data.
	 */
	public static function ajax_search() {
		check_ajax_referer( 'wzp_nonce', 'nonce' );

		$q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		if ( strlen( $q ) < 2 ) {
			wp_send_json_success( array() );
		}

		$query = new WP_Query( array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => 6,
			's'                   => $q,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => false,
		) );

		$results     = array();
		$search_url  = home_url( '/?s=' . rawurlencode( $q ) . '&post_type=product' );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product = wc_get_product( get_the_ID() );
				if ( ! $product ) { continue; }

				$thumb_id  = $product->get_image_id();
				$thumb_url = $thumb_id
					? wp_get_attachment_image_url( $thumb_id, 'thumbnail' )
					: wc_placeholder_img_src( 'thumbnail' );

				// First category only
				$cat_terms = get_the_terms( $product->get_id(), 'product_cat' );
				$cat_name  = '';
				if ( $cat_terms && ! is_wp_error( $cat_terms ) ) {
					foreach ( $cat_terms as $ct ) {
						if ( $ct->slug !== 'uncategorized' ) {
							$cat_name = $ct->name;
							break;
						}
					}
				}

				// Clean price: just the active price
				$price = $product->get_price();
				$price_formatted = $price !== ''
					? wc_price( $price )
					: $product->get_price_html();
				$price_clean = wp_strip_all_tags( $price_formatted );

				$results[] = array(
					'id'    => $product->get_id(),
					'name'  => $product->get_name(),
					'url'   => get_permalink( $product->get_id() ),
					'price' => $price_clean,
					'img'   => esc_url( $thumb_url ),
					'cat'   => $cat_name,
				);
			}
			wp_reset_postdata();
		}

		wp_send_json_success( array(
			'products'   => $results,
			'total'      => $query->found_posts,
			'search_url' => esc_url( $search_url ),
			'query'      => $q,
		) );
	}

	/**
	 * Replace whatever symbol WooCommerce uses with "Rs" for local SEO.
	 *
	 * @param string $symbol   Current symbol.
	 * @param string $currency Currency code.
	 * @return string
	 */
	public static function set_currency_symbol( $symbol, $currency ) {
		return 'Rs';
	}
}
