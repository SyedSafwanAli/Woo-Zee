<?php
/**
 * Admin menu and settings page.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

class WZP_Admin {

	/**
	 * The admin page hook suffix returned by add_menu_page().
	 *
	 * @var string
	 */
	private static $page_hook = '';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_menu',            array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init',            array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );

		// Instagram AJAX — logged-in admins only (no _nopriv variant needed).
		add_action( 'wp_ajax_wzp_test_instagram',  array( __CLASS__, 'ajax_test_instagram' ) );
		add_action( 'wp_ajax_wzp_clear_ig_cache',  array( __CLASS__, 'ajax_clear_ig_cache' ) );

		// Category icon library AJAX.
		add_action( 'wp_ajax_wzp_upload_icon',   array( __CLASS__, 'ajax_upload_icon' ) );
		add_action( 'wp_ajax_wzp_delete_icon',   array( __CLASS__, 'ajax_delete_icon' ) );
		add_action( 'wp_ajax_wzp_save_svg_icon', array( __CLASS__, 'ajax_save_svg_icon' ) );

		// Lookbook product search AJAX.
		add_action( 'wp_ajax_wzp_search_products', array( __CLASS__, 'ajax_search_products' ) );

		// Product Grid manager AJAX.
		add_action( 'wp_ajax_wzp_save_grid',   array( __CLASS__, 'ajax_save_grid' ) );
		add_action( 'wp_ajax_wzp_delete_grid', array( __CLASS__, 'ajax_delete_grid' ) );

		// Navbar settings AJAX.
		add_action( 'wp_ajax_wzp_save_navbar', array( __CLASS__, 'ajax_save_navbar' ) );

		// Menu builder AJAX.
		add_action( 'wp_ajax_wzp_save_menu',   array( __CLASS__, 'ajax_save_menu' ) );
		add_action( 'wp_ajax_wzp_delete_menu', array( __CLASS__, 'ajax_delete_menu' ) );

		// Product Detail settings AJAX.
		add_action( 'wp_ajax_wzp_save_product_detail', array( __CLASS__, 'ajax_save_product_detail' ) );

		// Wishlist page AJAX (logged-in and guests).
		add_action( 'wp_ajax_wzp_get_wishlist',        array( __CLASS__, 'ajax_get_wishlist' ) );
		add_action( 'wp_ajax_nopriv_wzp_get_wishlist', array( __CLASS__, 'ajax_get_wishlist' ) );

		// Cart drawer AJAX (logged-in and guests).
		add_action( 'wp_ajax_wzp_get_cart',           array( __CLASS__, 'ajax_get_cart' ) );
		add_action( 'wp_ajax_nopriv_wzp_get_cart',    array( __CLASS__, 'ajax_get_cart' ) );
		add_action( 'wp_ajax_wzp_update_cart',        array( __CLASS__, 'ajax_update_cart' ) );
		add_action( 'wp_ajax_nopriv_wzp_update_cart', array( __CLASS__, 'ajax_update_cart' ) );
		add_action( 'wp_ajax_wzp_remove_cart_item',        array( __CLASS__, 'ajax_remove_cart_item' ) );
		add_action( 'wp_ajax_nopriv_wzp_remove_cart_item', array( __CLASS__, 'ajax_remove_cart_item' ) );

		// Shop filter AJAX (logged-in and guests).
		add_action( 'wp_ajax_wzp_shop_filter',        array( __CLASS__, 'ajax_shop_filter' ) );
		add_action( 'wp_ajax_nopriv_wzp_shop_filter', array( __CLASS__, 'ajax_shop_filter' ) );

		// New Arrivals AJAX (logged-in and guests).
		add_action( 'wp_ajax_wzp_new_arrivals',        array( __CLASS__, 'ajax_new_arrivals' ) );
		add_action( 'wp_ajax_nopriv_wzp_new_arrivals', array( __CLASS__, 'ajax_new_arrivals' ) );

		// Category Products AJAX (logged-in and guests).
		add_action( 'wp_ajax_wzp_category_products',        array( __CLASS__, 'ajax_category_products' ) );
		add_action( 'wp_ajax_nopriv_wzp_category_products', array( __CLASS__, 'ajax_category_products' ) );

		// Newsletter admin AJAX.
		add_action( 'wp_ajax_wzp_newsletter_delete',    array( __CLASS__, 'ajax_newsletter_delete' ) );
		add_action( 'wp_ajax_wzp_newsletter_export',    array( __CLASS__, 'ajax_newsletter_export' ) );
		add_action( 'wp_ajax_wzp_newsletter_bulk_delete', array( __CLASS__, 'ajax_newsletter_bulk_delete' ) );
	}

	/**
	 * Register all plugin settings via the WordPress Settings API.
	 * Each module gets its own option group and option name.
	 */
	public static function register_settings() {

		// ── Product Grid ──────────────────────────────────────────────────────
		register_setting(
			'wzp_product_grid_group',
			'wzp_product_grid_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_product_grid_options' ),
				'default'           => array(
					'category' => '',
					'columns'  => '3',
					'count'    => '8',
					'orderby'  => 'date',
				),
			)
		);

		// ── Product Carousel ──────────────────────────────────────────────────
		register_setting(
			'wzp_product_carousel_group',
			'wzp_product_carousel_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_product_carousel_options' ),
				'default'           => array(
					'category' => '',
					'count'    => '8',
					'per_view' => '3',
					'autoplay' => 'true',
					'speed'    => '3000',
				),
			)
		);

		// ── Hero Slider ───────────────────────────────────────────────────────
		register_setting(
			'wzp_hero_group',
			'wzp_hero_slides',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_hero_slides' ),
				'default'           => array(),
			)
		);

		// ── Lookbook ──────────────────────────────────────────────────────────
		register_setting(
			'wzp_lookbook_group',
			'wzp_lookbook_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_lookbook_options' ),
				'default'           => array(),
			)
		);

		// ── Single Banner ─────────────────────────────────────────────────────
		register_setting(
			'wzp_single_banner_group',
			'wzp_single_banner_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_single_banner_options' ),
				'default'           => array(),
			)
		);

		// ── Testimonials ──────────────────────────────────────────────────────
		register_setting(
			'wzp_testimonials_group',
			'wzp_testimonials_data',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_testimonials_data' ),
				'default'           => array(),
			)
		);

		// ── Banner Cards ──────────────────────────────────────────────────────
		register_setting(
			'wzp_banner_cards_group',
			'wzp_banner_cards',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_banner_cards' ),
				'default'           => array(),
			)
		);

		// ── Category Icons ────────────────────────────────────────────────────
		register_setting(
			'wzp_cat_icons_group',
			'wzp_category_icons',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_category_icons' ),
				'default'           => array(),
			)
		);

		// ── Category Carousel ─────────────────────────────────────────────────
		register_setting(
			'wzp_cat_carousel_group',
			'wzp_cat_carousel_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_cat_carousel_options' ),
				'default'           => array(
					'per_view'   => '7',
					'orderby'    => 'name',
					'hide_empty' => 'true',
					'icon_size'  => '48',
				),
			)
		);

		// ── Card Style ───────────────────────────────────────────────────────
		register_setting(
			'wzp_card_style_group',
			'wzp_card_style_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_card_style_options' ),
				'default'           => array(),
			)
		);

		// ── Instagram Feed ────────────────────────────────────────────────────
		register_setting(
			'wzp_instagram_group',
			'wzp_instagram_options',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_instagram_options' ),
				'default'           => array(
					'access_token' => '',
					'username'     => '',
					'count'        => 6,
				),
			)
		);
	}

	/**
	 * Sanitise Card Style options.
	 *
	 * @param  mixed $raw  Raw POSTed value.
	 * @return array       Cleaned options.
	 */
	public static function sanitize_card_style_options( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$hex = function( $v, $default ) {
			$v = sanitize_hex_color( $v );
			return $v ? $v : $default;
		};

		return array(
			'primary'       => $hex( $raw['primary']       ?? '', '#1a1a1a' ),
			'secondary'     => $hex( $raw['secondary']     ?? '', '#4a4a4a' ),
			'accent'        => $hex( $raw['accent']        ?? '', '#c9a96e' ),
			'surface'       => $hex( $raw['surface']       ?? '', '#ffffff' ),
			'surface_alt'   => $hex( $raw['surface_alt']   ?? '', '#f7f6f4' ),
			'border'        => $hex( $raw['border']        ?? '', '#e8e4df' ),
			'title_size'    => absint( $raw['title_size']  ?? 15 ),
			'price_size'    => absint( $raw['price_size']  ?? 14 ),
			'cat_size'      => absint( $raw['cat_size']    ?? 10 ),
			'show_rating'   => ! empty( $raw['show_rating'] ) ? '1' : '0',
			'show_category' => ! empty( $raw['show_category'] ) ? '1' : '0',
			'show_wishlist' => ! empty( $raw['show_wishlist'] ) ? '1' : '0',
			'show_badge'    => ! empty( $raw['show_badge'] ) ? '1' : '0',
			'show_quickadd' => ! empty( $raw['show_quickadd'] ) ? '1' : '0',
		);
	}

	/**
	 * Sanitise Instagram options before saving.
	 * Deletes the feed transient so the next frontend load fetches fresh data.
	 *
	 * @param  mixed $raw  Raw POSTed value.
	 * @return array       Cleaned options.
	 */
	public static function sanitize_instagram_options( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		// Force a fresh API fetch after any settings change.
		delete_transient( 'wzp_instagram_feed' );

		return array(
			'access_token' => sanitize_text_field( wp_unslash( $raw['access_token'] ?? '' ) ),
			'username'     => sanitize_text_field( $raw['username']     ?? '' ),
			'count'        => min( 12, max( 1, absint( $raw['count'] ?? 6 ) ) ),
		);
	}

	/**
	 * AJAX: test the Instagram API with the provided access token.
	 * Accepts token from POST so the user can test before saving.
	 */
	public static function ajax_test_instagram() {
		check_ajax_referer( 'wzp_instagram_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-zee-plugin' ) ) );
		}

		$token = sanitize_text_field( wp_unslash( $_POST['token'] ?? '' ) );
		$count = min( 12, max( 1, absint( $_POST['count'] ?? 6 ) ) );

		if ( empty( $token ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter an access token first.', 'woo-zee-plugin' ) ) );
		}

		$api_url = add_query_arg(
			array(
				'fields'       => 'id,media_type,media_url',
				'limit'        => $count,
				'access_token' => $token,
			),
			'https://graph.instagram.com/me/media'
		);

		$response = wp_remote_get( $api_url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: WP_Error message */
					__( 'Connection failed: %s', 'woo-zee-plugin' ),
					$response->get_error_message()
				),
			) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || empty( $body['data'] ) ) {
			$api_msg = isset( $body['error']['message'] )
				? sanitize_text_field( $body['error']['message'] )
				: '';
			wp_send_json_error( array(
				'message' => $api_msg
					? sprintf( /* translators: %s: API error */ __( 'API error: %s', 'woo-zee-plugin' ), $api_msg )
					: __( 'Invalid token or no posts returned.', 'woo-zee-plugin' ),
			) );
		}

		$images = array_filter(
			$body['data'],
			fn( $p ) => isset( $p['media_type'] ) && 'IMAGE' === $p['media_type']
		);
		$n = count( $images );

		wp_send_json_success( array(
			'message' => sprintf(
				/* translators: %d: number of posts found */
				_n( 'Connected! Found %d post.', 'Connected! Found %d posts.', $n, 'woo-zee-plugin' ),
				$n
			),
		) );
	}

	/**
	 * AJAX: delete the Instagram feed transient to force a fresh fetch.
	 */
	public static function ajax_clear_ig_cache() {
		check_ajax_referer( 'wzp_instagram_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-zee-plugin' ) ) );
		}

		delete_transient( 'wzp_instagram_feed' );

		wp_send_json_success( array( 'message' => __( 'Cache cleared! The feed will refresh on next page load.', 'woo-zee-plugin' ) ) );
	}

	/**
	 * Sanitise testimonials data before saving.
	 * Skips rows where both name and review are empty.
	 * Re-indexes the result array.
	 *
	 * @param  mixed $raw  Raw POSTed value.
	 * @return array       Sequential array of clean review entries.
	 */
	public static function sanitize_testimonials_data( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$clean = array();

		foreach ( $raw as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$name   = sanitize_text_field( $entry['name']   ?? '' );
			$review = sanitize_textarea_field( $entry['review'] ?? '' );

			// Discard rows that have neither a name nor a review.
			if ( ! $name && ! $review ) {
				continue;
			}

			$clean[] = array(
				'avatar_id' => absint( $entry['avatar_id'] ?? 0 ),
				'name'      => $name,
				'location'  => sanitize_text_field( $entry['location'] ?? '' ),
				'review'    => $review,
			);
		}

		return $clean;
	}

	/**
	 * Sanitise single banner options before saving.
	 *
	 * @param  mixed $raw  Raw POSTed value.
	 * @return array       Cleaned options array.
	 */
	public static function sanitize_single_banner_options( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$align  = in_array( $raw['align'] ?? 'left', array( 'left', 'center', 'right' ), true )
		          ? $raw['align'] : 'left';
		$height = absint( $raw['height'] ?? 420 );
		return array(
			'image_id'    => absint( $raw['image_id']    ?? 0 ),
			'label'       => sanitize_text_field( $raw['label']       ?? '' ),
			'heading'     => sanitize_text_field( $raw['heading']     ?? '' ),
			'description' => sanitize_textarea_field( $raw['description'] ?? '' ),
			'btn_text'    => sanitize_text_field( $raw['btn_text']    ?? '' ),
			'btn_url'     => esc_url_raw( $raw['btn_url']    ?? '' ),
			'align'       => $align,
			'height'      => ( $height >= 200 && $height <= 900 ) ? $height : 420,
		);
	}

	/**
	 * Sanitise lookbook options before saving.
	 *
	 * @param  mixed $raw  Raw POSTed value.
	 * @return array       Cleaned options array.
	 */
	public static function sanitize_lookbook_options( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		// ── Hotspots sub-array ────────────────────────────────────────────────
		$hotspots = array();

		if ( ! empty( $raw['hotspots'] ) && is_array( $raw['hotspots'] ) ) {
			foreach ( $raw['hotspots'] as $hs ) {
				if ( ! is_array( $hs ) ) { continue; }

				$product_id = absint( $hs['product_id'] ?? 0 );
				if ( ! $product_id ) { continue; } // skip rows with no product

				$hotspots[] = array(
					'x'          => round( min( 100, max( 0, (float) ( $hs['x'] ?? 0 ) ) ), 2 ),
					'y'          => round( min( 100, max( 0, (float) ( $hs['y'] ?? 0 ) ) ), 2 ),
					'product_id' => $product_id,
				);
			}
		}

		return array(
			'image_id'    => absint( $raw['image_id']    ?? 0 ),
			'label'       => sanitize_text_field( $raw['label']       ?? '' ),
			'heading'     => sanitize_text_field( $raw['heading']     ?? '' ),
			'description' => sanitize_textarea_field( $raw['description'] ?? '' ),
			'btn_text'    => sanitize_text_field( $raw['btn_text']    ?? '' ),
			'btn_url'     => esc_url_raw( $raw['btn_url']    ?? '' ),
			'hotspots'    => $hotspots,
		);
	}

	/**
	 * Sanitise the hero slides array before saving.
	 * Re-indexes the array and strips slides that have no image AND no heading.
	 *
	 * @param  mixed $raw  Raw POSTed value.
	 * @return array       Sequential array of clean slide data.
	 */
	public static function sanitize_hero_slides( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$clean = array();

		foreach ( $raw as $slide ) {
			if ( ! is_array( $slide ) ) {
				continue;
			}

			$image_id = absint( $slide['image_id'] ?? 0 );
			$heading  = sanitize_text_field( $slide['heading'] ?? '' );

			// Discard rows where both image and heading are empty.
			if ( ! $image_id && ! $heading ) {
				continue;
			}

			$clean[] = array(
				'image_id'    => $image_id,
				'label'       => sanitize_text_field( $slide['label']       ?? '' ),
				'heading'     => $heading,
				'description' => sanitize_textarea_field( $slide['description'] ?? '' ),
				'btn_text'    => sanitize_text_field( $slide['btn_text']    ?? '' ),
				'btn_url'     => esc_url_raw( $slide['btn_url']    ?? '' ),
			);
		}

		return $clean;
	}

	/**
	 * Sanitise and validate the product-carousel option array before saving.
	 *
	 * @param  mixed $raw  Raw POSTed value.
	 * @return array       Clean, validated option array.
	 */
	public static function sanitize_product_carousel_options( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$allowed_per_view = array( '2', '3', '4' );

		// Checkbox: present in POST only when checked.
		$autoplay = isset( $raw['autoplay'] ) && '1' === (string) $raw['autoplay'] ? 'true' : 'false';

		return array(
			'category' => sanitize_text_field( $raw['category'] ?? '' ),
			'count'    => (string) min( 24, max( 1, absint( $raw['count'] ?? 8 ) ) ),
			'per_view' => in_array( $raw['per_view'] ?? '', $allowed_per_view, true )
			              ? $raw['per_view']
			              : '3',
			'autoplay' => $autoplay,
			'speed'    => (string) min( 10000, max( 500, absint( $raw['speed'] ?? 3000 ) ) ),
		);
	}

	/**
	 * Sanitise the category icon assignments before saving.
	 * Accepts array of term_id => icon_filename pairs.
	 *
	 * @param  mixed $raw  Raw POSTed value.
	 * @return array       Clean array of absint(term_id) => sanitized filename.
	 */
	public static function sanitize_category_icons( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$allowed_icons = array_column( WZP_Helpers::get_category_icons(), 'filename' );
		$clean         = array();

		foreach ( $raw as $term_id => $filename ) {
			$term_id  = absint( $term_id );
			$filename = sanitize_file_name( wp_unslash( (string) $filename ) );

			if ( ! $term_id ) {
				continue;
			}

			// Empty string = "no icon assigned" — always allow.
			if ( '' === $filename || in_array( $filename, $allowed_icons, true ) ) {
				$clean[ $term_id ] = $filename;
			}
		}

		return $clean;
	}

	/**
	 * Sanitise the category carousel options before saving.
	 *
	 * @param  mixed $raw  Raw POSTed value.
	 * @return array       Clean, validated option array.
	 */
	public static function sanitize_cat_carousel_options( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$allowed_per_view = array( '4', '5', '6', '7', '8', '9', '10' );
		$allowed_orderby  = array( 'name', 'count', 'id', 'slug', 'menu_order' );

		return array(
			'per_view'   => in_array( $raw['per_view'] ?? '', $allowed_per_view, true )
			                ? $raw['per_view']
			                : '7',
			'orderby'    => in_array( $raw['orderby'] ?? '', $allowed_orderby, true )
			                ? $raw['orderby']
			                : 'name',
			'hide_empty' => isset( $raw['hide_empty'] ) && '1' === (string) $raw['hide_empty']
			                ? 'true'
			                : 'false',
			'icon_size'  => (string) min( 96, max( 24, absint( $raw['icon_size'] ?? 48 ) ) ),
		);
	}

	/**
	 * Sanitise and validate the product-grid option array before it is saved.
	 *
	 * @param  mixed $raw  Raw POSTed value (may not be an array).
	 * @return array       Clean, validated option array.
	 */
	public static function sanitize_product_grid_options( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$allowed_orderby = array( 'date', 'popularity', 'rating', 'price' );
		$allowed_columns = array( '2', '3', '4', '5' );

		return array(
			'category' => sanitize_text_field( $raw['category'] ?? '' ),
			'columns'  => in_array( $raw['columns'] ?? '', $allowed_columns, true )
			              ? $raw['columns']
			              : '3',
			'count'    => (string) min( 24, max( 1, absint( $raw['count'] ?? 8 ) ) ),
			'orderby'  => in_array( $raw['orderby'] ?? '', $allowed_orderby, true )
			              ? $raw['orderby']
			              : 'date',
		);
	}

	/**
	 * Sanitise the 4 banner card entries before saving.
	 *
	 * @param  mixed $raw  Raw POSTed value.
	 * @return array       Array of up to 4 clean card entries.
	 */
	public static function sanitize_banner_cards( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$clean = array();

		foreach ( array_slice( $raw, 0, 4 ) as $card ) {
			if ( ! is_array( $card ) ) {
				$clean[] = array();
				continue;
			}
			$allowed_icons = array( '', '↗', '→', '➜', '›', '▸', '+', '✦' );
			$btn_icon      = $card['btn_icon'] ?? '↗';

			$clean[] = array(
				'image_id' => absint( $card['image_id'] ?? 0 ),
				'heading'  => sanitize_text_field( $card['heading']  ?? '' ),
				'btn_text' => sanitize_text_field( $card['btn_text'] ?? '' ),
				'btn_url'  => esc_url_raw( $card['btn_url'] ?? '' ),
				'btn_icon' => in_array( $btn_icon, $allowed_icons, true ) ? $btn_icon : '↗',
			);
		}

		return $clean;
	}

	/**
	 * AJAX: upload a new icon file to the category-icons directory.
	 * Accepts WebP, PNG, and SVG only.
	 */
	public static function ajax_upload_icon() {
		check_ajax_referer( 'wzp_upload_icon_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-zee-plugin' ) ) );
		}

		if ( empty( $_FILES['wzp_icon_file'] ) || UPLOAD_ERR_OK !== $_FILES['wzp_icon_file']['error'] ) {
			wp_send_json_error( array( 'message' => __( 'No file received or upload error.', 'woo-zee-plugin' ) ) );
		}

		$file    = $_FILES['wzp_icon_file'];
		$allowed = array( 'webp', 'png', 'svg' );
		$ext     = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		if ( ! in_array( $ext, $allowed, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Only WebP, PNG, and SVG files are allowed.', 'woo-zee-plugin' ) ) );
		}

		$dest_dir = WZP_PATH . 'assets/images/category-icons/';

		if ( ! is_dir( $dest_dir ) ) {
			wp_send_json_error( array( 'message' => __( 'Icons directory not found.', 'woo-zee-plugin' ) ) );
		}

		$filename = sanitize_file_name( wp_unslash( $file['name'] ) );
		$dest     = $dest_dir . $filename;

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save the file. Check directory permissions.', 'woo-zee-plugin' ) ) );
		}

		$label = ucwords( str_replace( array( '-', '_' ), ' ', pathinfo( $filename, PATHINFO_FILENAME ) ) );
		$url   = WZP_URL . 'assets/images/category-icons/' . rawurlencode( $filename );

		$response = array(
			'filename' => $filename,
			'url'      => $url,
			'label'    => $label,
		);

		// For SVG files return the raw markup so JS can render inline (avoids MIME issues).
		if ( 'svg' === $ext ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$response['svg_content'] = file_get_contents( $dest );
		}

		wp_send_json_success( $response );
	}

	/**
	 * AJAX: save pasted SVG code as a .svg file in the category-icons directory.
	 */
	public static function ajax_save_svg_icon() {
		check_ajax_referer( 'wzp_save_svg_icon_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-zee-plugin' ) ) );
		}

		$svg_code = isset( $_POST['svg_code'] ) ? wp_unslash( $_POST['svg_code'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$term_id  = isset( $_POST['term_id'] )  ? absint( $_POST['term_id'] ) : 0;

		$svg_code = trim( $svg_code );

		if ( empty( $svg_code ) ) {
			wp_send_json_error( array( 'message' => __( 'No SVG code provided.', 'woo-zee-plugin' ) ) );
		}

		// Must contain an <svg> element.
		if ( stripos( $svg_code, '<svg' ) === false ) {
			wp_send_json_error( array( 'message' => __( 'Invalid SVG: must contain an <svg> element.', 'woo-zee-plugin' ) ) );
		}

		// Basic sanitisation: strip <script> blocks and on* event attributes.
		$svg_code = preg_replace( '/<script[\s\S]*?<\/script>/i', '', $svg_code );
		$svg_code = preg_replace( '/\s+on\w+\s*=\s*(["\'])[^"\']*\1/i', '', $svg_code );
		$svg_code = preg_replace( '/\s+on\w+\s*=\s*[^\s>]+/i', '', $svg_code );

		$filename = $term_id ? 'cat-' . $term_id . '.svg' : 'icon-' . uniqid() . '.svg';
		$dest_dir = WZP_PATH . 'assets/images/category-icons/';

		if ( ! is_dir( $dest_dir ) ) {
			wp_mkdir_p( $dest_dir );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( file_put_contents( $dest_dir . $filename, $svg_code ) === false ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save SVG file. Check directory permissions.', 'woo-zee-plugin' ) ) );
		}

		$url = WZP_URL . 'assets/images/category-icons/' . rawurlencode( $filename );

		wp_send_json_success( array(
			'filename' => $filename,
			'url'      => $url,
		) );
	}

	/**
	 * AJAX: delete an icon file from the category-icons directory.
	 * Only allows deleting files that exist in the icon library.
	 */
	public static function ajax_delete_icon() {
		check_ajax_referer( 'wzp_delete_icon_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-zee-plugin' ) ) );
		}

		// basename() strips path traversal; keep original casing/characters intact.
		$filename = basename( wp_unslash( $_POST['filename'] ?? '' ) );

		if ( ! $filename ) {
			wp_send_json_error( array( 'message' => __( 'No filename provided.', 'woo-zee-plugin' ) ) );
		}

		// Allowlist: only files that exist in the library can be deleted.
		// Case-insensitive match so upload-time casing differences don't block deletion.
		$all_icons = WZP_Helpers::get_category_icons();
		$matched   = null;
		foreach ( $all_icons as $icon ) {
			if ( strcasecmp( $icon['filename'], $filename ) === 0 ) {
				$matched = $icon['filename'];
				break;
			}
		}

		if ( $matched === null ) {
			wp_send_json_error( array( 'message' => __( 'File not found in icon library.', 'woo-zee-plugin' ) ) );
		}

		// Use the exact on-disk filename (from allowlist) for the path.
		$path = WZP_PATH . 'assets/images/category-icons/' . $matched;

		if ( ! file_exists( $path ) || ! @unlink( $path ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not delete the file. Check directory permissions.', 'woo-zee-plugin' ) ) );
		}

		wp_send_json_success( array( 'filename' => $matched, 'message' => __( 'Icon deleted.', 'woo-zee-plugin' ) ) );
	}

	/**
	 * AJAX: search WooCommerce products by name or ID.
	 * Returns up to 10 results with id, name, thumb, price.
	 */
	public static function ajax_search_products() {
		check_ajax_referer( 'wzp_search_products_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$term = sanitize_text_field( wp_unslash( $_POST['term'] ?? '' ) );

		if ( '' === $term ) {
			wp_send_json_success( array() );
		}

		// Numeric input → search by ID only.
		if ( is_numeric( $term ) && (int) $term > 0 ) {
			$query_args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 10,
				'post__in'       => array( absint( $term ) ),
			);
		} else {
			$query_args = array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 10,
				's'              => $term,
			);
		}

		$q       = new WP_Query( $query_args );
		$results = array();

		if ( $q->have_posts() ) {
			while ( $q->have_posts() ) {
				$q->the_post();
				$product = wc_get_product( get_the_ID() );
				if ( ! $product instanceof WC_Product ) {
					continue;
				}
				$results[] = array(
					'id'    => $product->get_id(),
					'name'  => $product->get_name(),
					'thumb' => get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ) ?: '',
					'price' => wp_strip_all_tags( $product->get_price_html() ),
				);
			}
			wp_reset_postdata();
		}

		wp_send_json_success( $results );
	}

	/**
	 * Register top-level admin menu item.
	 */
	public static function register_menu() {
		self::$page_hook = add_menu_page(
			__( 'Woo Zee', 'woo-zee-plugin' ),          // page title
			__( 'Woo Zee', 'woo-zee-plugin' ),          // menu label
			'manage_options',                            // capability
			'woo-zee-plugin',                            // menu slug
			array( __CLASS__, 'render_admin_page' ),    // callback
			'dashicons-store',                           // icon
			56                                           // position (after WooCommerce)
		);
	}

	/**
	 * Render the admin page by loading the view template.
	 */
	public static function render_admin_page() {
		// Capability check — redundant with add_menu_page but belt-and-braces.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'woo-zee-plugin' ) );
		}

		$view = WZP_PATH . 'admin/views/admin-page.php';

		if ( file_exists( $view ) ) {
			include $view;
		}
	}

	/**
	 * Enqueue admin stylesheet and scripts only on the plugin's own admin page.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public static function enqueue_admin_assets( $hook_suffix ) {
		if ( $hook_suffix !== self::$page_hook ) {
			return;
		}

		// WordPress Media Library — required for the hero-slider image picker.
		wp_enqueue_media();

		wp_enqueue_style(
			'wzp-admin-style',
			WZP_URL . 'admin/css/admin-style.css',
			array(),
			WZP_VERSION
		);

		wp_enqueue_script(
			'wzp-admin-script',
			WZP_URL . 'admin/js/admin-script.js',
			array( 'jquery', 'media-upload' ),
			WZP_VERSION,
			true
		);

	}

	/**
	 * AJAX: Save (create or update) a product grid config.
	 */
	public static function ajax_save_grid() {
		check_ajax_referer( 'wzp_grid_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$raw = isset( $_POST['grid'] ) ? (array) $_POST['grid'] : array();

		$allowed_orderby = array( 'date', 'popularity', 'rating', 'price' );
		$allowed_columns = array( '2', '3', '4', '5' );

		$id = sanitize_key( $raw['id'] ?? '' );
		if ( ! $id ) {
			$id = 'grid_' . substr( md5( uniqid( '', true ) ), 0, 8 );
		}

		$raw_cats  = isset( $raw['categories'] ) ? (array) $raw['categories'] : array();
		$clean_cats = array_values( array_filter( array_map( 'sanitize_key', $raw_cats ) ) );

		$grid = array(
			'id'         => $id,
			'label'      => sanitize_text_field( $raw['label'] ?? '' ),
			'categories' => $clean_cats,
			'columns'    => in_array( $raw['columns'] ?? '', $allowed_columns, true ) ? $raw['columns'] : '3',
			'count'      => (string) min( 24, max( 1, absint( $raw['count'] ?? 8 ) ) ),
			'orderby'    => in_array( $raw['orderby'] ?? '', $allowed_orderby, true ) ? $raw['orderby'] : 'date',
		);

		$saved_grids = (array) get_option( 'wzp_saved_grids', array() );

		$found = false;
		foreach ( $saved_grids as $i => $g ) {
			if ( isset( $g['id'] ) && $g['id'] === $id ) {
				$saved_grids[ $i ] = $grid;
				$found             = true;
				break;
			}
		}
		if ( ! $found ) {
			$saved_grids[] = $grid;
		}

		update_option( 'wzp_saved_grids', array_values( $saved_grids ) );

		wp_send_json_success( array( 'grid' => $grid ) );
	}

	/**
	 * AJAX: Delete a product grid config by ID.
	 */
	public static function ajax_delete_grid() {
		check_ajax_referer( 'wzp_grid_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$id          = sanitize_key( $_POST['grid_id'] ?? '' );
		$saved_grids = (array) get_option( 'wzp_saved_grids', array() );
		$saved_grids = array_values( array_filter( $saved_grids, function ( $g ) use ( $id ) {
			return isset( $g['id'] ) && $g['id'] !== $id;
		} ) );

		update_option( 'wzp_saved_grids', $saved_grids );

		wp_send_json_success();
	}

	/**
	 * AJAX: Save navbar settings.
	 */
	public static function ajax_save_navbar() {
		check_ajax_referer( 'wzp_navbar_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$settings = array(
			'logo_type'     => in_array( $_POST['logo_type'] ?? 'text', array( 'text', 'image' ), true ) ? sanitize_key( $_POST['logo_type'] ) : 'text',
			'logo_id'       => absint( $_POST['logo_id'] ?? 0 ),
			'logo_text'     => sanitize_text_field( $_POST['logo_text'] ?? '' ),
			'menu_id'       => absint( $_POST['menu_id'] ?? 0 ),
			'account_url'   => esc_url_raw( $_POST['account_url'] ?? '' ),
			'wishlist_url'  => esc_url_raw( $_POST['wishlist_url'] ?? '' ),
			'cart_url'      => esc_url_raw( $_POST['cart_url'] ?? '' ),
			'show_search'   => ( isset( $_POST['show_search'] )   && $_POST['show_search']   === '1' ) ? '1' : '0',
			'show_account'  => ( isset( $_POST['show_account'] )  && $_POST['show_account']  === '1' ) ? '1' : '0',
			'show_wishlist' => ( isset( $_POST['show_wishlist'] ) && $_POST['show_wishlist'] === '1' ) ? '1' : '0',
			'show_cart'     => ( isset( $_POST['show_cart'] )     && $_POST['show_cart']     === '1' ) ? '1' : '0',
			'sticky'        => ( isset( $_POST['sticky'] )        && $_POST['sticky']        === '1' ) ? '1' : '0',
			'bg_color'      => sanitize_hex_color( $_POST['bg_color']     ?? '#ffffff' ) ?: '#ffffff',
			'text_color'    => sanitize_hex_color( $_POST['text_color']   ?? '#111111' ) ?: '#111111',
			'hover_color'   => sanitize_hex_color( $_POST['hover_color']  ?? '#888888' ) ?: '#888888',
			'border_color'  => sanitize_hex_color( $_POST['border_color'] ?? '#efefef' ) ?: '#efefef',
			'active_color'  => sanitize_hex_color( $_POST['active_color'] ?? '#111111' ) ?: '#111111',
		);

		if ( $settings['logo_id'] > 0 ) {
			$settings['logo_url'] = wp_get_attachment_image_url( $settings['logo_id'], 'full' ) ?: '';
		} else {
			$settings['logo_url'] = '';
		}

		update_option( 'wzp_navbar_settings', $settings );

		wp_send_json_success( array(
			'shortcode' => '[wzp_navbar]',
			'settings'  => $settings,
		) );
	}

	/**
	 * AJAX: Return wishlist list-rows HTML.
	 * Accepts 'items' — array of {id, added} objects (new format)
	 * or legacy 'ids' plain array.
	 */
	public static function ajax_get_wishlist() {
		check_ajax_referer( 'wzp_nonce', 'nonce' );

		// Support both new {items:[{id,added},...]} and legacy {ids:[...]} formats.
		$items = array();
		if ( ! empty( $_POST['items'] ) && is_array( $_POST['items'] ) ) {
			foreach ( $_POST['items'] as $raw ) {
				$id = absint( $raw['id'] ?? 0 );
				if ( $id ) {
					$items[] = array(
						'id'    => $id,
						'added' => sanitize_text_field( $raw['added'] ?? '' ),
					);
				}
			}
		} elseif ( ! empty( $_POST['ids'] ) ) {
			foreach ( (array) $_POST['ids'] as $raw_id ) {
				$id = absint( $raw_id );
				if ( $id ) {
					$items[] = array( 'id' => $id, 'added' => '' );
				}
			}
		}

		if ( empty( $items ) ) {
			wp_send_json_success( array( 'html' => '', 'count' => 0 ) );
		}

		ob_start();
		echo '<table class="wzp-wl-table">';
		echo '<thead class="wzp-wl-table__head"><tr>';
		echo '<th class="wzp-wl-th wzp-wl-th--img"></th>';
		echo '<th class="wzp-wl-th wzp-wl-th--name">' . esc_html__( 'Product', 'woo-zee-plugin' ) . '</th>';
		echo '<th class="wzp-wl-th wzp-wl-th--price">' . esc_html__( 'Price', 'woo-zee-plugin' ) . '</th>';
		echo '<th class="wzp-wl-th wzp-wl-th--date">' . esc_html__( 'Date Added', 'woo-zee-plugin' ) . '</th>';
		echo '<th class="wzp-wl-th wzp-wl-th--action"></th>';
		echo '</tr></thead>';
		echo '<tbody>';

		$count     = 0;
		$valid_ids = array();
		foreach ( $items as $item ) {
			$product = wc_get_product( $item['id'] );
			if ( ! $product instanceof WC_Product || ! $product->is_visible() ) {
				continue;
			}
			$count++;
			$valid_ids[] = $product->get_id();

			$img_id    = $product->get_image_id();
			$img_url   = $img_id
				? wp_get_attachment_image_url( $img_id, 'thumbnail' )
				: wc_placeholder_img_src( 'thumbnail' );
			$img_alt   = $img_id
				? trim( wp_strip_all_tags( get_post_meta( $img_id, '_wp_attachment_image_alt', true ) ) )
				: esc_attr( $product->get_name() );
			$permalink = get_permalink( $product->get_id() );
			$price     = $product->get_price_html();
			$in_stock  = $product->is_in_stock();

			// Format date.
			$date_str = '';
			if ( ! empty( $item['added'] ) ) {
				$ts = strtotime( $item['added'] );
				if ( $ts ) {
					$date_str = date_i18n( get_option( 'date_format' ), $ts );
				}
			}

			echo '<tr class="wzp-wl-row" data-product-id="' . esc_attr( $product->get_id() ) . '">';

			// Image.
			echo '<td class="wzp-wl-td wzp-wl-td--img">';
			echo '<a href="' . esc_url( $permalink ) . '" class="wzp-wl-row__img-link">';
			echo '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $img_alt ) . '" class="wzp-wl-row__img" loading="lazy">';
			echo '</a></td>';

			// Name.
			echo '<td class="wzp-wl-td wzp-wl-td--name">';
			echo '<a href="' . esc_url( $permalink ) . '" class="wzp-wl-row__name">' . esc_html( $product->get_name() ) . '</a>';
			if ( $product->get_sku() ) {
				echo '<span class="wzp-wl-row__sku">SKU: ' . esc_html( $product->get_sku() ) . '</span>';
			}
			echo '</td>';

			// Price.
			echo '<td class="wzp-wl-td wzp-wl-td--price">';
			echo '<span class="wzp-wl-row__price">' . wp_kses_post( $price ) . '</span>';
			echo '</td>';

			// Date.
			echo '<td class="wzp-wl-td wzp-wl-td--date">';
			echo '<span class="wzp-wl-row__date">' . esc_html( $date_str ?: '—' ) . '</span>';
			echo '</td>';

			// Actions.
			echo '<td class="wzp-wl-td wzp-wl-td--action">';
			if ( $in_stock ) {
				echo '<a href="?add-to-cart=' . esc_attr( $product->get_id() ) . '" class="wzp-wl-row__atc">' . esc_html__( 'Add to Cart', 'woo-zee-plugin' ) . '</a>';
			} else {
				echo '<span class="wzp-wl-row__oos">' . esc_html__( 'Out of Stock', 'woo-zee-plugin' ) . '</span>';
			}
			echo '<button type="button" class="wzp-wl-row__remove" data-product-id="' . esc_attr( $product->get_id() ) . '" aria-label="' . esc_attr__( 'Remove from wishlist', 'woo-zee-plugin' ) . '">';
			echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="16" height="16"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
			echo '</button>';
			echo '</td>';

			echo '</tr>';
		}

		echo '</tbody></table>';
		$html = ob_get_clean();

		if ( ! $count ) {
			wp_send_json_success( array( 'html' => '', 'count' => 0, 'valid_ids' => array() ) );
		}

		wp_send_json_success( array( 'html' => $html, 'count' => $count, 'valid_ids' => $valid_ids ) );
	}

	/**
	 * AJAX: Return cart drawer HTML + count + subtotal.
	 */
	public static function ajax_get_cart() {
		check_ajax_referer( 'wzp_cart_nonce', 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => 'WooCommerce not available.' ) );
		}

		WC()->cart->calculate_totals();

		include_once WZP_PATH . 'modules/navbar/render.php';

		ob_start();
		wzp_render_cart_drawer_items();
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html'     => $html,
			'count'    => WC()->cart->get_cart_contents_count(),
			'subtotal' => WC()->cart->get_cart_subtotal(),
		) );
	}

	/**
	 * AJAX: Update cart item quantity.
	 */
	public static function ajax_update_cart() {
		check_ajax_referer( 'wzp_cart_nonce', 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => 'WooCommerce not available.' ) );
		}

		$cart_key = sanitize_text_field( $_POST['cart_key'] ?? '' );
		$quantity  = absint( $_POST['quantity'] ?? 1 );

		if ( $quantity < 1 ) {
			WC()->cart->remove_cart_item( $cart_key );
		} else {
			WC()->cart->set_quantity( $cart_key, $quantity );
		}

		WC()->cart->calculate_totals();

		include_once WZP_PATH . 'modules/navbar/render.php';

		ob_start();
		wzp_render_cart_drawer_items();
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html'     => $html,
			'count'    => WC()->cart->get_cart_contents_count(),
			'subtotal' => WC()->cart->get_cart_subtotal(),
		) );
	}

	/**
	 * AJAX: Remove a cart item.
	 */
	public static function ajax_remove_cart_item() {
		check_ajax_referer( 'wzp_cart_nonce', 'nonce' );

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => 'WooCommerce not available.' ) );
		}

		$cart_key = sanitize_text_field( $_POST['cart_key'] ?? '' );

		if ( $cart_key ) {
			WC()->cart->remove_cart_item( $cart_key );
			WC()->cart->calculate_totals();
		}

		include_once WZP_PATH . 'modules/navbar/render.php';

		ob_start();
		wzp_render_cart_drawer_items();
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html'     => $html,
			'count'    => WC()->cart->get_cart_contents_count(),
			'subtotal' => WC()->cart->get_cart_subtotal(),
		) );
	}

	/**
	 * AJAX: Save a menu (create or update) in wzp_saved_menus.
	 */
	public static function ajax_save_menu() {
		check_ajax_referer( 'wzp_menu_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$menu_id   = sanitize_key( $_POST['menu_id'] ?? '' );
		$menu_name = sanitize_text_field( $_POST['menu_name'] ?? '' );
		$raw_items = isset( $_POST['items'] ) ? (array) $_POST['items'] : array();

		if ( ! $menu_name ) {
			wp_send_json_error( array( 'message' => 'Menu name is required.' ) );
		}

		// Sanitise items recursively.
		$items = array();
		foreach ( $raw_items as $item ) {
			$children = array();
			if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
				foreach ( $item['children'] as $child ) {
					$children[] = array(
						'label' => sanitize_text_field( $child['label'] ?? '' ),
						'url'   => esc_url_raw( $child['url'] ?? '' ),
					);
				}
			}
			$items[] = array(
				'label'    => sanitize_text_field( $item['label'] ?? '' ),
				'url'      => esc_url_raw( $item['url'] ?? '' ),
				'children' => $children,
			);
		}

		$menus = (array) get_option( 'wzp_saved_menus', array() );

		// Update existing or append new.
		$found = false;
		if ( $menu_id ) {
			foreach ( $menus as &$m ) {
				if ( isset( $m['id'] ) && $m['id'] === $menu_id ) {
					$m['name']  = $menu_name;
					$m['items'] = $items;
					$found      = true;
					break;
				}
			}
			unset( $m );
		}

		if ( ! $found ) {
			$menu_id = 'nav_' . substr( md5( uniqid( '', true ) ), 0, 8 );
			$menus[] = array(
				'id'    => $menu_id,
				'name'  => $menu_name,
				'items' => $items,
			);
		}

		update_option( 'wzp_saved_menus', array_values( $menus ) );

		wp_send_json_success( array(
			'menu_id' => $menu_id,
			'menus'   => $menus,
		) );
	}

	/**
	 * AJAX: Delete a menu from wzp_saved_menus.
	 */
	public static function ajax_delete_menu() {
		check_ajax_referer( 'wzp_menu_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$menu_id = sanitize_key( $_POST['menu_id'] ?? '' );

		if ( ! $menu_id ) {
			wp_send_json_error( array( 'message' => 'No menu ID provided.' ) );
		}

		$menus = (array) get_option( 'wzp_saved_menus', array() );
		$menus = array_values( array_filter( $menus, function ( $m ) use ( $menu_id ) {
			return ! ( isset( $m['id'] ) && $m['id'] === $menu_id );
		} ) );

		update_option( 'wzp_saved_menus', $menus );

		wp_send_json_success( array( 'menus' => $menus ) );
	}

	/**
	 * AJAX: Save product detail settings.
	 */
	public static function ajax_save_product_detail() {
		check_ajax_referer( 'wzp_product_detail_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		// Benefits repeater.
		$raw_benefits = isset( $_POST['benefits'] ) ? (array) $_POST['benefits'] : array();
		$benefits     = array();
		foreach ( $raw_benefits as $b ) {
			if ( empty( $b['title'] ) ) { continue; }
			$benefits[] = array(
				'icon'     => sanitize_text_field( $b['icon']     ?? '' ),
				'title'    => sanitize_text_field( $b['title']    ?? '' ),
				'subtitle' => sanitize_text_field( $b['subtitle'] ?? '' ),
			);
		}

		// Shipping repeater.
		$raw_shipping = isset( $_POST['shipping'] ) ? (array) $_POST['shipping'] : array();
		$shipping     = array();
		foreach ( $raw_shipping as $s ) {
			if ( empty( $s['text'] ) ) { continue; }
			$shipping[] = array(
				'icon' => sanitize_text_field( $s['icon'] ?? '' ),
				'text' => sanitize_text_field( $s['text'] ?? '' ),
			);
		}

		// Color controls.
		$settings = array(
			'benefits'      => $benefits,
			'shipping'      => $shipping,
			'accent_color'  => sanitize_hex_color( $_POST['accent_color']  ?? '#c9a96e' ) ?: '#c9a96e',
			'btn_color'     => sanitize_hex_color( $_POST['btn_color']     ?? '#1a1a1a' ) ?: '#1a1a1a',
			'btn_text'      => sanitize_hex_color( $_POST['btn_text']      ?? '#ffffff' ) ?: '#ffffff',
			'price_color'   => sanitize_hex_color( $_POST['price_color']   ?? '#1a1a1a' ) ?: '#1a1a1a',
		);

		update_option( 'wzp_product_detail_settings', $settings );

		wp_send_json_success( array( 'shortcode' => '[wzp_product_detail]' ) );
	}

	/**
	 * AJAX: Filter/sort/paginate the shop — returns new grid + pagination HTML
	 * + updated results count + structured data for JS to inject.
	 */
	public static function ajax_shop_filter() {
		check_ajax_referer( 'wzp_shop_nonce', 'nonce' );

		require_once WZP_PATH . 'modules/shop/query.php';
		require_once WZP_PATH . 'modules/product-grid/card.php';

		// ── Parse filters from POST ───────────────────────────────────────────
		// phpcs:disable WordPress.Security.NonceVerification
		$per_page  = max( 4, min( 60, intval( $_POST['per_page'] ?? 12 ) ) );
		$page      = max( 1, intval( $_POST['page'] ?? 1 ) );
		$orderby   = sanitize_key( $_POST['orderby'] ?? 'date' );
		$cats      = isset( $_POST['cats'] ) ? array_values( array_filter( array_map( 'sanitize_title', (array) $_POST['cats'] ) ) ) : array();
		$min_price = max( 0, floatval( $_POST['min_price'] ?? 0 ) );
		$max_price = max( 0, floatval( $_POST['max_price'] ?? 0 ) );
		$on_sale   = ! empty( $_POST['on_sale'] );
		$search    = sanitize_text_field( $_POST['search'] ?? '' );
		$columns   = max( 2, min( 5, intval( $_POST['columns'] ?? 4 ) ) );
		// phpcs:enable

		$filters = array(
			'per_page'  => $per_page,
			'page'      => $page,
			'orderby'   => $orderby,
			'cats'      => $cats,
			'min_price' => $min_price,
			'max_price' => $max_price,
			'on_sale'   => $on_sale,
			'search'    => $search,
		);

		$query       = new WP_Query( wzp_build_shop_query_args( $filters ) );
		$total_found = $query->found_posts;
		$total_pages = $query->max_num_pages;
		$from        = ( ( $page - 1 ) * $per_page ) + 1;
		$to          = min( $page * $per_page, $total_found );

		// ── Grid HTML ─────────────────────────────────────────────────────────
		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				echo wzp_render_product_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			wp_reset_postdata();
		} else {
			echo '<div class="wzp-shop__empty">';
			echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="wzp-shop__empty-icon"><circle cx="32" cy="32" r="28"/><path d="M20 32h24M32 20v24"/></svg>';
			echo '<p class="wzp-shop__empty-text">' . esc_html__( 'No products match your filters.', 'woo-zee-plugin' ) . '</p>';
			echo '<button type="button" class="wzp-shop__reset-btn">' . esc_html__( 'Clear Filters', 'woo-zee-plugin' ) . '</button>';
			echo '</div>';
		}
		$grid_html = ob_get_clean();

		// ── Pagination HTML ───────────────────────────────────────────────────
		$pagination_html = wzp_shop_pagination( $page, $total_pages );

		// ── Results count text ────────────────────────────────────────────────
		if ( $total_found > 0 ) {
			$count_text = sprintf(
				/* translators: 1: from, 2: to, 3: total */
				__( 'Showing %1$s–%2$s of %3$s products', 'woo-zee-plugin' ),
				number_format_i18n( $from ),
				number_format_i18n( $to ),
				number_format_i18n( $total_found )
			);
		} else {
			$count_text = __( 'No products found', 'woo-zee-plugin' );
		}

		// ── Structured data ───────────────────────────────────────────────────
		$ld = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'ItemList',
			'numberOfItems'   => $total_found,
			'itemListElement' => array(),
		);
		$pos = $from;
		foreach ( $query->posts as $post ) {
			$p = wc_get_product( $post->ID );
			if ( ! $p instanceof WC_Product ) { continue; }
			$ld['itemListElement'][] = array(
				'@type'    => 'ListItem',
				'position' => $pos++,
				'item'     => array(
					'@type' => 'Product',
					'name'  => $p->get_name(),
					'url'   => get_permalink( $p->get_id() ),
					'offers' => array(
						'@type'         => 'Offer',
						'price'         => $p->get_price(),
						'priceCurrency' => get_woocommerce_currency(),
					),
				),
			);
		}

		wp_send_json_success( array(
			'grid'       => $grid_html,
			'pagination' => $pagination_html,
			'count_text' => $count_text,
			'found'      => $total_found,
			'ld_json'    => wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
		) );
	}

	/**
	 * AJAX: New Arrivals tab switch / pagination.
	 */
	public static function ajax_new_arrivals() {
		check_ajax_referer( 'wzp_na_nonce', 'nonce' );

		require_once WZP_PATH . 'modules/product-grid/card.php';
		require_once WZP_PATH . 'modules/shop/query.php';

		// phpcs:disable WordPress.Security.NonceVerification
		$days     = intval( $_POST['days'] ?? 30 );
		$page     = max( 1, intval( $_POST['page'] ?? 1 ) );
		$per_page = max( 4, min( 60, intval( $_POST['per_page'] ?? 12 ) ) );
		// phpcs:enable

		$allowed_days = array( 0, 7, 30, 90 );
		if ( ! in_array( $days, $allowed_days, true ) ) {
			$days = 30;
		}

		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => $page,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		);

		if ( $days > 0 ) {
			$args['date_query'] = array(
				array(
					'after'     => $days . ' days ago',
					'inclusive' => true,
				),
			);
		}

		$query       = new WP_Query( $args );
		$total_found = $query->found_posts;
		$total_pages = $query->max_num_pages;

		// Grid HTML.
		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				echo wzp_render_product_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			wp_reset_postdata();
		} else {
			echo '<div class="wzp-na__empty">';
			echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="wzp-na__empty-icon"><circle cx="32" cy="32" r="28"/><path d="M32 20v12l8 4"/></svg>';
			echo '<p>' . esc_html__( 'No new arrivals in this period.', 'woo-zee-plugin' ) . '</p>';
			echo '</div>';
		}
		$grid_html = ob_get_clean();

		// Pagination HTML.
		$pagination_html = wzp_shop_pagination( $page, $total_pages );

		// Count text.
		$count_text = $total_found > 0
			? sprintf(
				_n( '%s product', '%s products', $total_found, 'woo-zee-plugin' ),
				'<strong>' . number_format_i18n( $total_found ) . '</strong>'
			)
			: __( 'No products found', 'woo-zee-plugin' );

		wp_send_json_success( array(
			'grid'       => $grid_html,
			'pagination' => $pagination_html,
			'count_text' => $count_text,
			'found'      => $total_found,
		) );
	}

	/**
	 * AJAX: Delete a single newsletter subscriber by ID.
	 */
	public static function ajax_newsletter_delete() {
		check_ajax_referer( 'wzp_newsletter_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$id = absint( $_POST['id'] ?? 0 );
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Invalid ID.' ) );
		}

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'wzp_newsletter_emails', array( 'id' => $id ), array( '%d' ) );

		wp_send_json_success( array( 'id' => $id ) );
	}

	/**
	 * AJAX: Bulk-delete newsletter subscribers by IDs.
	 */
	public static function ajax_newsletter_bulk_delete() {
		check_ajax_referer( 'wzp_newsletter_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();
		$ids = array_filter( $ids );

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => 'No IDs provided.' ) );
		}

		global $wpdb;
		$table       = $wpdb->prefix . 'wzp_newsletter_emails';
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", ...$ids ) );

		wp_send_json_success( array( 'ids' => $ids ) );
	}

	/**
	 * AJAX: Export all subscribers as a CSV download.
	 * Streams directly — no wp_send_json.
	 */
	public static function ajax_newsletter_export() {
		check_ajax_referer( 'wzp_newsletter_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized', 403 );
		}

		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT email, status, subscribed_at FROM {$wpdb->prefix}wzp_newsletter_emails ORDER BY subscribed_at DESC",
			ARRAY_A
		);

		$filename = 'newsletter-subscribers-' . date( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$out = fopen( 'php://output', 'w' );
		// UTF-8 BOM so Excel opens it correctly.
		fputs( $out, "\xEF\xBB\xBF" );
		fputcsv( $out, array( 'Email', 'Status', 'Subscribed At' ) );

		foreach ( $rows as $row ) {
			fputcsv( $out, array( $row['email'], $row['status'], $row['subscribed_at'] ) );
		}

		fclose( $out );
		exit;
	}

	/**
	 * AJAX: Category Products — sort / paginate.
	 */
	public static function ajax_category_products() {
		check_ajax_referer( 'wzp_cp_nonce', 'nonce' );

		require_once WZP_PATH . 'modules/product-grid/card.php';
		require_once WZP_PATH . 'modules/shop/query.php';

		// phpcs:disable WordPress.Security.NonceVerification
		$cat_slug = sanitize_title( $_POST['cat'] ?? '' );
		$orderby  = sanitize_key( $_POST['orderby'] ?? 'date' );
		$page     = max( 1, intval( $_POST['page'] ?? 1 ) );
		$per_page = max( 4, min( 60, intval( $_POST['per_page'] ?? 12 ) ) );
		// phpcs:enable

		if ( ! $cat_slug ) {
			wp_send_json_error( array( 'message' => 'Missing category.' ) );
		}

		$query_args  = wzp_build_shop_query_args( array(
			'per_page' => $per_page,
			'page'     => $page,
			'orderby'  => $orderby,
			'cats'     => array( $cat_slug ),
		) );

		$query       = new WP_Query( $query_args );
		$total_found = $query->found_posts;
		$total_pages = $query->max_num_pages;
		$from        = ( ( $page - 1 ) * $per_page ) + 1;
		$to          = min( $page * $per_page, $total_found );

		// Grid HTML.
		ob_start();
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				echo wzp_render_product_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			wp_reset_postdata();
		} else {
			echo '<div class="wzp-cp__empty">';
			echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="wzp-cp__empty-icon"><circle cx="32" cy="32" r="28"/><path d="M20 32h24M32 20v24"/></svg>';
			echo '<p>' . esc_html__( 'No products in this category yet.', 'woo-zee-plugin' ) . '</p>';
			echo '</div>';
		}
		$grid_html = ob_get_clean();

		// Count text.
		if ( $total_found > 0 ) {
			$count_text = sprintf(
				__( 'Showing %1$s–%2$s of %3$s products', 'woo-zee-plugin' ),
				'<strong>' . number_format_i18n( $from ) . '</strong>',
				'<strong>' . number_format_i18n( $to ) . '</strong>',
				'<strong>' . number_format_i18n( $total_found ) . '</strong>'
			);
		} else {
			$count_text = __( 'No products found', 'woo-zee-plugin' );
		}

		wp_send_json_success( array(
			'grid'       => $grid_html,
			'pagination' => wzp_shop_pagination( $page, $total_pages ),
			'count_text' => $count_text,
			'found'      => $total_found,
		) );
	}
}
