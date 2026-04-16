<?php
/**
 * Shared utility/helper methods.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

class WZP_Helpers {

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
