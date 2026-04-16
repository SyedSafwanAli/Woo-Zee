<?php
/**
 * Plugin Name:       Woo Zee Plugin
 * Plugin URI:        https://example.com/woo-zee-plugin
 * Description:       A production-ready WooCommerce companion plugin with sliders, grids, carousels, lookbook, testimonials, and Instagram feed shortcodes.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       woo-zee-plugin
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ────────────────────────────────────────────────────────────────
define( 'WZP_VERSION',  '1.0.0' );
define( 'WZP_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WZP_URL',      plugin_dir_url( __FILE__ ) );
define( 'WZP_BASENAME', plugin_basename( __FILE__ ) );

// ── WooCommerce dependency check ─────────────────────────────────────────────
if ( ! function_exists( 'wzp_check_woocommerce' ) ) {
	function wzp_check_woocommerce() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', 'wzp_missing_woocommerce_notice' );
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'wzp_missing_woocommerce_notice' ) ) {
	function wzp_missing_woocommerce_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: WooCommerce plugin link */
						__( '<strong>Woo Zee Plugin</strong> requires %s to be installed and active.', 'woo-zee-plugin' ),
						'<a href="' . esc_url( admin_url( 'plugin-install.php?s=woocommerce&tab=search&type=term' ) ) . '">WooCommerce</a>'
					),
					array(
						'strong' => array(),
						'a'      => array( 'href' => array() ),
					)
				);
				?>
			</p>
		</div>
		<?php
	}
}

// ── Include class files ───────────────────────────────────────────────────────
require_once WZP_PATH . 'includes/class-wzp-helpers.php';
require_once WZP_PATH . 'includes/class-wzp-assets.php';
require_once WZP_PATH . 'includes/class-wzp-admin.php';
require_once WZP_PATH . 'includes/class-wzp-shortcodes.php';
require_once WZP_PATH . 'includes/class-wzp-core.php';

// ── Bootstrap ─────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function () {
	if ( ! wzp_check_woocommerce() ) {
		return;
	}
	WZP_Core::init();
} );
