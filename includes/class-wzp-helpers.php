<?php
/**
 * Shared utility/helper methods.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

class WZP_Helpers {

	const ENC_PREFIX = 'wzp_enc:';

	/**
	 * Encrypt a plaintext string using AES-256-CBC keyed from AUTH_KEY.
	 * Returns a prefixed base64 string safe for wp_options storage.
	 *
	 * @param string $value Plaintext.
	 * @return string Encrypted, prefixed value.
	 */
	public static function encrypt( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		$key    = substr( hash( 'sha256', AUTH_KEY ), 0, 32 );
		$iv_len = openssl_cipher_iv_length( 'AES-256-CBC' );
		$iv     = openssl_random_pseudo_bytes( $iv_len );

		$encrypted = openssl_encrypt( $value, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $encrypted ) {
			return $value; // Fallback: store plaintext if openssl unavailable.
		}

		return self::ENC_PREFIX . base64_encode( $iv . $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/**
	 * Decrypt a value previously encrypted with self::encrypt().
	 * Handles legacy plaintext values transparently.
	 *
	 * @param string $value Encrypted or plaintext value.
	 * @return string Decrypted plaintext.
	 */
	public static function decrypt( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		// Not encrypted (legacy plaintext) — return as-is.
		if ( strpos( $value, self::ENC_PREFIX ) !== 0 ) {
			return $value;
		}

		$data   = base64_decode( substr( $value, strlen( self::ENC_PREFIX ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
		$key    = substr( hash( 'sha256', AUTH_KEY ), 0, 32 );
		$iv_len = openssl_cipher_iv_length( 'AES-256-CBC' );

		if ( strlen( $data ) <= $iv_len ) {
			return '';
		}

		$iv        = substr( $data, 0, $iv_len );
		$encrypted = substr( $data, $iv_len );

		$decrypted = openssl_decrypt( $encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
		return ( false === $decrypted ) ? '' : $decrypted;
	}

	/**
	 * Sanitise SVG markup using a strict wp_kses() whitelist.
	 * Removes <script>, event handlers (on*), javascript: hrefs, <use>,
	 * <image>, <foreignObject>, and any tag/attribute not in the whitelist.
	 *
	 * @param string $svg Raw SVG markup.
	 * @return string Sanitised SVG markup.
	 */
	public static function sanitize_svg( $svg ) {
		$allowed_tags = array(
			'svg'            => array(
				'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true,
				'fill' => true, 'stroke' => true, 'stroke-width' => true,
				'stroke-linecap' => true, 'stroke-linejoin' => true,
				'stroke-miterlimit' => true, 'stroke-dasharray' => true,
				'stroke-dashoffset' => true, 'fill-rule' => true,
				'clip-rule' => true, 'opacity' => true, 'aria-hidden' => true,
				'class' => true, 'style' => true, 'preserveaspectratio' => true,
				'role' => true,
			),
			'g'              => array(
				'fill' => true, 'stroke' => true, 'stroke-width' => true,
				'opacity' => true, 'class' => true, 'transform' => true,
				'fill-rule' => true, 'clip-rule' => true,
			),
			'path'           => array(
				'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true,
				'stroke-linecap' => true, 'stroke-linejoin' => true,
				'fill-rule' => true, 'clip-rule' => true, 'opacity' => true,
				'class' => true, 'transform' => true,
			),
			'circle'         => array(
				'cx' => true, 'cy' => true, 'r' => true, 'fill' => true,
				'stroke' => true, 'stroke-width' => true, 'opacity' => true,
				'class' => true, 'transform' => true,
			),
			'ellipse'        => array(
				'cx' => true, 'cy' => true, 'rx' => true, 'ry' => true,
				'fill' => true, 'stroke' => true, 'stroke-width' => true,
				'opacity' => true, 'class' => true, 'transform' => true,
			),
			'rect'           => array(
				'x' => true, 'y' => true, 'width' => true, 'height' => true,
				'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true,
				'stroke-width' => true, 'opacity' => true, 'class' => true,
				'transform' => true,
			),
			'line'           => array(
				'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true,
				'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true,
				'opacity' => true, 'class' => true, 'transform' => true,
			),
			'polyline'       => array(
				'points' => true, 'fill' => true, 'stroke' => true,
				'stroke-width' => true, 'stroke-linecap' => true,
				'stroke-linejoin' => true, 'opacity' => true, 'class' => true,
				'transform' => true,
			),
			'polygon'        => array(
				'points' => true, 'fill' => true, 'stroke' => true,
				'stroke-width' => true, 'opacity' => true, 'class' => true,
				'transform' => true,
			),
			'defs'           => array(),
			'clippath'       => array( 'id' => true ),
			'lineargradient' => array(
				'id' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true,
				'gradientunits' => true, 'gradienttransform' => true,
			),
			'radialgradient' => array(
				'id' => true, 'cx' => true, 'cy' => true, 'r' => true, 'fx' => true,
				'fy' => true, 'gradientunits' => true,
			),
			'stop'           => array(
				'offset' => true, 'stop-color' => true, 'stop-opacity' => true,
			),
			'title'          => array(),
			'desc'           => array(),
		);

		return wp_kses( $svg, $allowed_tags );
	}

	/**
	 * Return the full attachment URL for a given attachment ID.
	 * Falls back to an empty string when the ID is invalid or the file
	 * cannot be found.
	 *
	 * @param int $id WordPress attachment post ID.
	 * @return string Absolute URL, or empty string on failure.
	 */
	public static function get_attachment_url( $id ) {
		$id = absint( $id );

		if ( ! $id ) {
			return '';
		}

		$url = wp_get_attachment_url( $id );

		return $url ? esc_url( $url ) : '';
	}

	/**
	 * Return an array of WooCommerce product categories formatted for
	 * use in dropdowns / option lists.
	 *
	 * Each entry contains:
	 *   'term_id' (int), 'name' (string), 'slug' (string), 'count' (int)
	 *
	 * Returns an empty array when WooCommerce is unavailable or there are
	 * no published categories.
	 *
	 * @param array $args Optional arguments passed to get_terms().
	 * @return array
	 */
	public static function get_wc_categories( array $args = array() ) {
		if ( ! function_exists( 'WC' ) && ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$defaults = array(
			'taxonomy'   => 'product_cat',
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => true,
		);

		$terms = get_terms( wp_parse_args( $args, $defaults ) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		$categories = array();
		foreach ( $terms as $term ) {
			$categories[] = array(
				'term_id' => absint( $term->term_id ),
				'name'    => esc_html( $term->name ),
				'slug'    => sanitize_title( $term->slug ),
				'count'   => absint( $term->count ),
			);
		}

		return $categories;
	}

	/**
	 * Recursively sanitise every value in an array using sanitize_text_field().
	 * Nested arrays are handled via recursion; non-array scalars are cast to
	 * string before sanitisation.
	 *
	 * @param array $array The array to sanitise.
	 * @return array Sanitised copy of the input array.
	 */
	public static function sanitize_array( array $array ) {
		$sanitized = array();

		foreach ( $array as $key => $value ) {
			$clean_key = sanitize_key( $key );

			if ( is_array( $value ) ) {
				$sanitized[ $clean_key ] = self::sanitize_array( $value );
			} else {
				$sanitized[ $clean_key ] = sanitize_text_field( (string) $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Return an array of all available category icon files bundled with the plugin.
	 * Each entry: [ 'filename' => 'Earrings.webp', 'label' => 'Earrings', 'url' => '...' ]
	 *
	 * @return array
	 */
	public static function get_category_icons() {
		$dir   = WZP_PATH . 'assets/images/category-icons/';
		$exts  = array( 'webp', 'png', 'svg' );
		$files = array();

		foreach ( $exts as $ext ) {
			$matches = glob( $dir . '*.' . $ext );
			if ( $matches ) {
				$files = array_merge( $files, $matches );
			}
		}

		if ( empty( $files ) ) {
			return array();
		}

		$icons = array();
		foreach ( $files as $file ) {
			$filename = basename( $file );
			$label    = ucwords( str_replace( array( '-', '_' ), ' ', pathinfo( $filename, PATHINFO_FILENAME ) ) );
			$icons[]  = array(
				'filename' => $filename,
				'label'    => $label,
				'url'      => WZP_URL . 'assets/images/category-icons/' . rawurlencode( $filename ),
			);
		}

		usort( $icons, fn( $a, $b ) => strcmp( $a['label'], $b['label'] ) );

		return $icons;
	}

	/**
	 * Return the URL of the assigned icon for a product category term,
	 * or empty string if none is assigned.
	 *
	 * @param int $term_id WooCommerce product_cat term ID.
	 * @return string URL or empty string.
	 */
	public static function get_category_icon_url( $term_id ) {
		$assignments = (array) get_option( 'wzp_category_icons', array() );
		$filename    = $assignments[ absint( $term_id ) ] ?? '';

		if ( ! $filename ) {
			return '';
		}

		$path = WZP_PATH . 'assets/images/category-icons/' . $filename;

		if ( ! file_exists( $path ) ) {
			return '';
		}

		return WZP_URL . 'assets/images/category-icons/' . rawurlencode( $filename );
	}

	/**
	 * Return ready-to-echo HTML for a category icon.
	 * SVG files are output inline (avoids MIME-type issues).
	 * PNG/WebP return an <img> tag.
	 * Returns empty string when no icon is assigned.
	 *
	 * @param int    $term_id   WooCommerce product_cat term ID.
	 * @param string $css_class Extra class for the wrapper element.
	 * @return string HTML or empty string.
	 */
	public static function get_category_icon_html( $term_id, $css_class = '' ) {
		$assignments = (array) get_option( 'wzp_category_icons', array() );
		$filename    = $assignments[ absint( $term_id ) ] ?? '';

		if ( ! $filename ) {
			return '';
		}

		$path = WZP_PATH . 'assets/images/category-icons/' . $filename;

		if ( ! file_exists( $path ) ) {
			return '';
		}

		$class = $css_class ? ' class="' . esc_attr( $css_class ) . '"' : '';

		if ( 'svg' === strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$svg = self::sanitize_svg( file_get_contents( $path ) );
			return '<span' . $class . ' aria-hidden="true">' . $svg . '</span>';
		}

		$url = WZP_URL . 'assets/images/category-icons/' . rawurlencode( $filename );
		return '<img' . $class . ' src="' . esc_url( $url ) . '" alt="" loading="lazy">';
	}
}

// ── Procedural aliases (optional convenience wrappers) ────────────────────────
// These thin wrappers allow templates to call wzp_*() functions directly
// without referencing the class, keeping render files clean.

if ( ! function_exists( 'wzp_get_attachment_url' ) ) {
	function wzp_get_attachment_url( $id ) {
		return WZP_Helpers::get_attachment_url( $id );
	}
}

if ( ! function_exists( 'wzp_get_wc_categories' ) ) {
	function wzp_get_wc_categories( array $args = array() ) {
		return WZP_Helpers::get_wc_categories( $args );
	}
}

if ( ! function_exists( 'wzp_sanitize_array' ) ) {
	function wzp_sanitize_array( array $array ) {
		return WZP_Helpers::sanitize_array( $array );
	}
}

if ( ! function_exists( 'wzp_get_category_icons' ) ) {
	function wzp_get_category_icons() {
		return WZP_Helpers::get_category_icons();
	}
}

if ( ! function_exists( 'wzp_get_category_icon_url' ) ) {
	function wzp_get_category_icon_url( $term_id ) {
		return WZP_Helpers::get_category_icon_url( $term_id );
	}
}

if ( ! function_exists( 'wzp_get_category_icon_html' ) ) {
	function wzp_get_category_icon_html( $term_id, $css_class = '' ) {
		return WZP_Helpers::get_category_icon_html( $term_id, $css_class );
	}
}

if ( ! function_exists( 'wzp_sanitize_svg' ) ) {
	function wzp_sanitize_svg( $svg ) {
		return WZP_Helpers::sanitize_svg( $svg );
	}
}
