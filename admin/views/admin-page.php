<?php
/**
 * Admin page view — "Woo Zee Plugin Settings" page.
 *
 * Loaded by WZP_Admin::render_admin_page().
 * Capability check already performed by the caller.
 *
 * Tabs:
 *   hero-slider      — Hero Slider slide manager
 *   product-grid     — Product Grid defaults + live shortcode preview
 *   product-carousel — Product Carousel defaults + live shortcode preview
 *   lookbook         — Lookbook image + hotspots
 *   testimonials     — Testimonials / reviews manager
 *   instagram-feed   — Instagram Feed API settings
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

// ── Active tab ────────────────────────────────────────────────────────────────
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'hero-slider';

$tabs = array(
	'card-style'         => __( 'Card Style',          'woo-zee-plugin' ),
	'hero-slider'        => __( 'Hero Slider',        'woo-zee-plugin' ),
	'product-grid'       => __( 'Product Grid',        'woo-zee-plugin' ),
	'product-carousel'   => __( 'Product Carousel',    'woo-zee-plugin' ),
	'category-carousel'  => __( 'Category Carousel',   'woo-zee-plugin' ),
	'category-icons'     => __( 'Category Icons',      'woo-zee-plugin' ),
	'banner-cards'       => __( 'Banner Cards',        'woo-zee-plugin' ),
	'single-banner'      => __( 'Single Banner',       'woo-zee-plugin' ),
	'lookbook'           => __( 'Lookbook',            'woo-zee-plugin' ),
	'testimonials'       => __( 'Testimonials',        'woo-zee-plugin' ),
	'instagram-feed'     => __( 'Instagram Feed',      'woo-zee-plugin' ),
	'navbar'             => __( 'Navbar',              'woo-zee-plugin' ),
	'product-detail'     => __( 'Product Detail',      'woo-zee-plugin' ),
	'newsletter'         => __( 'Newsletter',          'woo-zee-plugin' ),
);

// Validate — fall back to first tab if an unknown slug is supplied.
if ( ! array_key_exists( $active_tab, $tabs ) ) {
	$active_tab = 'hero-slider';
}

// ── Saved options ─────────────────────────────────────────────────────────────
$hero_slides = (array) get_option( 'wzp_hero_slides', array() );

$lookbook_opts = wp_parse_args(
	(array) get_option( 'wzp_lookbook_options', array() ),
	array(
		'image_id'    => 0,
		'label'       => '',
		'heading'     => '',
		'description' => '',
		'btn_text'    => '',
		'btn_url'     => '',
		'hotspots'    => array(),
	)
);
$lookbook_hotspots = is_array( $lookbook_opts['hotspots'] ) ? $lookbook_opts['hotspots'] : array();

$ig_opts = wp_parse_args(
	(array) get_option( 'wzp_instagram_options', array() ),
	array(
		'access_token' => '',
		'username'     => '',
		'count'        => 6,
	)
);

$testimonials_data = array_values( array_filter(
	(array) get_option( 'wzp_testimonials_data', array() ),
	'is_array'
) );

$lookbook_thumb_url = $lookbook_opts['image_id']
	? wp_get_attachment_image_url( absint( $lookbook_opts['image_id'] ), 'medium' )
	: '';

$lookbook_large_url = $lookbook_opts['image_id']
	? ( wp_get_attachment_image_url( absint( $lookbook_opts['image_id'] ), 'large' ) ?: wp_get_attachment_image_url( absint( $lookbook_opts['image_id'] ), 'full' ) )
	: '';

$sb_opts = wp_parse_args(
	(array) get_option( 'wzp_single_banner_options', array() ),
	array(
		'image_id'    => 0,
		'label'       => '',
		'heading'     => '',
		'description' => '',
		'btn_text'    => '',
		'btn_url'     => '',
		'align'       => 'left',
		'height'      => 420,
	)
);
$sb_thumb_url = $sb_opts['image_id']
	? wp_get_attachment_image_url( absint( $sb_opts['image_id'] ), 'medium' )
	: '';

$card_style_opts = wp_parse_args(
	(array) get_option( 'wzp_card_style_options', array() ),
	array(
		'primary'       => '#1a1a1a',
		'secondary'     => '#4a4a4a',
		'accent'        => '#c9a96e',
		'surface'       => '#ffffff',
		'surface_alt'   => '#f7f6f4',
		'border'        => '#e8e4df',
		'title_size'    => 15,
		'price_size'    => 14,
		'cat_size'      => 10,
		'show_rating'   => '1',
		'show_category' => '1',
		'show_wishlist' => '1',
		'show_badge'    => '1',
		'show_quickadd' => '1',
	)
);

$grid_opts = wp_parse_args(
	(array) get_option( 'wzp_product_grid_options', array() ),
	array(
		'category' => '',
		'columns'  => '3',
		'count'    => '8',
		'orderby'  => 'date',
	)
);

$carousel_opts = wp_parse_args(
	(array) get_option( 'wzp_product_carousel_options', array() ),
	array(
		'category' => '',
		'count'    => '8',
		'per_view' => '3',
		'autoplay' => 'true',
		'speed'    => '3000',
	)
);

$cat_carousel_opts = wp_parse_args(
	(array) get_option( 'wzp_cat_carousel_options', array() ),
	array(
		'per_view'   => '7',
		'icon_size'  => '48',
		'orderby'    => 'name',
		'hide_empty' => 'true',
	)
);

// ── Navbar settings ───────────────────────────────────────────────────────────
$navbar_settings = wp_parse_args(
	(array) get_option( 'wzp_navbar_settings', array() ),
	array(
		'logo_type'     => 'text',
		'logo_id'       => 0,
		'logo_text'     => get_bloginfo( 'name' ),
		'logo_url'      => '',
		'menu_id'       => '',
		'account_url'   => '',
		'wishlist_url'  => '',
		'cart_url'      => '',
		'show_search'   => '1',
		'show_account'  => '1',
		'show_wishlist' => '1',
		'show_cart'     => '1',
		'sticky'        => '0',
		'bg_color'      => '#ffffff',
		'text_color'    => '#111111',
		'hover_color'   => '#888888',
		'border_color'  => '#efefef',
		'active_color'  => '#111111',
	)
);
$nb_logo_preview = ( (int) $navbar_settings['logo_id'] > 0 )
	? wp_get_attachment_image_url( (int) $navbar_settings['logo_id'], 'medium' )
	: '';
$saved_menus = array_values( array_filter(
	(array) get_option( 'wzp_saved_menus', array() ),
	function ( $m ) { return ! empty( $m['id'] ); }
) );

// ── Product Detail settings ───────────────────────────────────────────────────
$pd_icon_options = array(
	'leaf'    => 'Leaf — Sustainable',
	'return'  => 'Return — Refund',
	'diamond' => 'Diamond — Premium',
	'truck'   => 'Truck — Shipping',
	'package' => 'Package — Delivery',
	'shield'  => 'Shield — Guarantee',
	'heart'   => 'Heart — Care',
	'globe'   => 'Globe — Worldwide',
	'lock'    => 'Lock — Secure',
	'clock'   => 'Clock — Time',
	'check'   => 'Check — Verified',
	'gift'    => 'Gift',
	'star'    => 'Star — Excellence',
);

$pd_settings = wp_parse_args(
	(array) get_option( 'wzp_product_detail_settings', array() ),
	array(
		'benefits'     => array(
			array( 'icon' => 'leaf',    'title' => 'Sustainable Materials', 'subtitle' => 'Thoughtfully sourced, earth-friendly fabrics.' ),
			array( 'icon' => 'return',  'title' => '30 Days Free Returns',  'subtitle' => 'Changed your mind? No problem.' ),
			array( 'icon' => 'diamond', 'title' => 'Premium Quality',       'subtitle' => 'Every piece is handcrafted with care.' ),
			array( 'icon' => 'truck',   'title' => 'Free Shipping',         'subtitle' => 'Complimentary on all orders over $50.' ),
		),
		'shipping'     => array(
			array( 'icon' => 'truck',   'text' => 'Free standard shipping on orders over $50.' ),
			array( 'icon' => 'package', 'text' => 'Estimated delivery: 3–5 business days.' ),
		),
		'accent_color' => '#c9a96e',
		'btn_color'    => '#1a1a1a',
		'btn_text'     => '#ffffff',
		'price_color'  => '#1a1a1a',
	)
);

// ── Newsletter subscribers ────────────────────────────────────────────────────
if ( 'newsletter' === $active_tab ) {
	global $wpdb;
	$nl_rows = (array) $wpdb->get_results(
		"SELECT id, email, status, subscribed_at FROM {$wpdb->prefix}wzp_newsletter_emails ORDER BY subscribed_at DESC",
		ARRAY_A
	);
	$nl_total = count( $nl_rows );
} else {
	$nl_rows  = array();
	$nl_total = 0;
}

// ── WC categories for the dropdowns ──────────────────────────────────────────
$wc_categories = wzp_get_wc_categories();
?>
<div class="wrap wzp-admin-wrap">

	<h1 class="wzp-admin-heading">
		<span class="dashicons dashicons-store"></span>
		<?php esc_html_e( 'Woo Zee Plugin Settings', 'woo-zee-plugin' ); ?>
	</h1>

	<?php settings_errors( 'wzp_messages' ); ?>

	<?php
	// Localise JS strings inline (admin-script.js depends on window.wzpAdmin).
	wp_localize_script(
		'wzp-admin-script',
		'wzpAdmin',
		array(
			// Media picker labels
			'labelSelectImage' => __( 'Select Image',        'woo-zee-plugin' ),
			'labelChangeImage' => __( 'Change Image',        'woo-zee-plugin' ),
			'labelRemoveImage' => __( 'Remove image',        'woo-zee-plugin' ),
			'labelUpload'      => __( 'Upload',              'woo-zee-plugin' ),
			'labelMediaTitle'  => __( 'Select Slide Image',  'woo-zee-plugin' ),
			'labelAvatarTitle' => __( 'Select Avatar Image', 'woo-zee-plugin' ),
			'labelMediaButton' => __( 'Use this image',      'woo-zee-plugin' ),
			// Token field
			'labelShow'        => __( 'Show',                'woo-zee-plugin' ),
			'labelHide'        => __( 'Hide',                'woo-zee-plugin' ),
			// Instagram AJAX
			'ajaxUrl'          => esc_url( admin_url( 'admin-ajax.php' ) ),
			'igNonce'          => wp_create_nonce( 'wzp_instagram_nonce' ),
			'igTesting'        => __( 'Testing connection…', 'woo-zee-plugin' ),
			'igNoToken'        => __( 'Please enter an access token first.', 'woo-zee-plugin' ),
			'igError'          => __( 'Connection failed. Please try again.', 'woo-zee-plugin' ),
			// Icon library AJAX
			'uploadIconNonce'  => wp_create_nonce( 'wzp_upload_icon_nonce' ),
			'deleteIconNonce'  => wp_create_nonce( 'wzp_delete_icon_nonce' ),
			'svgIconNonce'     => wp_create_nonce( 'wzp_save_svg_icon_nonce' ),
			'confirmDelete'        => __( 'Delete this icon? Categories using it will fall back to the default.', 'woo-zee-plugin' ),
			// Lookbook product search
			'searchProductsNonce'  => wp_create_nonce( 'wzp_search_products_nonce' ),
			'labelAssignProduct'   => __( 'Assign Product', 'woo-zee-plugin' ),
			'labelSearchProducts'  => __( 'Search by name or ID…', 'woo-zee-plugin' ),
			'labelSearching'       => __( 'Searching…', 'woo-zee-plugin' ),
			'labelNoProducts'      => __( 'No products found.', 'woo-zee-plugin' ),
			'labelRemovePin'       => __( 'Remove this pin', 'woo-zee-plugin' ),
			'labelClickToAdd'      => __( 'Click on the image to place a hotspot pin.', 'woo-zee-plugin' ),
			// Product Grid Manager AJAX
			'gridNonce'            => wp_create_nonce( 'wzp_grid_nonce' ),
			'labelConfirmDelete'   => __( 'Delete this grid?', 'woo-zee-plugin' ),
			// Navbar Settings AJAX
			'navbarNonce'          => wp_create_nonce( 'wzp_navbar_nonce' ),
			// Menu builder AJAX
			'menuNonce'            => wp_create_nonce( 'wzp_menu_nonce' ),
			'savedMenus'           => $saved_menus,
			// Product Detail AJAX
			'productDetailNonce'   => wp_create_nonce( 'wzp_product_detail_nonce' ),
			// Newsletter AJAX
			'newsletterAdminNonce' => wp_create_nonce( 'wzp_newsletter_admin_nonce' ),
			'labelNewGrid'         => __( 'New Grid', 'woo-zee-plugin' ),
			'labelEditGrid'        => __( 'Edit Grid', 'woo-zee-plugin' ),
			'wcCategories'         => array_map( function( $cat ) {
				return array( 'slug' => $cat['slug'], 'name' => $cat['name'] );
			}, $wc_categories ),
		)
	);
	?>

	<?php /* ── Tab navigation ──────────────────────────────────────────────── */ ?>
	<nav class="wzp-tabs" aria-label="<?php esc_attr_e( 'Plugin sections', 'woo-zee-plugin' ); ?>">
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'woo-zee-plugin', 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>"
			   class="wzp-tab-link<?php echo $active_tab === $slug ? ' wzp-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<div class="wzp-tab-panels">

		<?php /* ══════════════════════════════════════════════════════════════
		   TAB 0 — Card Style
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'card-style' === $active_tab ) : ?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-card-style">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Card Style', 'woo-zee-plugin' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Customise colours, font sizes and visible elements for all product cards across your site.', 'woo-zee-plugin' ); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields( 'wzp_card_style_group' ); ?>

				<?php /* ─ Colours ─────────────────────────────────────────── */ ?>
				<h3 class="wzp-subsection-title"><?php esc_html_e( 'Colours', 'woo-zee-plugin' ); ?></h3>
				<table class="wzp-form-table">
					<tr>
						<th><label for="wzp_cs_primary"><?php esc_html_e( 'Primary (text / buttons)', 'woo-zee-plugin' ); ?></label></th>
						<td>
							<input type="color" id="wzp_cs_primary" name="wzp_card_style_options[primary]"
							       value="<?php echo esc_attr( $card_style_opts['primary'] ); ?>">
							<span class="description"><?php esc_html_e( 'Main text, borders, solid buttons.', 'woo-zee-plugin' ); ?></span>
						</td>
					</tr>
					<tr>
						<th><label for="wzp_cs_accent"><?php esc_html_e( 'Accent (gold)', 'woo-zee-plugin' ); ?></label></th>
						<td>
							<input type="color" id="wzp_cs_accent" name="wzp_card_style_options[accent]"
							       value="<?php echo esc_attr( $card_style_opts['accent'] ); ?>">
							<span class="description"><?php esc_html_e( 'Category label, sale price, wishlist fill.', 'woo-zee-plugin' ); ?></span>
						</td>
					</tr>
					<tr>
						<th><label for="wzp_cs_surface"><?php esc_html_e( 'Card background', 'woo-zee-plugin' ); ?></label></th>
						<td>
							<input type="color" id="wzp_cs_surface" name="wzp_card_style_options[surface]"
							       value="<?php echo esc_attr( $card_style_opts['surface'] ); ?>">
						</td>
					</tr>
					<tr>
						<th><label for="wzp_cs_surface_alt"><?php esc_html_e( 'Image background', 'woo-zee-plugin' ); ?></label></th>
						<td>
							<input type="color" id="wzp_cs_surface_alt" name="wzp_card_style_options[surface_alt]"
							       value="<?php echo esc_attr( $card_style_opts['surface_alt'] ); ?>">
							<span class="description"><?php esc_html_e( 'Shown while image loads.', 'woo-zee-plugin' ); ?></span>
						</td>
					</tr>
					<tr>
						<th><label for="wzp_cs_border"><?php esc_html_e( 'Border / divider', 'woo-zee-plugin' ); ?></label></th>
						<td>
							<input type="color" id="wzp_cs_border" name="wzp_card_style_options[border]"
							       value="<?php echo esc_attr( $card_style_opts['border'] ); ?>">
						</td>
					</tr>
				</table>

				<?php /* ─ Typography ─────────────────────────────────────── */ ?>
				<h3 class="wzp-subsection-title"><?php esc_html_e( 'Typography', 'woo-zee-plugin' ); ?></h3>
				<table class="wzp-form-table">
					<tr>
						<th><label for="wzp_cs_title_size"><?php esc_html_e( 'Title font size (px)', 'woo-zee-plugin' ); ?></label></th>
						<td>
							<input type="number" id="wzp_cs_title_size" name="wzp_card_style_options[title_size]"
							       value="<?php echo esc_attr( $card_style_opts['title_size'] ); ?>"
							       min="10" max="28" step="1" style="width:80px">
						</td>
					</tr>
					<tr>
						<th><label for="wzp_cs_price_size"><?php esc_html_e( 'Price font size (px)', 'woo-zee-plugin' ); ?></label></th>
						<td>
							<input type="number" id="wzp_cs_price_size" name="wzp_card_style_options[price_size]"
							       value="<?php echo esc_attr( $card_style_opts['price_size'] ); ?>"
							       min="10" max="24" step="1" style="width:80px">
						</td>
					</tr>
					<tr>
						<th><label for="wzp_cs_cat_size"><?php esc_html_e( 'Category label size (px)', 'woo-zee-plugin' ); ?></label></th>
						<td>
							<input type="number" id="wzp_cs_cat_size" name="wzp_card_style_options[cat_size]"
							       value="<?php echo esc_attr( $card_style_opts['cat_size'] ); ?>"
							       min="8" max="16" step="1" style="width:80px">
						</td>
					</tr>
				</table>

				<?php /* ─ Visibility toggles ─────────────────────────────── */ ?>
				<h3 class="wzp-subsection-title"><?php esc_html_e( 'Show / Hide Elements', 'woo-zee-plugin' ); ?></h3>
				<table class="wzp-form-table">
					<?php
					$toggles = array(
						'show_badge'    => __( 'Sale / New badge',    'woo-zee-plugin' ),
						'show_category' => __( 'Category label',      'woo-zee-plugin' ),
						'show_rating'   => __( 'Star rating',         'woo-zee-plugin' ),
						'show_wishlist' => __( 'Wishlist button',     'woo-zee-plugin' ),
						'show_quickadd' => __( 'Add to Cart bar',     'woo-zee-plugin' ),
					);
					foreach ( $toggles as $key => $label ) :
					?>
					<tr>
						<th><?php echo esc_html( $label ); ?></th>
						<td>
							<label class="wzp-toggle-label">
								<input type="hidden"   name="wzp_card_style_options[<?php echo esc_attr( $key ); ?>]" value="0">
								<input type="checkbox" name="wzp_card_style_options[<?php echo esc_attr( $key ); ?>]" value="1"
								       class="wzp-toggle-checkbox"
								       <?php checked( '1', $card_style_opts[ $key ] ); ?>>
								<span class="wzp-toggle-track"></span>
								<span><?php echo esc_html( $label ); ?></span>
							</label>
						</td>
					</tr>
					<?php endforeach; ?>
				</table>

				<p><button type="submit" class="button button-primary wzp-submit-btn"><?php esc_html_e( 'Save Card Style', 'woo-zee-plugin' ); ?></button></p>
			</form>

		</div><?php /* /#wzp-tab-card-style */ ?>
		<?php endif; ?>

		<?php /* ══════════════════════════════════════════════════════════════
		   TAB 1 — Hero Slider
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'hero-slider' === $active_tab ) : ?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-hero-slider">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Hero Slider', 'woo-zee-plugin' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Add and arrange full-screen slides. Use the shortcode below to embed the slider on any page or template.', 'woo-zee-plugin' ); ?>
			</p>
			<p class="description">
				<strong><?php esc_html_e( 'Shortcode:', 'woo-zee-plugin' ); ?></strong>
				<code>[wzp_hero_slider]</code>
			</p>

			<form method="post" action="options.php" novalidate>
				<?php settings_fields( 'wzp_hero_group' ); ?>

				<div id="wzp-hero-slides-list"
				     data-next-index="<?php echo esc_attr( count( $hero_slides ) ); ?>">

					<?php foreach ( $hero_slides as $i => $slide ) : ?>
						<?php
						$thumb_url = '';
						if ( ! empty( $slide['image_id'] ) ) {
							$thumb_url = wp_get_attachment_image_url( absint( $slide['image_id'] ), 'thumbnail' );
						}
						?>
						<div class="wzp-slide-row" data-index="<?php echo esc_attr( $i ); ?>">

							<div class="wzp-slide-header">
								<span class="wzp-slide-handle" aria-hidden="true">⠿</span>
								<span class="wzp-slide-title">
									<?php echo esc_html( $slide['heading'] ?: __( '(No heading)', 'woo-zee-plugin' ) ); ?>
								</span>
								<button type="button"
								        class="wzp-slide-remove button-link button-link-delete"
								        aria-label="<?php esc_attr_e( 'Remove this slide', 'woo-zee-plugin' ); ?>">
									<?php esc_html_e( 'Remove', 'woo-zee-plugin' ); ?>
								</button>
							</div>

							<div class="wzp-slide-body">

								<?php /* Image picker ──────────────────── */ ?>
								<div class="wzp-slide-field wzp-slide-field--media">
									<label><?php esc_html_e( 'Slide Image', 'woo-zee-plugin' ); ?></label>
									<input type="hidden"
									       class="wzp-slide-image-id"
									       name="wzp_hero_slides[<?php echo esc_attr( $i ); ?>][image_id]"
									       value="<?php echo esc_attr( $slide['image_id'] ?? '' ); ?>">
									<div class="wzp-media-preview">
										<?php if ( $thumb_url ) : ?>
											<img src="<?php echo esc_url( $thumb_url ); ?>" alt="">
										<?php endif; ?>
									</div>
									<button type="button" class="button wzp-media-btn">
										<?php echo $thumb_url
											? esc_html__( 'Change Image', 'woo-zee-plugin' )
											: esc_html__( 'Select Image', 'woo-zee-plugin' ); ?>
									</button>
									<button type="button"
									        class="button-link button-link-delete wzp-media-remove"
									        style="<?php echo $thumb_url ? '' : 'display:none'; ?>">
										<?php esc_html_e( 'Remove image', 'woo-zee-plugin' ); ?>
									</button>
								</div>

								<?php /* Text fields ───────────────────── */ ?>
								<?php
								$text_fields = array(
									'label'       => array( 'label' => __( 'Label (eyebrow text)', 'woo-zee-plugin' ), 'required' => false ),
									'heading'     => array( 'label' => __( 'Heading',             'woo-zee-plugin' ), 'required' => true  ),
									'description' => array( 'label' => __( 'Description',         'woo-zee-plugin' ), 'required' => false ),
									'btn_text'    => array( 'label' => __( 'Button Text',         'woo-zee-plugin' ), 'required' => false ),
									'btn_url'     => array( 'label' => __( 'Button URL',          'woo-zee-plugin' ), 'required' => false ),
								);
								foreach ( $text_fields as $field_key => $field_cfg ) :
									$field_value = sanitize_text_field( $slide[ $field_key ] ?? '' );
									$input_class = 'wzp-slide-' . str_replace( '_', '-', $field_key );
									$is_url      = ( 'btn_url' === $field_key );
									$is_textarea = ( 'description' === $field_key );
								?>
								<div class="wzp-slide-field">
									<label>
										<?php echo esc_html( $field_cfg['label'] ); ?>
										<?php if ( $field_cfg['required'] ) : ?>
											<span class="wzp-required" aria-hidden="true">*</span>
										<?php endif; ?>
									</label>
									<?php if ( $is_textarea ) : ?>
										<textarea class="large-text <?php echo esc_attr( $input_class ); ?>"
										          name="wzp_hero_slides[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $field_key ); ?>]"
										          rows="3"><?php echo esc_textarea( $field_value ); ?></textarea>
									<?php else : ?>
										<input type="<?php echo $is_url ? 'url' : 'text'; ?>"
										       class="large-text <?php echo esc_attr( $input_class ); ?>"
										       name="wzp_hero_slides[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $field_key ); ?>]"
										       value="<?php echo esc_attr( $field_value ); ?>">
									<?php endif; ?>
								</div>
								<?php endforeach; ?>

							</div><?php /* /.wzp-slide-body */ ?>
						</div><?php /* /.wzp-slide-row */ ?>
					<?php endforeach; ?>

				</div><?php /* /#wzp-hero-slides-list */ ?>

				<button type="button" id="wzp-add-slide" class="button button-secondary">
					+ <?php esc_html_e( 'Add New Slide', 'woo-zee-plugin' ); ?>
				</button>

				<?php submit_button( __( 'Save Slides', 'woo-zee-plugin' ), 'primary wzp-submit-btn' ); ?>

			</form>

		</div>
		<?php endif; /* hero-slider */ ?>

		<?php /* ══════════════════════════════════════════════════════════════
		   TAB 2 — Product Grid
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'product-grid' === $active_tab ) : ?>
		<?php
		$saved_grids = array_values( array_filter(
			(array) get_option( 'wzp_saved_grids', array() ),
			'is_array'
		) );
		$orderby_labels = array(
			'date'       => __( 'Date (newest)',      'woo-zee-plugin' ),
			'popularity' => __( 'Popularity',          'woo-zee-plugin' ),
			'rating'     => __( 'Avg Rating',          'woo-zee-plugin' ),
			'price'      => __( 'Price (lowest first)', 'woo-zee-plugin' ),
		);
		?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-product-grid">

			<div class="wzp-gm-header">
				<div>
					<h2 class="wzp-section-title"><?php esc_html_e( 'Product Grid Manager', 'woo-zee-plugin' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Create unlimited grids, each with its own categories, columns, and sort order. Place them anywhere with the shortcode.', 'woo-zee-plugin' ); ?></p>
				</div>
				<button type="button" id="wzp-gm-add-btn" class="button button-primary wzp-gm-add-btn">
					+ <?php esc_html_e( 'Add New Grid', 'woo-zee-plugin' ); ?>
				</button>
			</div>

			<?php /* ── Inline form (hidden by default) ─────────────────────── */ ?>
			<div id="wzp-gm-form-wrap" class="wzp-gm-form-wrap" style="display:none;">
				<div class="wzp-gm-form">
					<h3 class="wzp-gm-form__title"><?php esc_html_e( 'New Grid', 'woo-zee-plugin' ); ?></h3>
					<input type="hidden" id="wzp-gm-id" value="">

					<div class="wzp-gm-field">
						<label for="wzp-gm-label"><?php esc_html_e( 'Grid Name', 'woo-zee-plugin' ); ?></label>
						<input type="text" id="wzp-gm-label" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Featured Rings', 'woo-zee-plugin' ); ?>">
					</div>

					<div class="wzp-gm-field">
						<label><?php esc_html_e( 'Categories', 'woo-zee-plugin' ); ?></label>
						<p class="description"><?php esc_html_e( 'Select one or more. Leave all unchecked to show all products.', 'woo-zee-plugin' ); ?></p>
						<div class="wzp-gm-cats" id="wzp-gm-cats">
							<?php foreach ( $wc_categories as $cat ) : ?>
							<label class="wzp-gm-cat-label">
								<input type="checkbox"
								       class="wzp-gm-cat-cb"
								       value="<?php echo esc_attr( $cat['slug'] ); ?>">
								<?php echo esc_html( $cat['name'] ); ?>
								<span class="wzp-gm-cat-count">(<?php echo esc_html( $cat['count'] ); ?>)</span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="wzp-gm-row">
						<div class="wzp-gm-field">
							<label><?php esc_html_e( 'Columns', 'woo-zee-plugin' ); ?></label>
							<div class="wzp-radio-group">
								<?php foreach ( array( '2', '3', '4', '5' ) as $col ) : ?>
								<label class="wzp-radio-label">
									<input type="radio" name="wzp-gm-columns" class="wzp-gm-columns" value="<?php echo esc_attr( $col ); ?>" <?php checked( $col, '4' ); ?>>
									<?php echo esc_html( $col ); ?>
								</label>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="wzp-gm-field">
							<label for="wzp-gm-count"><?php esc_html_e( 'Products', 'woo-zee-plugin' ); ?></label>
							<input type="number" id="wzp-gm-count" class="small-text" value="8" min="1" max="24" step="1">
						</div>

						<div class="wzp-gm-field">
							<label for="wzp-gm-orderby"><?php esc_html_e( 'Sort By', 'woo-zee-plugin' ); ?></label>
							<select id="wzp-gm-orderby" class="wzp-select">
								<?php foreach ( $orderby_labels as $val => $lbl ) : ?>
								<option value="<?php echo esc_attr( $val ); ?>"><?php echo esc_html( $lbl ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="wzp-gm-form__actions">
						<button type="button" id="wzp-gm-save-btn" class="button button-primary wzp-submit-btn">
							<?php esc_html_e( 'Save Grid', 'woo-zee-plugin' ); ?>
						</button>
						<button type="button" id="wzp-gm-cancel-btn" class="button">
							<?php esc_html_e( 'Cancel', 'woo-zee-plugin' ); ?>
						</button>
						<span id="wzp-gm-saving" class="wzp-gm-saving" style="display:none;"><?php esc_html_e( 'Saving…', 'woo-zee-plugin' ); ?></span>
					</div>
				</div>
			</div>

			<?php /* ── Saved grids list ────────────────────────────────────── */ ?>
			<div id="wzp-gm-list" class="wzp-gm-list">
				<?php if ( empty( $saved_grids ) ) : ?>
				<p class="wzp-gm-empty" id="wzp-gm-empty">
					<?php esc_html_e( 'No grids yet. Click "Add New Grid" to create your first one.', 'woo-zee-plugin' ); ?>
				</p>
				<?php else : ?>
				<?php foreach ( $saved_grids as $grid ) :
					$grid_cats    = is_array( $grid['categories'] ) ? $grid['categories'] : array();
					$cats_display = implode( ', ', array_map( function( $slug ) use ( $wc_categories ) {
						foreach ( $wc_categories as $c ) {
							if ( $c['slug'] === $slug ) { return $c['name']; }
						}
						return $slug;
					}, $grid_cats ) );
					$shortcode = '[wzp_product_grid grid_id="' . esc_attr( $grid['id'] ) . '"]';
				?>
				<div class="wzp-gm-card" data-grid-id="<?php echo esc_attr( $grid['id'] ); ?>">
					<div class="wzp-gm-card__info">
						<strong class="wzp-gm-card__name"><?php echo esc_html( $grid['label'] ?: __( '(Unnamed)', 'woo-zee-plugin' ) ); ?></strong>
						<span class="wzp-gm-card__meta">
							<?php if ( $cats_display ) : ?>
							<span><?php echo esc_html( $cats_display ); ?></span> &middot;
							<?php else : ?>
							<span><?php esc_html_e( 'All categories', 'woo-zee-plugin' ); ?></span> &middot;
							<?php endif; ?>
							<span><?php echo esc_html( $grid['columns'] ?? '3' ); ?> <?php esc_html_e( 'cols', 'woo-zee-plugin' ); ?></span> &middot;
							<span><?php echo esc_html( $grid['count'] ?? '8' ); ?> <?php esc_html_e( 'products', 'woo-zee-plugin' ); ?></span> &middot;
							<span><?php echo esc_html( $orderby_labels[ $grid['orderby'] ] ?? $grid['orderby'] ); ?></span>
						</span>
						<div class="wzp-gm-card__shortcode-row">
							<code class="wzp-shortcode-preview"><?php echo esc_html( $shortcode ); ?></code>
							<button type="button" class="button wzp-copy-btn wzp-gm-copy-btn" data-shortcode="<?php echo esc_attr( $shortcode ); ?>">
								<?php esc_html_e( 'Copy', 'woo-zee-plugin' ); ?>
							</button>
						</div>
					</div>
					<div class="wzp-gm-card__actions">
						<button type="button" class="button wzp-gm-edit-btn"
						        data-grid="<?php echo esc_attr( wp_json_encode( $grid ) ); ?>">
							<?php esc_html_e( 'Edit', 'woo-zee-plugin' ); ?>
						</button>
						<button type="button" class="button wzp-gm-delete-btn"
						        data-grid-id="<?php echo esc_attr( $grid['id'] ); ?>">
							<?php esc_html_e( 'Delete', 'woo-zee-plugin' ); ?>
						</button>
					</div>
				</div>
				<?php endforeach; ?>
				<?php endif; ?>
			</div>

		</div>
		<?php endif; /* product-grid */ ?>

		<?php /* ══════════════════════════════════════════════════════════════
		   TAB 3 — Product Carousel
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'product-carousel' === $active_tab ) : ?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-product-carousel">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Product Carousel', 'woo-zee-plugin' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Default settings for [wzp_product_carousel]. Inline shortcode attributes always take priority.', 'woo-zee-plugin' ); ?>
			</p>

			<form method="post" action="options.php" novalidate>
				<?php settings_fields( 'wzp_product_carousel_group' ); ?>

				<table class="form-table wzp-form-table" role="presentation">

					<?php /* Category ──────────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<label for="wzp-pc-category">
								<?php esc_html_e( 'Default Category', 'woo-zee-plugin' ); ?>
							</label>
						</th>
						<td>
							<select id="wzp-pc-category"
							        name="wzp_product_carousel_options[category]"
							        class="wzp-select">
								<option value="">
									— <?php esc_html_e( 'All categories', 'woo-zee-plugin' ); ?> —
								</option>
								<?php foreach ( $wc_categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat['slug'] ); ?>"
									        <?php selected( $carousel_opts['category'], $cat['slug'] ); ?>>
										<?php echo esc_html( $cat['name'] ); ?>
										(<?php echo esc_html( $cat['count'] ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Leave blank to show products from all categories.', 'woo-zee-plugin' ); ?>
							</p>
						</td>
					</tr>

					<?php /* Count ─────────────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<label for="wzp-pc-count">
								<?php esc_html_e( 'Number of Products', 'woo-zee-plugin' ); ?>
								<span class="wzp-required" aria-hidden="true">*</span>
							</label>
						</th>
						<td>
							<input type="number"
							       id="wzp-pc-count"
							       name="wzp_product_carousel_options[count]"
							       value="<?php echo esc_attr( $carousel_opts['count'] ); ?>"
							       min="1"
							       max="24"
							       step="1"
							       class="small-text">
							<p class="description">
								<?php esc_html_e( 'Accepted range: 1 – 24.', 'woo-zee-plugin' ); ?>
							</p>
						</td>
					</tr>

					<?php /* Slides per view ────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<label for="wzp-pc-per-view">
								<?php esc_html_e( 'Slides Per View', 'woo-zee-plugin' ); ?>
								<span class="wzp-required" aria-hidden="true">*</span>
							</label>
						</th>
						<td>
							<select id="wzp-pc-per-view"
							        name="wzp_product_carousel_options[per_view]"
							        class="wzp-select wzp-select--narrow">
								<?php foreach ( array( '2', '3', '4' ) as $pv ) : ?>
									<option value="<?php echo esc_attr( $pv ); ?>"
									        <?php selected( $carousel_opts['per_view'], $pv ); ?>>
										<?php echo esc_html( $pv ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Slides visible at ≥ 1024 px. Smaller screens step down automatically.', 'woo-zee-plugin' ); ?>
							</p>
						</td>
					</tr>

					<?php /* Autoplay toggle ────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Autoplay', 'woo-zee-plugin' ); ?>
						</th>
						<td>
							<label class="wzp-toggle-label" for="wzp-pc-autoplay">
								<input type="checkbox"
								       id="wzp-pc-autoplay"
								       name="wzp_product_carousel_options[autoplay]"
								       value="1"
								       <?php checked( $carousel_opts['autoplay'], 'true' ); ?>
								       class="wzp-toggle-checkbox">
								<span class="wzp-toggle-track" aria-hidden="true"></span>
								<?php esc_html_e( 'Enable autoplay', 'woo-zee-plugin' ); ?>
							</label>
						</td>
					</tr>

					<?php /* Speed (conditionally hidden) ───────────────── */ ?>
					<tr id="wzp-pc-speed-row"
					    <?php echo 'true' !== $carousel_opts['autoplay'] ? 'style="display:none"' : ''; ?>>
						<th scope="row">
							<label for="wzp-pc-speed">
								<?php esc_html_e( 'Autoplay Speed (ms)', 'woo-zee-plugin' ); ?>
							</label>
						</th>
						<td>
							<input type="number"
							       id="wzp-pc-speed"
							       name="wzp_product_carousel_options[speed]"
							       value="<?php echo esc_attr( $carousel_opts['speed'] ); ?>"
							       min="500"
							       max="10000"
							       step="100"
							       class="small-text">
							<p class="description">
								<?php esc_html_e( 'Delay between slides in milliseconds. Range: 500 – 10 000.', 'woo-zee-plugin' ); ?>
							</p>
						</td>
					</tr>

				</table>

				<?php /* Live shortcode preview ─────────────────────────── */ ?>
				<div class="wzp-preview-wrap">
					<strong><?php esc_html_e( 'Shortcode Preview', 'woo-zee-plugin' ); ?></strong>
					<div class="wzp-preview-row">
						<code id="wzp-carousel-shortcode-preview" class="wzp-shortcode-preview"></code>
						<button type="button" id="wzp-carousel-copy-btn" class="button wzp-copy-btn">
							<?php esc_html_e( 'Copy', 'woo-zee-plugin' ); ?>
						</button>
					</div>
				</div>

				<?php submit_button( __( 'Save Settings', 'woo-zee-plugin' ), 'primary wzp-submit-btn' ); ?>

			</form>

		</div>
		<?php endif; /* product-carousel */ ?>

		<?php /* ══════════════════════════════════════════════════════════════
		   TAB 4 — Category Carousel
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'category-carousel' === $active_tab ) : ?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-category-carousel">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Category Carousel', 'woo-zee-plugin' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Display WooCommerce product categories in a horizontal scrollable carousel. Use [wzp_category_carousel] in any page or template.', 'woo-zee-plugin' ); ?>
			</p>

			<form method="post" action="options.php" novalidate>
				<?php settings_fields( 'wzp_cat_carousel_group' ); ?>

				<table class="form-table wzp-form-table" role="presentation">

					<?php /* Items per view ─────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<label for="wzp-cc-per-view">
								<?php esc_html_e( 'Items Per View', 'woo-zee-plugin' ); ?>
								<span class="wzp-required" aria-hidden="true">*</span>
							</label>
						</th>
						<td>
							<select id="wzp-cc-per-view"
							        name="wzp_cat_carousel_options[per_view]"
							        class="wzp-select wzp-select--narrow">
								<?php foreach ( array( '4', '5', '6', '7', '8', '9', '10' ) as $pv ) : ?>
									<option value="<?php echo esc_attr( $pv ); ?>"
									        <?php selected( $cat_carousel_opts['per_view'], $pv ); ?>>
										<?php echo esc_html( $pv ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Categories visible at ≥ 1280 px. Smaller screens step down automatically.', 'woo-zee-plugin' ); ?>
							</p>
						</td>
					</tr>

					<?php /* Icon size ──────────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<label for="wzp-cc-icon-size">
								<?php esc_html_e( 'Icon Size (px)', 'woo-zee-plugin' ); ?>
							</label>
						</th>
						<td>
							<div style="display:flex;align-items:center;gap:12px;">
								<input type="range"
								       id="wzp-cc-icon-size"
								       name="wzp_cat_carousel_options[icon_size]"
								       min="24" max="96" step="4"
								       value="<?php echo esc_attr( $cat_carousel_opts['icon_size'] ); ?>"
								       class="wzp-range"
								       oninput="document.getElementById('wzp-cc-icon-size-val').textContent=this.value+'px'">
								<span id="wzp-cc-icon-size-val" style="font-weight:600;min-width:36px;">
									<?php echo esc_html( $cat_carousel_opts['icon_size'] ); ?>px
								</span>
							</div>
							<p class="description">
								<?php esc_html_e( 'Width and height of each category icon in the carousel.', 'woo-zee-plugin' ); ?>
							</p>
						</td>
					</tr>

				<?php /* Order by ───────────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<label for="wzp-cc-orderby">
								<?php esc_html_e( 'Sort By', 'woo-zee-plugin' ); ?>
							</label>
						</th>
						<td>
							<select id="wzp-cc-orderby"
							        name="wzp_cat_carousel_options[orderby]"
							        class="wzp-select">
								<?php
								$cc_orderby_opts = array(
									'name'       => __( 'Name (A–Z)',          'woo-zee-plugin' ),
									'count'      => __( 'Product Count',       'woo-zee-plugin' ),
									'menu_order' => __( 'Menu Order',          'woo-zee-plugin' ),
									'id'         => __( 'Category ID',         'woo-zee-plugin' ),
								);
								foreach ( $cc_orderby_opts as $val => $lbl ) :
								?>
									<option value="<?php echo esc_attr( $val ); ?>"
									        <?php selected( $cat_carousel_opts['orderby'], $val ); ?>>
										<?php echo esc_html( $lbl ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<?php /* Hide empty ──────────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Show Empty Categories', 'woo-zee-plugin' ); ?>
						</th>
						<td>
							<label class="wzp-toggle-label" for="wzp-cc-hide-empty">
								<input type="checkbox"
								       id="wzp-cc-hide-empty"
								       name="wzp_cat_carousel_options[hide_empty]"
								       value="1"
								       <?php checked( $cat_carousel_opts['hide_empty'], 'false' ); ?>
								       class="wzp-toggle-checkbox">
								<span class="wzp-toggle-track" aria-hidden="true"></span>
								<?php esc_html_e( 'Include categories with no products', 'woo-zee-plugin' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'When off, only categories with at least one product are shown.', 'woo-zee-plugin' ); ?>
							</p>
						</td>
					</tr>

				</table>

				<?php /* Live shortcode preview ─────────────────────────── */ ?>
				<div class="wzp-preview-wrap">
					<strong><?php esc_html_e( 'Shortcode Preview', 'woo-zee-plugin' ); ?></strong>
					<div class="wzp-preview-row">
						<code id="wzp-cc-shortcode-preview" class="wzp-shortcode-preview"></code>
						<button type="button" id="wzp-cc-copy-btn" class="button wzp-copy-btn">
							<?php esc_html_e( 'Copy', 'woo-zee-plugin' ); ?>
						</button>
					</div>
				</div>

				<?php submit_button( __( 'Save Settings', 'woo-zee-plugin' ), 'primary wzp-submit-btn' ); ?>

			</form>

		</div>
		<?php endif; /* category-carousel */ ?>

		<?php /* ══════════════════════════════════════════════════════════════
		   TAB 5 — Category Icons
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'category-icons' === $active_tab ) : ?>
		<?php
		$icon_assignments = (array) get_option( 'wzp_category_icons', array() );
		$all_icons        = wzp_get_category_icons();
		$all_terms        = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'orderby'    => 'name',
		) );
		$all_terms = is_wp_error( $all_terms ) ? array() : $all_terms;
		$all_terms = array_values( array_filter( $all_terms, fn( $t ) => 'uncategorized' !== $t->slug ) );
		?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-category-icons">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Category Icons', 'woo-zee-plugin' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Upload an icon directly to each category. Supports WebP, PNG, and SVG.', 'woo-zee-plugin' ); ?>
			</p>

			<form method="post" action="options.php" novalidate id="wzp-cat-icons-form">
				<?php settings_fields( 'wzp_cat_icons_group' ); ?>

				<?php if ( empty( $all_terms ) ) : ?>
					<p class="description"><?php esc_html_e( 'No product categories found. Please create categories in WooCommerce first.', 'woo-zee-plugin' ); ?></p>
				<?php else : ?>

				<div class="wzp-cat-icon-table">

					<div class="wzp-cat-icon-row wzp-cat-icon-row--head">
						<span><?php esc_html_e( 'Category', 'woo-zee-plugin' ); ?></span>
						<span><?php esc_html_e( 'Icon', 'woo-zee-plugin' ); ?></span>
						<span><?php esc_html_e( 'Action', 'woo-zee-plugin' ); ?></span>
					</div>

					<?php foreach ( $all_terms as $term ) :
						$assigned      = $icon_assignments[ $term->term_id ] ?? '';
						$icon_obj      = $assigned ? current( array_filter( $all_icons, fn( $ic ) => $ic['filename'] === $assigned ) ) : null;
						$icon_url      = $icon_obj ? $icon_obj['url'] : '';
						$icon_label    = $icon_obj ? $icon_obj['label'] : '';
					?>
					<div class="wzp-cat-icon-row" data-term-id="<?php echo esc_attr( $term->term_id ); ?>">

						<div class="wzp-cat-icon-row__cat">
							<strong><?php echo esc_html( $term->name ); ?></strong>
							<span class="wzp-cat-icon-row__count"><?php echo esc_html( $term->count ); ?> <?php esc_html_e( 'products', 'woo-zee-plugin' ); ?></span>
						</div>

						<div class="wzp-cat-icon-row__preview">
							<?php if ( $icon_url ) : ?>
								<div class="wzp-cat-icon-preview wzp-cat-icon-preview--active">
									<?php
									$icon_file = WZP_PATH . 'assets/images/category-icons/' . $assigned;
									if ( 'svg' === strtolower( pathinfo( $assigned, PATHINFO_EXTENSION ) ) && file_exists( $icon_file ) ) :
										// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
										$svg_raw = file_get_contents( $icon_file );
										echo '<div style="width:32px;height:32px;display:flex;align-items:center;">' . $svg_raw . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
									else :
									?>
										<img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $icon_label ); ?>" loading="lazy" style="width:32px;height:32px;object-fit:contain;">
									<?php endif; ?>
									<span><?php echo esc_html( $icon_label ); ?></span>
								</div>
							<?php else : ?>
								<span class="wzp-cat-icon-none"><?php esc_html_e( '— None —', 'woo-zee-plugin' ); ?></span>
							<?php endif; ?>
						</div>

						<div class="wzp-cat-icon-row__actions">
							<input type="hidden"
							       name="wzp_category_icons[<?php echo esc_attr( $term->term_id ); ?>]"
							       value="<?php echo esc_attr( $assigned ); ?>"
							       class="wzp-cat-hidden-input">

							<input type="file"
							       class="wzp-cat-file-input"
							       accept=".webp,.png,.svg"
							       style="display:none"
							       aria-label="<?php esc_attr_e( 'Upload icon', 'woo-zee-plugin' ); ?>">

							<button type="button" class="button wzp-cat-upload-btn">
								<span class="dashicons dashicons-upload" style="vertical-align:middle;margin-right:3px;font-size:14px;height:14px;width:14px;"></span>
								<?php esc_html_e( 'Upload Icon', 'woo-zee-plugin' ); ?>
							</button>

							<button type="button" class="button wzp-cat-svg-btn">
								&lt;/&gt; <?php esc_html_e( 'SVG Code', 'woo-zee-plugin' ); ?>
							</button>

							<button type="button"
							        class="button-link button-link-delete wzp-cat-remove-btn"
							        style="<?php echo $assigned ? '' : 'display:none'; ?>"
							        aria-label="<?php esc_attr_e( 'Remove icon', 'woo-zee-plugin' ); ?>">
								<?php esc_html_e( 'Remove', 'woo-zee-plugin' ); ?>
							</button>

							<span class="wzp-cat-upload-status"></span>

							<div class="wzp-cat-svg-editor" style="display:none;">
								<textarea class="wzp-cat-svg-textarea" rows="4" placeholder="<?php esc_attr_e( 'Paste your <svg>...</svg> code here', 'woo-zee-plugin' ); ?>"></textarea>
								<div class="wzp-cat-svg-editor-actions">
									<button type="button" class="button button-primary wzp-cat-svg-save"><?php esc_html_e( 'Save', 'woo-zee-plugin' ); ?></button>
									<button type="button" class="button wzp-cat-svg-cancel"><?php esc_html_e( 'Cancel', 'woo-zee-plugin' ); ?></button>
								</div>
							</div>
						</div>

					</div>
					<?php endforeach; ?>

				</div>

				<?php endif; ?>

				<?php submit_button( __( 'Save Icon Assignments', 'woo-zee-plugin' ), 'primary wzp-submit-btn' ); ?>

			</form>

		</div>
		<?php endif; /* category-icons */ ?>

		<?php /* ══════════════════════════════════════════════════════════════
		   TAB — Banner Cards
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'banner-cards' === $active_tab ) :
			$banner_cards = (array) get_option( 'wzp_banner_cards', array() );
			// Ensure 4 slots always exist.
			for ( $i = 0; $i < 4; $i++ ) {
				if ( ! isset( $banner_cards[ $i ] ) || ! is_array( $banner_cards[ $i ] ) ) {
					$banner_cards[ $i ] = array( 'image_id' => 0, 'heading' => '', 'btn_text' => '', 'btn_url' => '', 'btn_icon' => '↗' );
				} elseif ( ! isset( $banner_cards[ $i ]['btn_icon'] ) ) {
					$banner_cards[ $i ]['btn_icon'] = '↗';
				}
			}

			// Available link icons — value => display label.
			$link_icons = array(
				''    => 'None',
				'↗'   => '↗',
				'→'   => '→',
				'➜'   => '➜',
				'›'   => '›',
				'▸'   => '▸',
				'+'   => '+',
				'✦'   => '✦',
			);
		?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-banner-cards">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Banner Cards', 'woo-zee-plugin' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Configure 4 image cards displayed side-by-side. Use the shortcode below to embed them on any page.', 'woo-zee-plugin' ); ?>
			</p>

			<?php /* Shortcode preview */ ?>
			<div class="wzp-preview-wrap">
				<strong><?php esc_html_e( 'Shortcode', 'woo-zee-plugin' ); ?></strong>
				<div class="wzp-preview-row">
					<code class="wzp-shortcode-preview">[wzp_banner_cards]</code>
					<button type="button" class="button wzp-copy-btn"
					        onclick="navigator.clipboard.writeText('[wzp_banner_cards]');this.textContent='<?php echo esc_js( __( 'Copied!', 'woo-zee-plugin' ) ); ?>';setTimeout(()=>this.textContent='<?php echo esc_js( __( 'Copy', 'woo-zee-plugin' ) ); ?>',2000)">
						<?php esc_html_e( 'Copy', 'woo-zee-plugin' ); ?>
					</button>
				</div>
			</div>

			<form method="post" action="options.php" novalidate>
				<?php settings_fields( 'wzp_banner_cards_group' ); ?>

				<div class="wzp-banner-card-editor">
					<?php for ( $i = 0; $i < 4; $i++ ) :
						$card      = $banner_cards[ $i ];
						$image_id  = absint( $card['image_id'] );
						$thumb_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
					?>
					<div class="wzp-bce-card" data-index="<?php echo esc_attr( $i ); ?>">

						<div class="wzp-bce-card__num"><?php echo esc_html( $i + 1 ); ?></div>

						<?php /* Image picker */ ?>
						<div class="wzp-bce-image">
							<div class="wzp-bce-preview">
								<?php if ( $thumb_url ) : ?>
								<img src="<?php echo esc_url( $thumb_url ); ?>" alt="">
								<?php endif; ?>
							</div>
							<input type="hidden"
							       name="wzp_banner_cards[<?php echo esc_attr( $i ); ?>][image_id]"
							       class="wzp-bce-image-id"
							       value="<?php echo esc_attr( $image_id ); ?>">
							<button type="button" class="button wzp-bce-media-btn">
								<?php echo $thumb_url
									? esc_html__( 'Change Image', 'woo-zee-plugin' )
									: esc_html__( 'Select Image', 'woo-zee-plugin' ); ?>
							</button>
							<?php if ( $thumb_url ) : ?>
							<button type="button" class="button-link wzp-bce-media-remove">
								<?php esc_html_e( 'Remove', 'woo-zee-plugin' ); ?>
							</button>
							<?php else : ?>
							<button type="button" class="button-link wzp-bce-media-remove" style="display:none;">
								<?php esc_html_e( 'Remove', 'woo-zee-plugin' ); ?>
							</button>
							<?php endif; ?>
						</div>

						<?php /* Text fields */ ?>
						<div class="wzp-bce-fields">
							<div class="wzp-bce-field">
								<label><?php esc_html_e( 'Heading', 'woo-zee-plugin' ); ?></label>
								<input type="text"
								       name="wzp_banner_cards[<?php echo esc_attr( $i ); ?>][heading]"
								       value="<?php echo esc_attr( $card['heading'] ); ?>"
								       placeholder="<?php esc_attr_e( 'e.g. Sale Off 35%', 'woo-zee-plugin' ); ?>"
								       class="regular-text">
							</div>
							<div class="wzp-bce-field">
								<label><?php esc_html_e( 'Link Text', 'woo-zee-plugin' ); ?></label>
								<input type="text"
								       name="wzp_banner_cards[<?php echo esc_attr( $i ); ?>][btn_text]"
								       value="<?php echo esc_attr( $card['btn_text'] ); ?>"
								       placeholder="<?php esc_attr_e( 'e.g. Shop Now', 'woo-zee-plugin' ); ?>"
								       class="regular-text">
							</div>
							<div class="wzp-bce-field">
								<label><?php esc_html_e( 'Link URL', 'woo-zee-plugin' ); ?></label>
								<input type="url"
								       name="wzp_banner_cards[<?php echo esc_attr( $i ); ?>][btn_url]"
								       value="<?php echo esc_url( $card['btn_url'] ); ?>"
								       placeholder="https://"
								       class="regular-text">
							</div>
							<div class="wzp-bce-field">
								<label><?php esc_html_e( 'Link Icon', 'woo-zee-plugin' ); ?></label>
								<div class="wzp-bce-icon-picker">
									<?php foreach ( $link_icons as $icon_val => $icon_lbl ) :
										$is_selected = ( ( $card['btn_icon'] ?? '↗' ) === $icon_val );
									?>
									<label class="wzp-bce-icon-opt<?php echo $is_selected ? ' wzp-bce-icon-opt--active' : ''; ?>"
									       title="<?php echo '' === $icon_val ? esc_attr__( 'No icon', 'woo-zee-plugin' ) : esc_attr( $icon_lbl ); ?>">
										<input type="radio"
										       name="wzp_banner_cards[<?php echo esc_attr( $i ); ?>][btn_icon]"
										       value="<?php echo esc_attr( $icon_val ); ?>"
										       <?php checked( $is_selected ); ?>>
										<span><?php echo '' === $icon_val ? esc_html__( 'None', 'woo-zee-plugin' ) : esc_html( $icon_lbl ); ?></span>
									</label>
									<?php endforeach; ?>
								</div>
							</div>
						</div>

					</div>
					<?php endfor; ?>
				</div>

				<?php submit_button( __( 'Save Banner Cards', 'woo-zee-plugin' ), 'primary wzp-submit-btn' ); ?>

			</form>

		</div>
		<?php endif; /* banner-cards */ ?>

		<?php /* ══════════════════════════════════════════════════════════════
		   TAB — Single Banner
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'single-banner' === $active_tab ) : ?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-single-banner">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Single Banner', 'woo-zee-plugin' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Configure the [wzp_single_banner] full-width promotional banner. Shortcode: [wzp_single_banner]', 'woo-zee-plugin' ); ?>
			</p>

			<form method="post" action="options.php" novalidate>
				<?php settings_fields( 'wzp_single_banner_group' ); ?>

				<table class="form-table wzp-form-table" role="presentation">

					<?php /* Background image */ ?>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Background Image', 'woo-zee-plugin' ); ?>
							<span class="wzp-required" aria-hidden="true">*</span>
						</th>
						<td>
							<input type="hidden"
							       id="wzp-sb-image-id"
							       name="wzp_single_banner_options[image_id]"
							       value="<?php echo esc_attr( $sb_opts['image_id'] ); ?>">
							<div id="wzp-sb-preview" class="wzp-img-preview">
								<?php if ( $sb_thumb_url ) : ?>
									<img src="<?php echo esc_url( $sb_thumb_url ); ?>" alt="">
								<?php endif; ?>
							</div>
							<button type="button" id="wzp-sb-media-btn" class="button">
								<?php echo $sb_thumb_url ? esc_html__( 'Change Image', 'woo-zee-plugin' ) : esc_html__( 'Select Image', 'woo-zee-plugin' ); ?>
							</button>
							<button type="button" id="wzp-sb-media-remove" class="button-link button-link-delete"
							        style="<?php echo $sb_thumb_url ? '' : 'display:none'; ?>">
								<?php esc_html_e( 'Remove image', 'woo-zee-plugin' ); ?>
							</button>
						</td>
					</tr>

					<?php
					$sb_fields = array(
						'label'       => array( 'label' => __( 'Label (eyebrow)',  'woo-zee-plugin' ), 'type' => 'text',     'required' => false ),
						'heading'     => array( 'label' => __( 'Heading',          'woo-zee-plugin' ), 'type' => 'text',     'required' => true  ),
						'description' => array( 'label' => __( 'Description',      'woo-zee-plugin' ), 'type' => 'textarea', 'required' => false ),
						'btn_text'    => array( 'label' => __( 'Button Text',      'woo-zee-plugin' ), 'type' => 'text',     'required' => false ),
						'btn_url'     => array( 'label' => __( 'Button URL',       'woo-zee-plugin' ), 'type' => 'url',      'required' => false ),
					);
					foreach ( $sb_fields as $fk => $fc ) :
						$fid = 'wzp-sb-' . str_replace( '_', '-', $fk );
					?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $fid ); ?>">
								<?php echo esc_html( $fc['label'] ); ?>
								<?php if ( $fc['required'] ) : ?><span class="wzp-required" aria-hidden="true">*</span><?php endif; ?>
							</label>
						</th>
						<td>
							<?php if ( 'textarea' === $fc['type'] ) : ?>
								<textarea id="<?php echo esc_attr( $fid ); ?>"
								          name="wzp_single_banner_options[<?php echo esc_attr( $fk ); ?>]"
								          class="large-text" rows="3"><?php echo esc_textarea( $sb_opts[ $fk ] ); ?></textarea>
							<?php else : ?>
								<input type="<?php echo esc_attr( $fc['type'] ); ?>"
								       id="<?php echo esc_attr( $fid ); ?>"
								       name="wzp_single_banner_options[<?php echo esc_attr( $fk ); ?>]"
								       value="<?php echo esc_attr( $sb_opts[ $fk ] ); ?>"
								       class="regular-text">
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>

					<tr>
						<th scope="row"><label for="wzp-sb-align"><?php esc_html_e( 'Content Alignment', 'woo-zee-plugin' ); ?></label></th>
						<td>
							<select id="wzp-sb-align" name="wzp_single_banner_options[align]">
								<option value="left"   <?php selected( $sb_opts['align'], 'left'   ); ?>><?php esc_html_e( 'Left',   'woo-zee-plugin' ); ?></option>
								<option value="center" <?php selected( $sb_opts['align'], 'center' ); ?>><?php esc_html_e( 'Center', 'woo-zee-plugin' ); ?></option>
								<option value="right"  <?php selected( $sb_opts['align'], 'right'  ); ?>><?php esc_html_e( 'Right',  'woo-zee-plugin' ); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row"><label for="wzp-sb-height"><?php esc_html_e( 'Banner Height (px)', 'woo-zee-plugin' ); ?></label></th>
						<td>
							<input type="number" id="wzp-sb-height"
							       name="wzp_single_banner_options[height]"
							       value="<?php echo esc_attr( $sb_opts['height'] ); ?>"
							       min="200" max="900" step="10" class="small-text">
							<span class="description">px (200–900)</span>
						</td>
					</tr>

				</table>

				<?php submit_button( __( 'Save Single Banner', 'woo-zee-plugin' ), 'primary wzp-submit-btn' ); ?>

			</form>

		</div>
		<?php endif; /* single-banner */ ?>

		<?php /* ══════════════════════════════════════════════════════════════
		   TAB 6 — Lookbook
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'lookbook' === $active_tab ) : ?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-lookbook">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Lookbook', 'woo-zee-plugin' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Configure the [wzp_lookbook] section — background image, copy, and shoppable hotspots.', 'woo-zee-plugin' ); ?>
			</p>

			<form method="post" action="options.php" novalidate>
				<?php settings_fields( 'wzp_lookbook_group' ); ?>

				<table class="form-table wzp-form-table" role="presentation">

					<?php /* Background image ────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Background Image', 'woo-zee-plugin' ); ?>
							<span class="wzp-required" aria-hidden="true">*</span>
						</th>
						<td>
							<input type="hidden"
							       id="wzp-lookbook-image-id"
							       name="wzp_lookbook_options[image_id]"
							       value="<?php echo esc_attr( $lookbook_opts['image_id'] ); ?>">
							<div id="wzp-lookbook-preview" class="wzp-lookbook-preview">
								<?php if ( $lookbook_thumb_url ) : ?>
									<img src="<?php echo esc_url( $lookbook_thumb_url ); ?>" alt="">
								<?php endif; ?>
							</div>
							<button type="button"
							        id="wzp-lookbook-media-btn"
							        class="button">
								<?php echo $lookbook_thumb_url
									? esc_html__( 'Change Image', 'woo-zee-plugin' )
									: esc_html__( 'Select Image', 'woo-zee-plugin' ); ?>
							</button>
							<button type="button"
							        id="wzp-lookbook-media-remove"
							        class="button-link button-link-delete"
							        style="<?php echo $lookbook_thumb_url ? '' : 'display:none'; ?>">
								<?php esc_html_e( 'Remove image', 'woo-zee-plugin' ); ?>
							</button>
						</td>
					</tr>

					<?php /* Text fields ─────────────────────────────────── */ ?>
					<?php
					$lb_fields = array(
						'label'       => array( 'label' => __( 'Label (eyebrow)',  'woo-zee-plugin' ), 'type' => 'text',     'required' => false ),
						'heading'     => array( 'label' => __( 'Heading',          'woo-zee-plugin' ), 'type' => 'text',     'required' => true  ),
						'description' => array( 'label' => __( 'Description',      'woo-zee-plugin' ), 'type' => 'textarea', 'required' => false ),
						'btn_text'    => array( 'label' => __( 'Button Text',      'woo-zee-plugin' ), 'type' => 'text',     'required' => false ),
						'btn_url'     => array( 'label' => __( 'Button URL',       'woo-zee-plugin' ), 'type' => 'url',      'required' => false ),
					);
					foreach ( $lb_fields as $fk => $fc ) :
						$fid = 'wzp-lb-' . str_replace( '_', '-', $fk );
					?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $fid ); ?>">
								<?php echo esc_html( $fc['label'] ); ?>
								<?php if ( $fc['required'] ) : ?>
									<span class="wzp-required" aria-hidden="true">*</span>
								<?php endif; ?>
							</label>
						</th>
						<td>
							<?php if ( 'textarea' === $fc['type'] ) : ?>
								<textarea id="<?php echo esc_attr( $fid ); ?>"
								          name="wzp_lookbook_options[<?php echo esc_attr( $fk ); ?>]"
								          class="large-text"
								          rows="3"><?php echo esc_textarea( $lookbook_opts[ $fk ] ); ?></textarea>
							<?php else : ?>
								<input type="<?php echo esc_attr( $fc['type'] ); ?>"
								       id="<?php echo esc_attr( $fid ); ?>"
								       name="wzp_lookbook_options[<?php echo esc_attr( $fk ); ?>]"
								       value="<?php echo esc_attr( $lookbook_opts[ $fk ] ); ?>"
								       class="regular-text">
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>

				</table>

				<?php /* -- Hotspot canvas editor -- */ ?>
				<h2 class="wzp-subsection-title">
					<?php esc_html_e( 'Shoppable Hotspots', 'woo-zee-plugin' ); ?>
				</h2>
				<p class="description">
					<?php esc_html_e( 'Double-click anywhere on the image to place a pin. Double-click an existing pin to assign a product. Drag pins to reposition.', 'woo-zee-plugin' ); ?>
				</p>

				<div id="wzp-canvas-outer" class="wzp-canvas-outer">

					<?php if ( $lookbook_large_url ) : ?>
					<div id="wzp-canvas-wrap" class="wzp-canvas-wrap">

						<img id="wzp-canvas-img"
						     src="<?php echo esc_url( $lookbook_large_url ); ?>"
						     alt=""
						     draggable="false">

						<div id="wzp-canvas-pins">
						<?php foreach ( $lookbook_hotspots as $hi => $hs ) :
							$pid       = absint( $hs['product_id'] ?? 0 );
							$pin_name  = '';
							$pin_price = '';
							$pin_thumb = '';
							if ( $pid ) {
								$_prod = wc_get_product( $pid );
								if ( $_prod instanceof WC_Product ) {
									$pin_name  = $_prod->get_name();
									$pin_price = wp_strip_all_tags( $_prod->get_price_html() );
									$_tid      = $_prod->get_image_id();
									$pin_thumb = $_tid ? wp_get_attachment_image_url( $_tid, 'thumbnail' ) : '';
								}
							}
						?>
						<button type="button"
						        class="wzp-canvas-pin<?php echo $pid ? ' wzp-canvas-pin--assigned' : ''; ?>"
						        data-index="<?php echo esc_attr( $hi ); ?>"
						        data-product-name="<?php echo esc_attr( $pin_name ); ?>"
						        data-product-price="<?php echo esc_attr( $pin_price ); ?>"
						        data-product-thumb="<?php echo esc_attr( $pin_thumb ); ?>"
						        style="left:<?php echo esc_attr( round( $hs['x'] ?? 0, 2 ) ); ?>%;top:<?php echo esc_attr( round( $hs['y'] ?? 0, 2 ) ); ?>%"
						        title="<?php echo $pid ? esc_attr( $pin_name ?: sprintf( __( 'Product #%d', 'woo-zee-plugin' ), $pid ) ) : esc_attr__( 'Click to assign product', 'woo-zee-plugin' ); ?>">
							<span class="wzp-canvas-pin__num"><?php echo esc_html( $hi + 1 ); ?></span>
							<span class="wzp-canvas-pin__remove" aria-label="<?php esc_attr_e( 'Remove pin', 'woo-zee-plugin' ); ?>">&#x2715;</span>
						</button>
						<?php endforeach; ?>
						</div>

						<div id="wzp-hs-popup" class="wzp-hs-popup" hidden>
							<div class="wzp-hs-popup__head">
							  <span class="wzp-hs-popup__title"><?php esc_html_e( 'Assign Product', 'woo-zee-plugin' ); ?></span>
							  <button type="button" class="wzp-hs-popup__close" aria-label="<?php esc_attr_e( 'Close', 'woo-zee-plugin' ); ?>">&#x2715;</button>
							</div>
							<div class="wzp-hs-popup__body">
							  <input type="text"
							         id="wzp-hs-search"
							         class="wzp-hs-search-input"
							         placeholder="<?php esc_attr_e( 'Search by name or ID', 'woo-zee-plugin' ); ?>"
							         autocomplete="off">
							  <div id="wzp-hs-results" class="wzp-hs-results"></div>
							  <div class="wzp-hs-popup__footer">
							    <button type="button" id="wzp-hs-remove-pin" class="button-link button-link-delete">
							      <?php esc_html_e( 'Remove this pin', 'woo-zee-plugin' ); ?>
							    </button>
							  </div>
							</div>
						</div>

					</div><?php /* /#wzp-canvas-wrap */ ?>
					<?php else : ?>
					<p id="wzp-canvas-placeholder" class="wzp-canvas-placeholder">
						<?php esc_html_e( 'Select a background image above to enable hotspot editing.', 'woo-zee-plugin' ); ?>
					</p>
					<?php endif; ?>

					<div id="wzp-hs-data-store">
					<?php foreach ( $lookbook_hotspots as $hi => $hs ) : ?>
					<div class="wzp-hs-row" data-index="<?php echo esc_attr( $hi ); ?>">
						<input type="hidden" name="wzp_lookbook_options[hotspots][<?php echo esc_attr( $hi ); ?>][x]" value="<?php echo esc_attr( round( $hs['x'] ?? 0, 2 ) ); ?>">
						<input type="hidden" name="wzp_lookbook_options[hotspots][<?php echo esc_attr( $hi ); ?>][y]" value="<?php echo esc_attr( round( $hs['y'] ?? 0, 2 ) ); ?>">
						<input type="hidden" name="wzp_lookbook_options[hotspots][<?php echo esc_attr( $hi ); ?>][product_id]" value="<?php echo esc_attr( absint( $hs['product_id'] ?? 0 ) ); ?>">
					</div>
					<?php endforeach; ?>
					</div>

				</div><?php /* /#wzp-canvas-outer */ ?>

				<?php submit_button( __( 'Save Lookbook', 'woo-zee-plugin' ), 'primary wzp-submit-btn' ); ?>

			</form>
		</div>
		<?php endif; /* lookbook */ ?>

		<?php /* ══════════════════════════════════════════════════════════════
		   TAB 5 — Testimonials
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'testimonials' === $active_tab ) : ?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-testimonials">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Testimonials', 'woo-zee-plugin' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Add and manage customer reviews displayed by the [wzp_testimonials] shortcode.', 'woo-zee-plugin' ); ?>
			</p>

			<form method="post" action="options.php" novalidate>
				<?php settings_fields( 'wzp_testimonials_group' ); ?>

				<div id="wzp-testimonials-list"
				     data-next-index="<?php echo esc_attr( count( $testimonials_data ) ); ?>">

					<?php foreach ( $testimonials_data as $ti => $entry ) : ?>
						<?php
						$av_id  = absint( $entry['avatar_id'] ?? 0 );
						$av_url = $av_id ? wp_get_attachment_image_url( $av_id, 'thumbnail' ) : '';
						$t_name = sanitize_text_field( $entry['name']     ?? '' );
						$t_loc  = sanitize_text_field( $entry['location'] ?? '' );
						$t_rev  = sanitize_textarea_field( $entry['review']  ?? '' );
						?>
						<div class="wzp-review-row" data-index="<?php echo esc_attr( $ti ); ?>">

							<div class="wzp-review-row__avatar-col">
								<input type="hidden"
								       class="wzp-review-avatar-id"
								       name="wzp_testimonials_data[<?php echo esc_attr( $ti ); ?>][avatar_id]"
								       value="<?php echo esc_attr( $av_id ); ?>">
								<div class="wzp-review-avatar-preview wzp-avatar-circle">
									<?php if ( $av_url ) : ?>
										<img src="<?php echo esc_url( $av_url ); ?>" alt="">
									<?php endif; ?>
								</div>
								<button type="button" class="button wzp-review-avatar-btn">
									<?php echo $av_url
										? esc_html__( 'Change', 'woo-zee-plugin' )
										: esc_html__( 'Upload', 'woo-zee-plugin' ); ?>
								</button>
								<button type="button"
								        class="button-link button-link-delete wzp-review-avatar-remove"
								        style="<?php echo $av_url ? '' : 'display:none'; ?>">
									<?php esc_html_e( '✕', 'woo-zee-plugin' ); ?>
								</button>
							</div>

							<div class="wzp-review-row__fields">
								<div class="wzp-review-field-row">
									<input type="text"
									       name="wzp_testimonials_data[<?php echo esc_attr( $ti ); ?>][name]"
									       value="<?php echo esc_attr( $t_name ); ?>"
									       placeholder="<?php esc_attr_e( 'Customer Name *', 'woo-zee-plugin' ); ?>"
									       class="regular-text wzp-review-name">
									<input type="text"
									       name="wzp_testimonials_data[<?php echo esc_attr( $ti ); ?>][location]"
									       value="<?php echo esc_attr( $t_loc ); ?>"
									       placeholder="<?php esc_attr_e( 'Location (e.g. New York)', 'woo-zee-plugin' ); ?>"
									       class="regular-text">
								</div>
								<textarea name="wzp_testimonials_data[<?php echo esc_attr( $ti ); ?>][review]"
								          rows="4"
								          placeholder="<?php esc_attr_e( 'Review text *', 'woo-zee-plugin' ); ?>"
								          class="large-text"><?php echo esc_textarea( $t_rev ); ?></textarea>
							</div>

							<div class="wzp-review-row__actions">
								<span class="wzp-review-row__drag" aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'woo-zee-plugin' ); ?>">⠿</span>
								<button type="button"
								        class="button-link button-link-delete wzp-review-remove"
								        aria-label="<?php esc_attr_e( 'Remove review', 'woo-zee-plugin' ); ?>">
									<?php esc_html_e( 'Remove', 'woo-zee-plugin' ); ?>
								</button>
							</div>

						</div>
					<?php endforeach; ?>

				</div><?php /* /#wzp-testimonials-list */ ?>

				<button type="button" id="wzp-add-review" class="button button-secondary">
					+ <?php esc_html_e( 'Add Review', 'woo-zee-plugin' ); ?>
				</button>

				<?php submit_button( __( 'Save Reviews', 'woo-zee-plugin' ), 'primary wzp-submit-btn' ); ?>

			</form>
		</div>
		<?php endif; /* testimonials */ ?>

		<?php /* ══════════════════════════════════════════════════════════════
		   TAB 6 — Instagram Feed
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'instagram-feed' === $active_tab ) : ?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-instagram-feed">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Instagram Feed', 'woo-zee-plugin' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Connect via the Instagram Graph API. Use [wzp_instagram_feed] or [wzp_instagram_feed count="6"] in any page or template.', 'woo-zee-plugin' ); ?>
			</p>

			<form method="post" action="options.php" novalidate id="wzp-ig-section">
				<?php settings_fields( 'wzp_instagram_group' ); ?>

				<table class="form-table wzp-form-table" role="presentation">

					<?php /* Access token ───────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<label for="wzp-ig-token">
								<?php esc_html_e( 'Access Token', 'woo-zee-plugin' ); ?>
								<span class="wzp-required" aria-hidden="true">*</span>
							</label>
						</th>
						<td>
							<div class="wzp-token-wrap">
								<input type="password"
								       id="wzp-ig-token"
								       name="wzp_instagram_options[access_token]"
								       value="<?php echo esc_attr( $ig_opts['access_token'] ); ?>"
								       class="regular-text"
								       autocomplete="off"
								       spellcheck="false">
								<button type="button"
								        id="wzp-ig-token-toggle"
								        class="button wzp-token-toggle"
								        aria-controls="wzp-ig-token"
								        aria-pressed="false">
									<?php esc_html_e( 'Show', 'woo-zee-plugin' ); ?>
								</button>
							</div>
							<p class="description">
								<?php esc_html_e( 'Generate a long-lived token via the Instagram Graph API or a service like token.tools.', 'woo-zee-plugin' ); ?>
							</p>
						</td>
					</tr>

					<?php /* Username ───────────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<label for="wzp-ig-username">
								<?php esc_html_e( 'Username', 'woo-zee-plugin' ); ?>
							</label>
						</th>
						<td>
							<input type="text"
							       id="wzp-ig-username"
							       name="wzp_instagram_options[username]"
							       value="<?php echo esc_attr( $ig_opts['username'] ); ?>"
							       class="regular-text"
							       placeholder="@youraccount">
							<p class="description">
								<?php esc_html_e( 'Display only — not used in the API call.', 'woo-zee-plugin' ); ?>
							</p>
						</td>
					</tr>

					<?php /* Count ──────────────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<label for="wzp-ig-count">
								<?php esc_html_e( 'Number of Photos', 'woo-zee-plugin' ); ?>
								<span class="wzp-required" aria-hidden="true">*</span>
							</label>
						</th>
						<td>
							<input type="number"
							       id="wzp-ig-count"
							       name="wzp_instagram_options[count]"
							       value="<?php echo esc_attr( absint( $ig_opts['count'] ) ); ?>"
							       min="1"
							       max="12"
							       step="1"
							       class="small-text">
							<p class="description">
								<?php esc_html_e( 'Accepted range: 1 – 12. Shortcode attribute overrides this.', 'woo-zee-plugin' ); ?>
							</p>
						</td>
					</tr>

					<?php /* API tools ──────────────────────────────────── */ ?>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'API Tools', 'woo-zee-plugin' ); ?>
						</th>
						<td>
							<div class="wzp-ig-tools">
								<button type="button"
								        id="wzp-ig-test"
								        class="button button-secondary">
									<?php esc_html_e( 'Test Connection', 'woo-zee-plugin' ); ?>
								</button>
								<button type="button"
								        id="wzp-ig-clear-cache"
								        class="button button-secondary">
									<?php esc_html_e( 'Clear Cache', 'woo-zee-plugin' ); ?>
								</button>
								<span id="wzp-ig-status" class="wzp-ig-status" role="status" aria-live="polite"></span>
							</div>
							<p class="description">
								<?php esc_html_e( 'Test verifies the token live. Cache refreshes hourly but can be cleared manually.', 'woo-zee-plugin' ); ?>
							</p>
						</td>
					</tr>

				</table>

				<?php submit_button( __( 'Save Settings', 'woo-zee-plugin' ), 'primary wzp-submit-btn' ); ?>

			</form>

		</div>
		<?php endif; /* instagram-feed */ ?>


		<?php /* ══════════════════════════════════════════════════════════════
		   TAB — Navbar
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'navbar' === $active_tab ) : ?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-navbar">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Navbar', 'woo-zee-plugin' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Configure your site navbar. Use [wzp_navbar] in any template or Full-Site Editor block.', 'woo-zee-plugin' ); ?>
			</p>

			<div class="wzp-nb-shortcode-bar">
				<code class="wzp-nb-shortcode-code">[wzp_navbar]</code>
				<button type="button" id="wzp-nb-copy-sc" class="button button-secondary">
					<?php esc_html_e( 'Copy', 'woo-zee-plugin' ); ?>
				</button>
			</div>

			<div class="wzp-nb-admin-grid">

				<!-- ── Left column: settings ────────────────────────────────── -->
				<div class="wzp-nb-admin-col">

					<h3 class="wzp-nb-admin-section-title"><?php esc_html_e( 'Logo', 'woo-zee-plugin' ); ?></h3>
					<table class="form-table wzp-form-table" role="presentation">
						<tr>
							<th><?php esc_html_e( 'Logo Type', 'woo-zee-plugin' ); ?></th>
							<td>
								<label class="wzp-radio-label">
									<input type="radio" name="nb_logo_type" value="text"
									       <?php checked( $navbar_settings['logo_type'], 'text' ); ?>>
									<?php esc_html_e( 'Text', 'woo-zee-plugin' ); ?>
								</label>
								<label class="wzp-radio-label">
									<input type="radio" name="nb_logo_type" value="image"
									       <?php checked( $navbar_settings['logo_type'], 'image' ); ?>>
									<?php esc_html_e( 'Image', 'woo-zee-plugin' ); ?>
								</label>
							</td>
						</tr>
						<tr class="wzp-nb-logo-text-row<?php echo 'image' === $navbar_settings['logo_type'] ? ' wzp-nb-hidden' : ''; ?>">
							<th><label for="nb_logo_text"><?php esc_html_e( 'Logo Text', 'woo-zee-plugin' ); ?></label></th>
							<td>
								<input type="text" id="nb_logo_text" class="regular-text"
								       value="<?php echo esc_attr( $navbar_settings['logo_text'] ); ?>">
							</td>
						</tr>
						<tr class="wzp-nb-logo-image-row<?php echo 'text' === $navbar_settings['logo_type'] ? ' wzp-nb-hidden' : ''; ?>">
							<th><?php esc_html_e( 'Logo Image', 'woo-zee-plugin' ); ?></th>
							<td>
								<input type="hidden" id="nb_logo_id" value="<?php echo esc_attr( $navbar_settings['logo_id'] ); ?>">
								<?php if ( $nb_logo_preview ) : ?>
									<img id="nb-logo-preview-img" src="<?php echo esc_url( $nb_logo_preview ); ?>"
									     style="max-width:160px;display:block;margin-bottom:8px;">
								<?php else : ?>
									<img id="nb-logo-preview-img" src="" style="max-width:160px;display:none;margin-bottom:8px;">
								<?php endif; ?>
								<button type="button" id="nb-logo-select-btn" class="button button-secondary">
									<?php echo $nb_logo_preview ? esc_html__( 'Change Logo', 'woo-zee-plugin' ) : esc_html__( 'Select Logo', 'woo-zee-plugin' ); ?>
								</button>
								<button type="button" id="nb-logo-remove-btn" class="button button-link-delete"
								        <?php echo $nb_logo_preview ? '' : 'style="display:none"'; ?>>
									<?php esc_html_e( 'Remove', 'woo-zee-plugin' ); ?>
								</button>
							</td>
						</tr>
					</table>

					<h3 class="wzp-nb-admin-section-title"><?php esc_html_e( 'Menu', 'woo-zee-plugin' ); ?></h3>
					<table class="form-table wzp-form-table" role="presentation">
						<tr>
							<th><label for="nb_menu_id"><?php esc_html_e( 'Active Menu', 'woo-zee-plugin' ); ?></label></th>
							<td>
								<?php $wp_nav_menus = wp_get_nav_menus(); ?>
								<select id="nb_menu_id" class="regular-text">
									<option value=""><?php esc_html_e( '— None —', 'woo-zee-plugin' ); ?></option>
									<?php foreach ( $wp_nav_menus as $wp_menu ) : ?>
										<option value="<?php echo esc_attr( $wp_menu->term_id ); ?>"
										        <?php selected( (int) $navbar_settings['menu_id'], $wp_menu->term_id ); ?>>
											<?php echo esc_html( $wp_menu->name ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php
									printf(
										/* translators: %s: link to Appearance > Menus */
										esc_html__( 'Create and edit menus under %s.', 'woo-zee-plugin' ),
										'<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '" target="_blank">' . esc_html__( 'Appearance → Menus', 'woo-zee-plugin' ) . '</a>'
									);
									?>
								</p>
							</td>
						</tr>
					</table>

					<h3 class="wzp-nb-admin-section-title"><?php esc_html_e( 'Links', 'woo-zee-plugin' ); ?></h3>
					<table class="form-table wzp-form-table" role="presentation">
						<tr>
							<th><label for="nb_account_url"><?php esc_html_e( 'Account URL', 'woo-zee-plugin' ); ?></label></th>
							<td><input type="url" id="nb_account_url" class="regular-text" value="<?php echo esc_attr( $navbar_settings['account_url'] ); ?>" placeholder="/my-account/"></td>
						</tr>
						<tr>
							<th><label for="nb_wishlist_url"><?php esc_html_e( 'Wishlist URL', 'woo-zee-plugin' ); ?></label></th>
							<td><input type="url" id="nb_wishlist_url" class="regular-text" value="<?php echo esc_attr( $navbar_settings['wishlist_url'] ); ?>" placeholder="/wishlist/"></td>
						</tr>
						<tr>
							<th><label for="nb_cart_url"><?php esc_html_e( 'Cart URL', 'woo-zee-plugin' ); ?></label></th>
							<td><input type="url" id="nb_cart_url" class="regular-text" value="<?php echo esc_attr( $navbar_settings['cart_url'] ); ?>" placeholder="/cart/"></td>
						</tr>
					</table>

					<h3 class="wzp-nb-admin-section-title"><?php esc_html_e( 'Visibility', 'woo-zee-plugin' ); ?></h3>
					<table class="form-table wzp-form-table" role="presentation">
						<tr>
							<th><?php esc_html_e( 'Show Icons', 'woo-zee-plugin' ); ?></th>
							<td>
								<label class="wzp-checkbox-label">
									<input type="checkbox" id="nb_show_search" value="1" <?php checked( $navbar_settings['show_search'], '1' ); ?>>
									<?php esc_html_e( 'Search', 'woo-zee-plugin' ); ?>
								</label>
								<label class="wzp-checkbox-label">
									<input type="checkbox" id="nb_show_account" value="1" <?php checked( $navbar_settings['show_account'], '1' ); ?>>
									<?php esc_html_e( 'Account', 'woo-zee-plugin' ); ?>
								</label>
								<label class="wzp-checkbox-label">
									<input type="checkbox" id="nb_show_wishlist" value="1" <?php checked( $navbar_settings['show_wishlist'], '1' ); ?>>
									<?php esc_html_e( 'Wishlist', 'woo-zee-plugin' ); ?>
								</label>
								<label class="wzp-checkbox-label">
									<input type="checkbox" id="nb_show_cart" value="1" <?php checked( $navbar_settings['show_cart'], '1' ); ?>>
									<?php esc_html_e( 'Cart', 'woo-zee-plugin' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Sticky', 'woo-zee-plugin' ); ?></th>
							<td>
								<label class="wzp-checkbox-label">
									<input type="checkbox" id="nb_sticky" value="1" <?php checked( $navbar_settings['sticky'], '1' ); ?>>
									<?php esc_html_e( 'Enable sticky + hide-on-scroll-down behaviour', 'woo-zee-plugin' ); ?>
								</label>
							</td>
						</tr>
					</table>

					<h3 class="wzp-nb-admin-section-title"><?php esc_html_e( 'Colours', 'woo-zee-plugin' ); ?></h3>
					<table class="form-table wzp-form-table" role="presentation">
						<?php
						$nb_colors = array(
							'nb_bg_color'     => array( 'label' => __( 'Background',  'woo-zee-plugin' ), 'key' => 'bg_color' ),
							'nb_text_color'   => array( 'label' => __( 'Text',        'woo-zee-plugin' ), 'key' => 'text_color' ),
							'nb_hover_color'  => array( 'label' => __( 'Hover',       'woo-zee-plugin' ), 'key' => 'hover_color' ),
							'nb_active_color' => array( 'label' => __( 'Active',      'woo-zee-plugin' ), 'key' => 'active_color' ),
							'nb_border_color' => array( 'label' => __( 'Border',      'woo-zee-plugin' ), 'key' => 'border_color' ),
						);
						foreach ( $nb_colors as $field_id => $cfg ) :
							$val = esc_attr( $navbar_settings[ $cfg['key'] ] );
						?>
						<tr>
							<th><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $cfg['label'] ); ?></label></th>
							<td>
								<div class="wzp-nb-color-row">
									<input type="color" id="<?php echo esc_attr( $field_id ); ?>"
									       class="wzp-nb-color-picker" value="<?php echo $val; ?>">
									<input type="text" class="wzp-nb-color-text small-text"
									       value="<?php echo $val; ?>" maxlength="7" placeholder="#000000">
								</div>
							</td>
						</tr>
						<?php endforeach; ?>
					</table>

					<p>
						<button type="button" id="wzp-nb-save-btn" class="button button-primary">
							<?php esc_html_e( 'Save Navbar Settings', 'woo-zee-plugin' ); ?>
						</button>
						<span id="wzp-nb-saved-msg" class="wzp-saved-msg" style="display:none;">
							<?php esc_html_e( 'Saved!', 'woo-zee-plugin' ); ?>
						</span>
					</p>

				</div>
				<!-- /left col -->

				<!-- ── Right column: menu builder ───────────────────────────── -->
				<div class="wzp-nb-admin-col">

					<h3 class="wzp-nb-admin-section-title"><?php esc_html_e( 'Menu Builder', 'woo-zee-plugin' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Create menus here and assign them to the navbar above.', 'woo-zee-plugin' ); ?></p>

					<!-- Saved menus list -->
					<div id="wzp-mb-list" class="wzp-mb-list">
						<?php if ( empty( $saved_menus ) ) : ?>
							<p class="wzp-mb-empty"><?php esc_html_e( 'No menus yet. Create one below.', 'woo-zee-plugin' ); ?></p>
						<?php else : ?>
							<?php foreach ( $saved_menus as $sm ) :
								if ( empty( $sm['id'] ) ) { continue; }
							?>
								<div class="wzp-mb-menu-row" data-menu-id="<?php echo esc_attr( $sm['id'] ); ?>">
									<span class="wzp-mb-menu-name"><?php echo esc_html( $sm['name'] ?? $sm['id'] ); ?></span>
									<span class="wzp-mb-menu-id"><code><?php echo esc_html( $sm['id'] ); ?></code></span>
									<div class="wzp-mb-menu-actions">
										<button type="button" class="button button-small wzp-mb-edit-btn">
											<?php esc_html_e( 'Edit', 'woo-zee-plugin' ); ?>
										</button>
										<button type="button" class="button button-small button-link-delete wzp-mb-delete-btn">
											<?php esc_html_e( 'Delete', 'woo-zee-plugin' ); ?>
										</button>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<!-- Menu editor -->
					<div id="wzp-mb-editor" class="wzp-mb-editor" style="display:none;">
						<input type="hidden" id="wzp-mb-edit-id" value="">
						<div class="wzp-mb-editor-header">
							<input type="text" id="wzp-mb-edit-name" class="regular-text"
							       placeholder="<?php esc_attr_e( 'Menu name…', 'woo-zee-plugin' ); ?>">
						</div>
						<div id="wzp-mb-items" class="wzp-mb-items"></div>
						<button type="button" id="wzp-mb-add-item" class="button button-secondary">
							+ <?php esc_html_e( 'Add Item', 'woo-zee-plugin' ); ?>
						</button>
						<div class="wzp-mb-editor-footer">
							<button type="button" id="wzp-mb-save-btn" class="button button-primary">
								<?php esc_html_e( 'Save Menu', 'woo-zee-plugin' ); ?>
							</button>
							<button type="button" id="wzp-mb-cancel-btn" class="button button-secondary">
								<?php esc_html_e( 'Cancel', 'woo-zee-plugin' ); ?>
							</button>
							<span id="wzp-mb-saved-msg" class="wzp-saved-msg" style="display:none;">
								<?php esc_html_e( 'Saved!', 'woo-zee-plugin' ); ?>
							</span>
						</div>
					</div>

					<button type="button" id="wzp-mb-new-btn" class="button button-secondary wzp-mb-new-btn">
						+ <?php esc_html_e( 'New Menu', 'woo-zee-plugin' ); ?>
					</button>

				</div>
				<!-- /right col -->

			</div>
			<!-- /grid -->

		</div>
		<?php endif; /* navbar */ ?>


		<?php /* ══════════════════════════════════════════════════════════════
		   TAB — Product Detail
		   ══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'product-detail' === $active_tab ) : ?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-product-detail">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Product Detail', 'woo-zee-plugin' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Customise the single product detail layout. Use [wzp_product_detail] on any product page or template.', 'woo-zee-plugin' ); ?>
			</p>

			<div class="wzp-nb-shortcode-bar">
				<code class="wzp-nb-shortcode-code">[wzp_product_detail]</code>
				<button type="button" id="wzp-pd-copy-sc" class="button button-secondary">
					<?php esc_html_e( 'Copy', 'woo-zee-plugin' ); ?>
				</button>
			</div>

			<!-- ── Benefits repeater ──────────────────────────────────────── -->
			<h3 class="wzp-nb-admin-section-title"><?php esc_html_e( 'Benefits', 'woo-zee-plugin' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Displayed below the add-to-cart form. Drag to reorder.', 'woo-zee-plugin' ); ?></p>

			<div id="wzp-pd-benefits" class="wzp-repeater">
				<?php foreach ( (array) $pd_settings['benefits'] as $i => $benefit ) : ?>
				<div class="wzp-repeater-row" data-index="<?php echo esc_attr( $i ); ?>">
					<span class="wzp-repeater-handle" aria-hidden="true">⠿</span>
					<select class="wzp-pd-benefit-icon">
						<option value="">— Icon —</option>
						<?php foreach ( $pd_icon_options as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $benefit['icon'] ?? '', $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<input type="text" class="wzp-pd-benefit-title regular-text"
					       placeholder="<?php esc_attr_e( 'Title', 'woo-zee-plugin' ); ?>"
					       value="<?php echo esc_attr( $benefit['title'] ?? '' ); ?>">
					<input type="text" class="wzp-pd-benefit-subtitle regular-text"
					       placeholder="<?php esc_attr_e( 'Subtitle', 'woo-zee-plugin' ); ?>"
					       value="<?php echo esc_attr( $benefit['subtitle'] ?? '' ); ?>">
					<button type="button" class="button-link button-link-delete wzp-repeater-remove"
					        aria-label="<?php esc_attr_e( 'Remove benefit', 'woo-zee-plugin' ); ?>">✕</button>
				</div>
				<?php endforeach; ?>
			</div>
			<button type="button" id="wzp-pd-add-benefit" class="button button-secondary">
				+ <?php esc_html_e( 'Add Benefit', 'woo-zee-plugin' ); ?>
			</button>

			<!-- ── Shipping repeater ──────────────────────────────────────── -->
			<h3 class="wzp-nb-admin-section-title" style="margin-top:28px;">
				<?php esc_html_e( 'Shipping Info', 'woo-zee-plugin' ); ?>
			</h3>
			<p class="description"><?php esc_html_e( 'Short shipping lines shown above the benefits grid.', 'woo-zee-plugin' ); ?></p>

			<div id="wzp-pd-shipping" class="wzp-repeater">
				<?php foreach ( (array) $pd_settings['shipping'] as $i => $line ) : ?>
				<div class="wzp-repeater-row" data-index="<?php echo esc_attr( $i ); ?>">
					<span class="wzp-repeater-handle" aria-hidden="true">⠿</span>
					<select class="wzp-pd-ship-icon">
						<option value="">— Icon —</option>
						<?php foreach ( $pd_icon_options as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $line['icon'] ?? '', $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<input type="text" class="wzp-pd-ship-text large-text"
					       placeholder="<?php esc_attr_e( 'Shipping line text…', 'woo-zee-plugin' ); ?>"
					       value="<?php echo esc_attr( $line['text'] ?? '' ); ?>">
					<button type="button" class="button-link button-link-delete wzp-repeater-remove"
					        aria-label="<?php esc_attr_e( 'Remove line', 'woo-zee-plugin' ); ?>">✕</button>
				</div>
				<?php endforeach; ?>
			</div>
			<button type="button" id="wzp-pd-add-ship" class="button button-secondary">
				+ <?php esc_html_e( 'Add Shipping Line', 'woo-zee-plugin' ); ?>
			</button>

			<!-- ── Colour controls ────────────────────────────────────────── -->
			<h3 class="wzp-nb-admin-section-title" style="margin-top:28px;">
				<?php esc_html_e( 'Colours', 'woo-zee-plugin' ); ?>
			</h3>
			<table class="form-table wzp-form-table" role="presentation">
				<?php
				$pd_colors = array(
					'pd_accent_color' => array( 'label' => __( 'Accent / badge',      'woo-zee-plugin' ), 'key' => 'accent_color' ),
					'pd_btn_color'    => array( 'label' => __( 'Add to bag button',   'woo-zee-plugin' ), 'key' => 'btn_color' ),
					'pd_btn_text'     => array( 'label' => __( 'Button text',         'woo-zee-plugin' ), 'key' => 'btn_text' ),
					'pd_price_color'  => array( 'label' => __( 'Price',               'woo-zee-plugin' ), 'key' => 'price_color' ),
				);
				foreach ( $pd_colors as $field_id => $cfg ) :
					$val = esc_attr( $pd_settings[ $cfg['key'] ] );
				?>
				<tr>
					<th><label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $cfg['label'] ); ?></label></th>
					<td>
						<div class="wzp-nb-color-row">
							<input type="color" id="<?php echo esc_attr( $field_id ); ?>"
							       class="wzp-pd-color-picker" value="<?php echo $val; ?>">
							<input type="text" class="wzp-pd-color-text small-text"
							       value="<?php echo $val; ?>" maxlength="7" placeholder="#000000">
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>

			<p>
				<button type="button" id="wzp-pd-save-btn" class="button button-primary">
					<?php esc_html_e( 'Save Product Detail Settings', 'woo-zee-plugin' ); ?>
				</button>
				<span id="wzp-pd-saved-msg" class="wzp-saved-msg" style="display:none;">
					<?php esc_html_e( 'Saved!', 'woo-zee-plugin' ); ?>
				</span>
			</p>

		</div>
		<?php endif; /* product-detail */ ?>


		<?php /* ── Hidden templates (cloned by JS — always rendered) ──────── */ ?>

		<template id="wzp-slide-template">
			<div class="wzp-slide-row" data-index="__INDEX__">
				<div class="wzp-slide-header">
					<span class="wzp-slide-handle" aria-hidden="true">⠿</span>
					<span class="wzp-slide-title">
						<?php esc_html_e( 'New Slide', 'woo-zee-plugin' ); ?>
					</span>
					<button type="button"
					        class="wzp-slide-remove button-link button-link-delete"
					        aria-label="<?php esc_attr_e( 'Remove this slide', 'woo-zee-plugin' ); ?>">
						<?php esc_html_e( 'Remove', 'woo-zee-plugin' ); ?>
					</button>
				</div>
				<div class="wzp-slide-body">
					<div class="wzp-slide-field wzp-slide-field--media">
						<label><?php esc_html_e( 'Slide Image', 'woo-zee-plugin' ); ?></label>
						<input type="hidden" class="wzp-slide-image-id"
						       name="wzp_hero_slides[__INDEX__][image_id]" value="">
						<div class="wzp-media-preview"></div>
						<button type="button" class="button wzp-media-btn">
							<?php esc_html_e( 'Select Image', 'woo-zee-plugin' ); ?>
						</button>
						<button type="button"
						        class="button-link button-link-delete wzp-media-remove"
						        style="display:none">
							<?php esc_html_e( 'Remove image', 'woo-zee-plugin' ); ?>
						</button>
					</div>
					<?php
					$tpl_fields = array(
						'label'       => array( 'label' => __( 'Label (eyebrow text)', 'woo-zee-plugin' ), 'required' => false ),
						'heading'     => array( 'label' => __( 'Heading',             'woo-zee-plugin' ), 'required' => true  ),
						'description' => array( 'label' => __( 'Description',         'woo-zee-plugin' ), 'required' => false ),
						'btn_text'    => array( 'label' => __( 'Button Text',         'woo-zee-plugin' ), 'required' => false ),
						'btn_url'     => array( 'label' => __( 'Button URL',          'woo-zee-plugin' ), 'required' => false ),
					);
					foreach ( $tpl_fields as $fk => $fc ) :
						$is_url_tpl      = ( 'btn_url' === $fk );
						$is_textarea_tpl = ( 'description' === $fk );
					?>
					<div class="wzp-slide-field">
						<label>
							<?php echo esc_html( $fc['label'] ); ?>
							<?php if ( $fc['required'] ) : ?>
								<span class="wzp-required" aria-hidden="true">*</span>
							<?php endif; ?>
						</label>
						<?php if ( $is_textarea_tpl ) : ?>
							<textarea class="large-text wzp-slide-<?php echo esc_attr( str_replace( '_', '-', $fk ) ); ?>"
							          name="wzp_hero_slides[__INDEX__][<?php echo esc_attr( $fk ); ?>]"
							          rows="3"></textarea>
						<?php else : ?>
							<input type="<?php echo $is_url_tpl ? 'url' : 'text'; ?>"
							       class="large-text wzp-slide-<?php echo esc_attr( str_replace( '_', '-', $fk ) ); ?>"
							       name="wzp_hero_slides[__INDEX__][<?php echo esc_attr( $fk ); ?>]"
							       value="">
						<?php endif; ?>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		</template>

		<template id="wzp-hotspot-template">
			<div class="wzp-hotspot-row" data-index="__INDEX__">
				<input type="number"
				       name="wzp_lookbook_options[hotspots][__INDEX__][x]"
				       value="" min="0" max="100" step="0.1"
				       placeholder="X %" class="small-text">
				<input type="number"
				       name="wzp_lookbook_options[hotspots][__INDEX__][y]"
				       value="" min="0" max="100" step="0.1"
				       placeholder="Y %" class="small-text">
				<input type="number"
				       name="wzp_lookbook_options[hotspots][__INDEX__][product_id]"
				       value="" min="1" step="1"
				       placeholder="<?php esc_attr_e( 'Product ID', 'woo-zee-plugin' ); ?>"
				       class="small-text">
				<button type="button"
				        class="button-link button-link-delete wzp-hotspot-remove"
				        aria-label="<?php esc_attr_e( 'Remove hotspot', 'woo-zee-plugin' ); ?>">
					<?php esc_html_e( 'Remove', 'woo-zee-plugin' ); ?>
				</button>
			</div>
		</template>

		<template id="wzp-testimonial-template">
			<div class="wzp-review-row" data-index="__INDEX__">
				<div class="wzp-review-row__avatar-col">
					<input type="hidden" class="wzp-review-avatar-id"
					       name="wzp_testimonials_data[__INDEX__][avatar_id]" value="">
					<div class="wzp-review-avatar-preview wzp-avatar-circle"></div>
					<button type="button" class="button wzp-review-avatar-btn">
						<?php esc_html_e( 'Upload', 'woo-zee-plugin' ); ?>
					</button>
					<button type="button"
					        class="button-link button-link-delete wzp-review-avatar-remove"
					        style="display:none">
						<?php esc_html_e( '✕', 'woo-zee-plugin' ); ?>
					</button>
				</div>
				<div class="wzp-review-row__fields">
					<div class="wzp-review-field-row">
						<input type="text"
						       name="wzp_testimonials_data[__INDEX__][name]"
						       value=""
						       placeholder="<?php esc_attr_e( 'Customer Name *', 'woo-zee-plugin' ); ?>"
						       class="regular-text wzp-review-name">
						<input type="text"
						       name="wzp_testimonials_data[__INDEX__][location]"
						       value=""
						       placeholder="<?php esc_attr_e( 'Location (e.g. New York)', 'woo-zee-plugin' ); ?>"
						       class="regular-text">
					</div>
					<textarea name="wzp_testimonials_data[__INDEX__][review]"
					          rows="4"
					          placeholder="<?php esc_attr_e( 'Review text *', 'woo-zee-plugin' ); ?>"
					          class="large-text"></textarea>
				</div>
				<div class="wzp-review-row__actions">
					<span class="wzp-review-row__drag" aria-hidden="true" title="<?php esc_attr_e( 'Drag to reorder', 'woo-zee-plugin' ); ?>">⠿</span>
					<button type="button"
					        class="button-link button-link-delete wzp-review-remove"
					        aria-label="<?php esc_attr_e( 'Remove review', 'woo-zee-plugin' ); ?>">
						<?php esc_html_e( 'Remove', 'woo-zee-plugin' ); ?>
					</button>
				</div>
			</div>
		</template>

		<?php /* ═══════════════════════════════════════════════════════════════
		   TAB — Newsletter
		   ═══════════════════════════════════════════════════════════════════ */ ?>
		<?php if ( 'newsletter' === $active_tab ) : ?>
		<div class="wzp-tab-content wzp-tab-content--active" id="wzp-tab-newsletter">

			<h2 class="wzp-section-title"><?php esc_html_e( 'Newsletter Subscribers', 'woo-zee-plugin' ); ?></h2>
			<p class="description">
				<?php
				printf(
					/* translators: %s: shortcode */
					esc_html__( 'Use the %s shortcode to add the sign-up form anywhere on your site.', 'woo-zee-plugin' ),
					'<code>[wzp_newsletter]</code>'
				);
				?>
			</p>

			<?php /* ─ Toolbar ─────────────────────────────────────────────── */ ?>
			<div class="wzp-nl-toolbar">
				<span class="wzp-nl-count">
					<?php
					printf(
						/* translators: %d: subscriber count */
						esc_html( _n( '%d subscriber', '%d subscribers', $nl_total, 'woo-zee-plugin' ) ),
						$nl_total
					);
					?>
				</span>
				<div class="wzp-nl-toolbar__actions">
					<?php if ( $nl_total > 0 ) : ?>
						<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'wzp_newsletter_export', 'nonce' => wp_create_nonce( 'wzp_newsletter_admin_nonce' ) ), admin_url( 'admin-ajax.php' ) ) ); ?>"
						   class="button">
							<?php esc_html_e( 'Export CSV', 'woo-zee-plugin' ); ?>
						</a>
						<button type="button" class="button button-link-delete wzp-nl-bulk-delete-btn" style="margin-left:8px;" disabled>
							<?php esc_html_e( 'Delete Selected', 'woo-zee-plugin' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( empty( $nl_rows ) ) : ?>
				<div class="wzp-nl-empty">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="48" height="48" aria-hidden="true"><rect x="8" y="16" width="48" height="36" rx="4"/><polyline points="8,16 32,36 56,16"/></svg>
					<p><?php esc_html_e( 'No subscribers yet. Add the [wzp_newsletter] shortcode to start collecting emails.', 'woo-zee-plugin' ); ?></p>
				</div>
			<?php else : ?>
				<table class="widefat striped wzp-nl-table" id="wzp-nl-table">
					<thead>
						<tr>
							<th class="wzp-nl-th--check">
								<input type="checkbox" id="wzp-nl-check-all" aria-label="<?php esc_attr_e( 'Select all', 'woo-zee-plugin' ); ?>">
							</th>
							<th><?php esc_html_e( 'Email', 'woo-zee-plugin' ); ?></th>
							<th><?php esc_html_e( 'Status', 'woo-zee-plugin' ); ?></th>
							<th><?php esc_html_e( 'Subscribed At', 'woo-zee-plugin' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'woo-zee-plugin' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $nl_rows as $row ) : ?>
							<tr data-id="<?php echo esc_attr( $row['id'] ); ?>" class="wzp-nl-row">
								<td class="wzp-nl-td--check">
									<input type="checkbox" class="wzp-nl-check" value="<?php echo esc_attr( $row['id'] ); ?>" aria-label="<?php esc_attr_e( 'Select row', 'woo-zee-plugin' ); ?>">
								</td>
								<td class="wzp-nl-td--email"><?php echo esc_html( $row['email'] ); ?></td>
								<td class="wzp-nl-td--status">
									<span class="wzp-nl-badge wzp-nl-badge--<?php echo esc_attr( $row['status'] ); ?>">
										<?php echo esc_html( ucfirst( $row['status'] ) ); ?>
									</span>
								</td>
								<td class="wzp-nl-td--date">
									<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $row['subscribed_at'] ) ) ); ?>
								</td>
								<td class="wzp-nl-td--actions">
									<button type="button" class="button-link button-link-delete wzp-nl-delete-btn"
									        data-id="<?php echo esc_attr( $row['id'] ); ?>"
									        aria-label="<?php esc_attr_e( 'Delete subscriber', 'woo-zee-plugin' ); ?>">
										<?php esc_html_e( 'Delete', 'woo-zee-plugin' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<?php /* ─ Shortcode help ─────────────────────────────────────── */ ?>
			<div class="wzp-nl-help">
				<h3 class="wzp-subsection-title"><?php esc_html_e( 'Shortcode Options', 'woo-zee-plugin' ); ?></h3>
				<table class="wzp-form-table">
					<tr>
						<th><code>[wzp_newsletter]</code></th>
						<td><?php esc_html_e( 'Basic form — just the email input and arrow button.', 'woo-zee-plugin' ); ?></td>
					</tr>
					<tr>
						<th><code>heading="..."</code></th>
						<td><?php esc_html_e( 'Optional heading text displayed above the form.', 'woo-zee-plugin' ); ?></td>
					</tr>
					<tr>
						<th><code>subtext="..."</code></th>
						<td><?php esc_html_e( 'Optional sub-text / description below the heading.', 'woo-zee-plugin' ); ?></td>
					</tr>
					<tr>
						<th><code>btn_text="..."</code></th>
						<td><?php esc_html_e( 'Replace the arrow icon with a text label on the button.', 'woo-zee-plugin' ); ?></td>
					</tr>
				</table>
				<p class="description"><?php esc_html_e( 'Example:', 'woo-zee-plugin' ); ?>
					<code>[wzp_newsletter heading="Stay in the loop" subtext="New arrivals, exclusive deals." btn_text="Subscribe"]</code>
				</p>
			</div>

		</div><?php /* /.wzp-tab-content */ ?>
		<?php endif; ?>

	</div><?php /* /.wzp-tab-panels */ ?>

</div><?php /* /.wrap.wzp-admin-wrap */ ?>

<?php /* ── Inline JS: autoplay toggle + live shortcode previews ──────────── */ ?>
<script>
( function () {
	'use strict';

	/* ── Autoplay speed row toggle (carousel tab) ── */
	var autoplayChk = document.getElementById( 'wzp-pc-autoplay' );
	var speedRow    = document.getElementById( 'wzp-pc-speed-row' );

	if ( autoplayChk && speedRow ) {
		autoplayChk.addEventListener( 'change', function () {
			speedRow.style.display = this.checked ? '' : 'none';
		} );
	}

	/* ── Live shortcode preview — Category Carousel ── */
	function wzpBuildCatCarouselShortcode() {
		var pvEl    = document.getElementById( 'wzp-cc-per-view' );
		var obEl    = document.getElementById( 'wzp-cc-orderby' );
		var heEl    = document.getElementById( 'wzp-cc-hide-empty' );
		var szEl    = document.getElementById( 'wzp-cc-icon-size' );

		if ( ! pvEl ) { return ''; }

		var sc = '[wzp_category_carousel';
		sc += ' per_view="' + pvEl.value + '"';
		if ( obEl && obEl.value !== 'name' ) { sc += ' orderby="' + obEl.value + '"'; }
		if ( heEl && heEl.checked ) { sc += ' hide_empty="false"'; }
		if ( szEl && szEl.value !== '48' ) { sc += ' icon_size="' + szEl.value + '"'; }
		sc += ']';
		return sc;
	}

	var ccPreview = document.getElementById( 'wzp-cc-shortcode-preview' );
	if ( ccPreview ) {
		function updateCcPreview() { ccPreview.textContent = wzpBuildCatCarouselShortcode(); }
		updateCcPreview();
		[ 'wzp-cc-per-view', 'wzp-cc-orderby', 'wzp-cc-hide-empty', 'wzp-cc-icon-size' ].forEach( function ( id ) {
			var el = document.getElementById( id );
			if ( el ) { el.addEventListener( 'change', updateCcPreview ); el.addEventListener( 'input', updateCcPreview ); }
		} );
		var ccCopyBtn = document.getElementById( 'wzp-cc-copy-btn' );
		if ( ccCopyBtn ) {
			ccCopyBtn.addEventListener( 'click', function () {
				navigator.clipboard.writeText( ccPreview.textContent ).then( function () {
					ccCopyBtn.textContent = 'Copied!';
					setTimeout( function () { ccCopyBtn.textContent = 'Copy'; }, 2000 );
				} );
			} );
		}
	}

	/* ── Live shortcode preview — Product Grid ── */
	function wzpBuildGridShortcode() {
		var catEl   = document.getElementById( 'wzp-pg-category' );
		var colEl   = document.querySelector( 'input[name="wzp_product_grid_options[columns]"]:checked' );
		var countEl = document.getElementById( 'wzp-pg-count' );
		var orderEl = document.getElementById( 'wzp-pg-orderby' );

		if ( ! catEl ) { return ''; }

		var sc = '[wzp_product_grid';
		if ( catEl.value )   { sc += ' category="'  + catEl.value                      + '"'; }
		if ( colEl )         { sc += ' columns="'   + colEl.value                       + '"'; }
		if ( countEl )       { sc += ' count="'     + ( parseInt( countEl.value ) || 8 ) + '"'; }
		if ( orderEl && orderEl.value ) { sc += ' orderby="' + orderEl.value + '"'; }
		sc += ']';
		return sc;
	}

	var gridPreview = document.getElementById( 'wzp-grid-shortcode-preview' );
	if ( gridPreview ) {
		function updateGridPreview() {
			gridPreview.textContent = wzpBuildGridShortcode();
		}
		updateGridPreview();

		[ 'wzp-pg-category', 'wzp-pg-count', 'wzp-pg-orderby' ].forEach( function ( id ) {
			var el = document.getElementById( id );
			if ( el ) { el.addEventListener( 'change', updateGridPreview ); }
		} );

		document.querySelectorAll( 'input[name="wzp_product_grid_options[columns]"]' ).forEach( function ( el ) {
			el.addEventListener( 'change', updateGridPreview );
		} );

		var gridCopyBtn = document.getElementById( 'wzp-grid-copy-btn' );
		if ( gridCopyBtn ) {
			gridCopyBtn.addEventListener( 'click', function () {
				var txt = gridPreview.textContent;
				if ( ! txt ) { return; }
				navigator.clipboard.writeText( txt ).then( function () {
					gridCopyBtn.textContent = 'Copied!';
					setTimeout( function () { gridCopyBtn.textContent = 'Copy'; }, 2000 );
				} );
			} );
		}
	}

	/* ── Live shortcode preview — Product Carousel ── */
	function wzpBuildCarouselShortcode() {
		var catEl     = document.getElementById( 'wzp-pc-category' );
		var countEl   = document.getElementById( 'wzp-pc-count' );
		var perViewEl = document.getElementById( 'wzp-pc-per-view' );
		var autoEl    = document.getElementById( 'wzp-pc-autoplay' );
		var speedEl   = document.getElementById( 'wzp-pc-speed' );

		if ( ! catEl ) { return ''; }

		var sc = '[wzp_product_carousel';
		if ( catEl.value )     { sc += ' category="' + catEl.value                          + '"'; }
		if ( countEl )         { sc += ' count="'    + ( parseInt( countEl.value ) || 8 )   + '"'; }
		if ( perViewEl )       { sc += ' per_view="' + perViewEl.value                       + '"'; }
		sc += ' autoplay="' + ( autoEl && autoEl.checked ? 'true' : 'false' ) + '"';
		if ( autoEl && autoEl.checked && speedEl ) {
			sc += ' speed="' + ( parseInt( speedEl.value ) || 3000 ) + '"';
		}
		sc += ']';
		return sc;
	}

	var carouselPreview = document.getElementById( 'wzp-carousel-shortcode-preview' );
	if ( carouselPreview ) {
		function updateCarouselPreview() {
			carouselPreview.textContent = wzpBuildCarouselShortcode();
		}
		updateCarouselPreview();

		[ 'wzp-pc-category', 'wzp-pc-count', 'wzp-pc-per-view', 'wzp-pc-autoplay', 'wzp-pc-speed' ].forEach( function ( id ) {
			var el = document.getElementById( id );
			if ( el ) { el.addEventListener( 'change', updateCarouselPreview ); }
		} );

		var carouselCopyBtn = document.getElementById( 'wzp-carousel-copy-btn' );
		if ( carouselCopyBtn ) {
			carouselCopyBtn.addEventListener( 'click', function () {
				var txt = carouselPreview.textContent;
				if ( ! txt ) { return; }
				navigator.clipboard.writeText( txt ).then( function () {
					carouselCopyBtn.textContent = 'Copied!';
					setTimeout( function () { carouselCopyBtn.textContent = 'Copy'; }, 2000 );
				} );
			} );
		}
	}

	/* ── Newsletter admin: delete + bulk delete ── */
	( function () {
		var table   = document.getElementById( 'wzp-nl-table' );
		if ( ! table ) { return; }

		var checkAll  = document.getElementById( 'wzp-nl-check-all' );
		var bulkBtn   = document.querySelector( '.wzp-nl-bulk-delete-btn' );
		var ajaxUrl   = ( typeof wzpAdmin !== 'undefined' ) ? wzpAdmin.ajaxUrl : '';
		var nonce     = ( typeof wzpAdmin !== 'undefined' ) ? wzpAdmin.newsletterAdminNonce : '';

		function getCheckedIds() {
			return Array.from( table.querySelectorAll( '.wzp-nl-check:checked' ) ).map( function ( el ) {
				return el.value;
			} );
		}

		function syncBulkBtn() {
			if ( bulkBtn ) { bulkBtn.disabled = getCheckedIds().length === 0; }
		}

		if ( checkAll ) {
			checkAll.addEventListener( 'change', function () {
				table.querySelectorAll( '.wzp-nl-check' ).forEach( function ( el ) {
					el.checked = checkAll.checked;
				} );
				syncBulkBtn();
			} );
		}

		table.addEventListener( 'change', function ( e ) {
			if ( e.target.classList.contains( 'wzp-nl-check' ) ) {
				syncBulkBtn();
				if ( ! e.target.checked && checkAll ) { checkAll.checked = false; }
			}
		} );

		function deleteRow( id ) {
			var row = table.querySelector( 'tr[data-id="' + id + '"]' );
			if ( row ) { row.remove(); }
			// Update counter.
			var remaining = table.querySelectorAll( '.wzp-nl-row' ).length;
			var countEl   = document.querySelector( '.wzp-nl-count' );
			if ( countEl ) {
				countEl.textContent = remaining + ( remaining === 1 ? ' subscriber' : ' subscribers' );
			}
		}

		table.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.wzp-nl-delete-btn' );
			if ( ! btn ) { return; }
			if ( ! confirm( 'Delete this subscriber?' ) ) { return; }

			var id = btn.dataset.id;
			btn.disabled = true;

			var fd = new FormData();
			fd.append( 'action', 'wzp_newsletter_delete' );
			fd.append( 'nonce', nonce );
			fd.append( 'id', id );

			fetch( ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( res.success ) { deleteRow( id ); }
					else { btn.disabled = false; alert( res.data.message || 'Error.' ); }
				} )
				.catch( function () { btn.disabled = false; } );
		} );

		if ( bulkBtn ) {
			bulkBtn.addEventListener( 'click', function () {
				var ids = getCheckedIds();
				if ( ! ids.length ) { return; }
				if ( ! confirm( 'Delete ' + ids.length + ' subscriber(s)?' ) ) { return; }

				bulkBtn.disabled = true;

				var fd = new FormData();
				fd.append( 'action', 'wzp_newsletter_bulk_delete' );
				fd.append( 'nonce', nonce );
				ids.forEach( function ( id ) { fd.append( 'ids[]', id ); } );

				fetch( ajaxUrl, { method: 'POST', credentials: 'same-origin', body: fd } )
					.then( function ( r ) { return r.json(); } )
					.then( function ( res ) {
						if ( res.success ) {
							ids.forEach( function ( id ) { deleteRow( id ); } );
							if ( checkAll ) { checkAll.checked = false; }
						}
						bulkBtn.disabled = getCheckedIds().length === 0;
					} )
					.catch( function () { bulkBtn.disabled = false; } );
			} );
		}
	} )();

} )();
</script>
