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

		// Performance: preconnect / dns-prefetch hints
		add_action( 'wp_head', array( __CLASS__, 'output_head_hints' ), 1 );

		// noindex on private/utility pages (always — regardless of Yoast)
		add_action( 'wp_head', array( __CLASS__, 'output_noindex' ), 2 );

		// rel=prev/next for paginated archives
		add_action( 'wp_head', array( __CLASS__, 'output_pagination_links' ), 3 );

		// Open Graph / Twitter Card meta tags
		add_action( 'wp_head', array( __CLASS__, 'output_og_tags' ), 5 );

		// Organization + WebSite schema (runs only when Yoast is not active)
		add_action( 'wp_head', array( __CLASS__, 'output_organization_schema' ), 6 );

		// FAQPage schema on homepage
		add_action( 'wp_head', array( __CLASS__, 'output_faq_schema' ), 7 );

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
	 * Output Open Graph and Twitter Card meta tags in <head>.
	 * Skips if Yoast SEO is active (it already outputs these).
	 */
	public static function output_og_tags() {
		// Yoast handles OG if active — avoid duplicates.
		if ( defined( 'WPSEO_VERSION' ) ) {
			return;
		}

		$site_name = get_bloginfo( 'name' );

		// ── Determine context ─────────────────────────────────────────────────
		if ( is_singular( 'product' ) ) {
			$product_id = get_the_ID();
			$product    = wc_get_product( $product_id );
			if ( ! $product instanceof WC_Product ) {
				return;
			}

			$og_title = $product->get_name() . ' | ' . $site_name;
			$og_desc  = wp_trim_words(
				wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
				30,
				'...'
			);
			$og_url   = get_permalink( $product_id );
			$og_type  = 'og:product';

			$img_id  = $product->get_image_id();
			$img_url = $img_id
				? wp_get_attachment_image_url( $img_id, 'large' )
				: wc_placeholder_img_src( 'large' );

		} elseif ( is_front_page() || is_home() ) {
			$og_title = get_bloginfo( 'name' ) . ' | ' . get_bloginfo( 'description' );
			$og_desc  = get_bloginfo( 'description' );
			$og_url   = home_url( '/' );
			$og_type  = 'website';
			$img_url  = '';

			// Custom logo as OG image
			$logo_id = get_theme_mod( 'custom_logo' );
			if ( $logo_id ) {
				$img_url = wp_get_attachment_image_url( $logo_id, 'large' );
			}

		} elseif ( is_singular() ) {
			$og_title = get_the_title() . ' | ' . $site_name;
			$og_desc  = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 30, '...' );
			$og_url   = get_permalink();
			$og_type  = 'article';

			$thumb_id = get_post_thumbnail_id();
			$img_url  = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';

		} else {
			return; // Archives etc — skip to avoid generic tags.
		}

		if ( ! $og_desc ) {
			$og_desc = get_bloginfo( 'description' );
		}

		// ── Output tags ───────────────────────────────────────────────────────
		?>
<!-- Open Graph -->
<meta property="og:type"        content="<?php echo esc_attr( $og_type ); ?>">
<meta property="og:title"       content="<?php echo esc_attr( $og_title ); ?>">
<meta property="og:description" content="<?php echo esc_attr( $og_desc ); ?>">
<meta property="og:url"         content="<?php echo esc_url( $og_url ); ?>">
<meta property="og:site_name"   content="<?php echo esc_attr( $site_name ); ?>">
		<?php if ( $img_url ) : ?>
<meta property="og:image"       content="<?php echo esc_url( $img_url ); ?>">
<meta property="og:image:width" content="1200">
		<?php endif; ?>
<!-- Twitter Card -->
<meta name="twitter:card"        content="summary_large_image">
<meta name="twitter:title"       content="<?php echo esc_attr( $og_title ); ?>">
<meta name="twitter:description" content="<?php echo esc_attr( $og_desc ); ?>">
		<?php if ( $img_url ) : ?>
<meta name="twitter:image"       content="<?php echo esc_url( $img_url ); ?>">
		<?php endif; ?>
		<?php
	}

	/**
	 * Output noindex,nofollow on utility pages that must never be indexed.
	 * Runs regardless of whether Yoast is active — these pages should never rank.
	 */
	public static function output_noindex() {
		$is_private = is_cart() || is_checkout() || is_account_page() || is_order_received_page();

		// Also noindex shop/category pages with active filter/sort parameters.
		if ( ! $is_private && ( is_shop() || is_product_category() ) ) {
			$indexable_params = array( 'paged', 'page' );
			foreach ( $_GET as $key => $val ) {
				if ( ! in_array( $key, $indexable_params, true ) ) {
					$is_private = true;
					break;
				}
			}
		}

		if ( $is_private ) {
			echo '<meta name="robots" content="noindex,nofollow">' . "\n";
		}
	}

	/**
	 * Output rel=prev / rel=next for paginated archives so Google
	 * understands the series without treating each page as duplicate content.
	 */
	public static function output_pagination_links() {
		$paged      = max( 1, (int) get_query_var( 'paged' ) );
		$max_pages  = (int) $GLOBALS['wp_query']->max_num_pages;

		if ( $paged > 1 ) {
			$prev_url = get_pagenum_link( $paged - 1 );
			if ( $prev_url ) {
				echo '<link rel="prev" href="' . esc_url( $prev_url ) . '">' . "\n";
			}
		}

		if ( $paged < $max_pages ) {
			$next_url = get_pagenum_link( $paged + 1 );
			if ( $next_url ) {
				echo '<link rel="next" href="' . esc_url( $next_url ) . '">' . "\n";
			}
		}
	}

	/**
	 * Preconnect and dns-prefetch hints for third-party origins used on every page.
	 */
	public static function output_head_hints() {
		?>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
		<?php
	}

	/**
	 * LocalBusiness + WebSite JSON-LD — skipped when Yoast SEO is active.
	 */
	public static function output_organization_schema() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			return;
		}

		$name     = get_bloginfo( 'name' );
		$url      = home_url( '/' );
		$logo_id  = get_theme_mod( 'custom_logo' );
		$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';

		// LocalBusiness (jewellery store)
		$org = array(
			'@context'        => 'https://schema.org',
			'@type'           => array( 'Organization', 'LocalBusiness', 'JewelryStore' ),
			'name'            => $name,
			'url'             => $url,
			'address'         => array(
				'@type'            => 'PostalAddress',
				'addressLocality'  => 'Karachi',
				'addressCountry'   => 'PK',
			),
			'sameAs'          => array(
				'https://www.instagram.com/genzjwellery_',
				'https://www.facebook.com/genzjewellery',
			),
			'contactPoint'    => array(
				'@type'             => 'ContactPoint',
				'contactType'       => 'customer service',
				'availableLanguage' => array( 'English', 'Urdu' ),
			),
		);

		if ( $logo_url ) {
			$org['logo'] = array( '@type' => 'ImageObject', 'url' => $logo_url );
		}

		// WebSite with SearchAction (enables Google Sitelinks Search Box)
		$website = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'name'            => $name,
			'url'             => $url,
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => $url . '?s={search_term_string}&post_type=product',
				),
				'query-input' => 'required name=search_term_string',
			),
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $org, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
		echo '<script type="application/ld+json">' . wp_json_encode( $website, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
	}

	/**
	 * FAQPage JSON-LD — outputs on the homepage only, reads from wzp_faq_schema option.
	 * Option format: [ ['question' => '...', 'answer' => '...'], ... ]
	 */
	public static function output_faq_schema() {
		if ( ! ( is_front_page() || is_home() ) ) {
			return;
		}

		$faqs = (array) get_option( 'wzp_faq_schema', array() );
		if ( empty( $faqs ) ) {
			return;
		}

		$items = array();
		foreach ( $faqs as $faq ) {
			if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
				continue;
			}
			$items[] = array(
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( $faq['question'] ),
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( $faq['answer'] ),
				),
			);
		}

		if ( empty( $items ) ) {
			return;
		}

		$schema = array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $items,
		);

		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
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
