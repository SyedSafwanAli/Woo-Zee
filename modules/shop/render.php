<?php
/**
 * [wzp_shop] — Full shop page shortcode.
 *
 * Attributes:
 *   category  string  Pre-filter to a category slug.
 *   columns   int     Grid columns (3 or 4). Default 4.
 *   per_page  int     Products per page. Default 12.
 *   orderby   string  Default sort: date|price|price-desc|popularity|rating|title.
 *   title     string  Optional H1 above the shop. Empty = no heading.
 *
 * URL params (GET) are honoured so filtered pages are shareable and crawlable:
 *   wzp_cats[]    category slugs
 *   wzp_min       minimum price
 *   wzp_max       maximum price
 *   wzp_sort      orderby slug
 *   wzp_sale      1 = on sale only
 *   wzp_s         search keyword
 *   wzp_page      page number
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) ) { return; }

require_once WZP_PATH . 'modules/product-grid/card.php';
require_once WZP_PATH . 'modules/shop/query.php';

// ── Shortcode atts ────────────────────────────────────────────────────────────
$atts = shortcode_atts(
	array(
		'category' => '',
		'columns'  => '4',
		'per_page' => '12',
		'orderby'  => 'date',
		'title'    => '',
	),
	$atts,
	'wzp_shop'
);

$default_orderby = sanitize_key( $atts['orderby'] );
$columns         = max( 2, min( 5, intval( $atts['columns'] ) ) );
$per_page        = max( 4, min( 60, intval( $atts['per_page'] ) ) );
$default_cat     = sanitize_title( $atts['category'] );
$shop_title      = sanitize_text_field( $atts['title'] );

// ── Read URL filters (GET) ────────────────────────────────────────────────────
// phpcs:disable WordPress.Security.NonceVerification
$url_cats      = isset( $_GET['wzp_cats'] )
	? array_values( array_filter( array_map( 'sanitize_title', (array) $_GET['wzp_cats'] ) ) )
	: ( $default_cat ? array( $default_cat ) : array() );
$url_min       = isset( $_GET['wzp_min'] ) ? max( 0, floatval( $_GET['wzp_min'] ) ) : 0;
$url_max       = isset( $_GET['wzp_max'] ) ? max( 0, floatval( $_GET['wzp_max'] ) ) : 0;
$url_sort      = isset( $_GET['wzp_sort'] ) ? sanitize_key( $_GET['wzp_sort'] ) : $default_orderby;
$url_on_sale   = ! empty( $_GET['wzp_sale'] );
$url_search    = isset( $_GET['wzp_s'] ) ? sanitize_text_field( $_GET['wzp_s'] ) : '';
$current_page  = isset( $_GET['wzp_page'] ) ? max( 1, intval( $_GET['wzp_page'] ) ) : 1;
// phpcs:enable

// ── Run query ─────────────────────────────────────────────────────────────────
$filters = array(
	'per_page'  => $per_page,
	'page'      => $current_page,
	'orderby'   => $url_sort,
	'cats'      => $url_cats,
	'min_price' => $url_min,
	'max_price' => $url_max,
	'on_sale'   => $url_on_sale,
	'search'    => $url_search,
);

$query       = new WP_Query( wzp_build_shop_query_args( $filters ) );
$total_found = $query->found_posts;
$total_pages = $query->max_num_pages;
$from        = ( ( $current_page - 1 ) * $per_page ) + 1;
$to          = min( $current_page * $per_page, $total_found );

// ── Categories for sidebar ────────────────────────────────────────────────────
$all_cats = get_terms( array(
	'taxonomy'   => 'product_cat',
	'hide_empty' => true,
	'parent'     => 0,
	'orderby'    => 'name',
) );

// ── Price bounds for slider ───────────────────────────────────────────────────
$bounds    = wzp_shop_price_bounds();
$slider_min = $bounds['min'];
$slider_max = $bounds['max'];
$active_min = $url_min ?: $slider_min;
$active_max = $url_max ?: $slider_max;

// ── Sort options ──────────────────────────────────────────────────────────────
$sort_options = array(
	'date'       => __( 'Newest',          'woo-zee-plugin' ),
	'popularity' => __( 'Most Popular',    'woo-zee-plugin' ),
	'rating'     => __( 'Top Rated',       'woo-zee-plugin' ),
	'price'      => __( 'Price: Low → High', 'woo-zee-plugin' ),
	'price-desc' => __( 'Price: High → Low', 'woo-zee-plugin' ),
	'title'      => __( 'A → Z',           'woo-zee-plugin' ),
);

// ── JSON-LD Structured Data ───────────────────────────────────────────────────
$structured_data = array(
	'@context'        => 'https://schema.org',
	'@type'           => 'ItemList',
	'name'            => $shop_title ?: get_bloginfo( 'name' ) . ' — Shop',
	'numberOfItems'   => $total_found,
	'itemListElement' => array(),
);

$position = $from;
foreach ( $query->posts as $post ) {
	$p = wc_get_product( $post->ID );
	if ( ! $p instanceof WC_Product ) { continue; }

	$img_id  = $p->get_image_id();
	$img_url = $img_id ? wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail' ) : '';

	$item = array(
		'@type'    => 'ListItem',
		'position' => $position++,
		'item'     => array(
			'@type'  => 'Product',
			'name'   => $p->get_name(),
			'url'    => get_permalink( $p->get_id() ),
			'offers' => array(
				'@type'         => 'Offer',
				'price'         => $p->get_price(),
				'priceCurrency' => get_woocommerce_currency(),
				'availability'  => $p->is_in_stock()
					? 'https://schema.org/InStock'
					: 'https://schema.org/OutOfStock',
			),
		),
	);
	if ( $img_url ) {
		$item['item']['image'] = $img_url;
	}
	$sku = $p->get_sku();
	if ( $sku ) {
		$item['item']['sku'] = $sku;
	}

	$structured_data['itemListElement'][] = $item;
}

// ── Active filter chips ───────────────────────────────────────────────────────
$active_chips = array();
foreach ( $url_cats as $cat_slug ) {
	$term = get_term_by( 'slug', $cat_slug, 'product_cat' );
	if ( $term ) {
		$active_chips[] = array( 'type' => 'cat', 'slug' => $cat_slug, 'label' => $term->name );
	}
}
if ( $url_min > $slider_min || ( $url_max > 0 && $url_max < $slider_max ) ) {
	$active_chips[] = array(
		'type'  => 'price',
		'label' => sprintf( 'Rs %s – Rs %s', number_format( $active_min, 0 ), number_format( $active_max, 0 ) ),
	);
}
if ( $url_on_sale ) {
	$active_chips[] = array( 'type' => 'sale', 'label' => __( 'On Sale', 'woo-zee-plugin' ) );
}
if ( $url_search ) {
	$active_chips[] = array( 'type' => 'search', 'label' => sprintf( '"%s"', $url_search ) );
}

?>
<?php /* ── JSON-LD ── */ ?>
<script type="application/ld+json"><?php echo wp_json_encode( $structured_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>

<section class="wzp-module wzp-shop"
         data-wzp-module="shop"
         data-columns="<?php echo esc_attr( $columns ); ?>"
         data-per-page="<?php echo esc_attr( $per_page ); ?>"
         data-slider-min="<?php echo esc_attr( $slider_min ); ?>"
         data-slider-max="<?php echo esc_attr( $slider_max ); ?>"
         data-nonce="<?php echo esc_attr( wp_create_nonce( 'wzp_shop_nonce' ) ); ?>">

	<?php if ( $shop_title ) : ?>
	<h1 class="wzp-shop__heading"><?php echo esc_html( $shop_title ); ?></h1>
	<?php endif; ?>

	<?php /* ── Top bar ── */ ?>
	<div class="wzp-shop__topbar">

		<button class="wzp-shop__filter-toggle" aria-expanded="false" aria-controls="wzp-shop-sidebar">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="16" height="16"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="12" y1="18" x2="12" y2="18"/></svg>
			<?php esc_html_e( 'Filters', 'woo-zee-plugin' ); ?>
		</button>

		<p class="wzp-shop__results-count" aria-live="polite" aria-atomic="true">
			<?php if ( $total_found > 0 ) :
				printf(
					/* translators: 1: from, 2: to, 3: total */
					esc_html__( 'Showing %1$s–%2$s of %3$s products', 'woo-zee-plugin' ),
					number_format_i18n( $from ),
					number_format_i18n( $to ),
					number_format_i18n( $total_found )
				);
			else :
				esc_html_e( 'No products found', 'woo-zee-plugin' );
			endif; ?>
		</p>

		<div class="wzp-shop__topbar-right">
			<label class="screen-reader-text" for="wzp-shop-sort"><?php esc_html_e( 'Sort by', 'woo-zee-plugin' ); ?></label>
			<select id="wzp-shop-sort" class="wzp-shop__sort" aria-label="<?php esc_attr_e( 'Sort products', 'woo-zee-plugin' ); ?>">
				<?php foreach ( $sort_options as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $url_sort, $val ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
				<?php endforeach; ?>
			</select>

		</div>

	</div>
	<?php /* /topbar */ ?>

	<?php /* ── Active filter chips ── */ ?>
	<?php if ( ! empty( $active_chips ) ) : ?>
	<div class="wzp-shop__active-filters" role="list" aria-label="<?php esc_attr_e( 'Active filters', 'woo-zee-plugin' ); ?>">
		<?php foreach ( $active_chips as $chip ) : ?>
		<span class="wzp-shop__chip"
		      data-chip-type="<?php echo esc_attr( $chip['type'] ); ?>"
		      <?php echo isset( $chip['slug'] ) ? 'data-chip-slug="' . esc_attr( $chip['slug'] ) . '"' : ''; ?>
		      role="listitem">
			<?php echo esc_html( $chip['label'] ); ?>
			<button type="button" class="wzp-shop__chip-remove" aria-label="<?php esc_attr_e( 'Remove filter', 'woo-zee-plugin' ); ?>">&#215;</button>
		</span>
		<?php endforeach; ?>
		<button type="button" class="wzp-shop__clear-all"><?php esc_html_e( 'Clear all', 'woo-zee-plugin' ); ?></button>
	</div>
	<?php endif; ?>

	<div class="wzp-shop__layout">

		<?php /* ── Sidebar / Filters ── */ ?>
		<aside class="wzp-shop__sidebar" id="wzp-shop-sidebar" aria-label="<?php esc_attr_e( 'Product filters', 'woo-zee-plugin' ); ?>">

			<div class="wzp-shop__sidebar-header">
				<span class="wzp-shop__sidebar-title"><?php esc_html_e( 'Filters', 'woo-zee-plugin' ); ?></span>
				<button class="wzp-shop__sidebar-close" aria-label="<?php esc_attr_e( 'Close filters', 'woo-zee-plugin' ); ?>">&#215;</button>
			</div>

			<?php /* Search */ ?>
			<div class="wzp-shop__filter-group">
				<h3 class="wzp-shop__filter-label"><?php esc_html_e( 'Search', 'woo-zee-plugin' ); ?></h3>
				<div class="wzp-shop__search-wrap">
					<input type="search"
					       class="wzp-shop__search-input"
					       id="wzp-shop-search"
					       placeholder="<?php esc_attr_e( 'Search products…', 'woo-zee-plugin' ); ?>"
					       value="<?php echo esc_attr( $url_search ); ?>"
					       autocomplete="off">
					<svg class="wzp-shop__search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
				</div>
			</div>

			<?php /* Categories */ ?>
			<?php if ( ! empty( $all_cats ) && ! is_wp_error( $all_cats ) ) : ?>
			<div class="wzp-shop__filter-group">
				<h3 class="wzp-shop__filter-label"><?php esc_html_e( 'Categories', 'woo-zee-plugin' ); ?></h3>
				<ul class="wzp-shop__cat-list" role="list">
					<?php foreach ( $all_cats as $cat ) :
						$is_checked = in_array( $cat->slug, $url_cats, true );
						// Get children.
						$children = get_terms( array(
							'taxonomy'   => 'product_cat',
							'hide_empty' => true,
							'parent'     => $cat->term_id,
							'orderby'    => 'name',
						) );
					?>
					<li class="wzp-shop__cat-item<?php echo ! empty( $children ) && ! is_wp_error( $children ) ? ' wzp-shop__cat-item--has-children' : ''; ?>">
						<label class="wzp-shop__cat-label">
							<input type="checkbox"
							       class="wzp-shop__cat-check"
							       name="wzp_cats[]"
							       value="<?php echo esc_attr( $cat->slug ); ?>"
							       <?php checked( $is_checked ); ?>>
							<span><?php echo esc_html( $cat->name ); ?></span>
							<span class="wzp-shop__cat-count">(<?php echo absint( $cat->count ); ?>)</span>
						</label>
						<?php if ( ! empty( $children ) && ! is_wp_error( $children ) ) : ?>
						<ul class="wzp-shop__cat-children" <?php echo empty( $url_cats ) ? 'hidden' : ''; ?>>
							<?php foreach ( $children as $child ) : ?>
							<li class="wzp-shop__cat-item">
								<label class="wzp-shop__cat-label">
									<input type="checkbox"
									       class="wzp-shop__cat-check"
									       name="wzp_cats[]"
									       value="<?php echo esc_attr( $child->slug ); ?>"
									       <?php checked( in_array( $child->slug, $url_cats, true ) ); ?>>
									<span><?php echo esc_html( $child->name ); ?></span>
									<span class="wzp-shop__cat-count">(<?php echo absint( $child->count ); ?>)</span>
								</label>
							</li>
							<?php endforeach; ?>
						</ul>
						<?php endif; ?>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<?php /* Price Range */ ?>
			<?php if ( $slider_max > $slider_min ) : ?>
			<div class="wzp-shop__filter-group">
				<h3 class="wzp-shop__filter-label"><?php esc_html_e( 'Price', 'woo-zee-plugin' ); ?></h3>
				<div class="wzp-price-range"
				     data-min="<?php echo esc_attr( $slider_min ); ?>"
				     data-max="<?php echo esc_attr( $slider_max ); ?>">
					<div class="wzp-price-range__track">
						<div class="wzp-price-range__fill"></div>
					</div>
					<input type="range"
					       class="wzp-price-range__input wzp-price-range__input--min"
					       min="<?php echo esc_attr( $slider_min ); ?>"
					       max="<?php echo esc_attr( $slider_max ); ?>"
					       value="<?php echo esc_attr( $active_min ); ?>"
					       aria-label="<?php esc_attr_e( 'Minimum price', 'woo-zee-plugin' ); ?>">
					<input type="range"
					       class="wzp-price-range__input wzp-price-range__input--max"
					       min="<?php echo esc_attr( $slider_min ); ?>"
					       max="<?php echo esc_attr( $slider_max ); ?>"
					       value="<?php echo esc_attr( $active_max ); ?>"
					       aria-label="<?php esc_attr_e( 'Maximum price', 'woo-zee-plugin' ); ?>">
					<div class="wzp-price-range__vals">
						<span>Rs <strong class="wzp-price-range__val-min"><?php echo number_format( $active_min, 0 ); ?></strong></span>
						<span>Rs <strong class="wzp-price-range__val-max"><?php echo number_format( $active_max, 0 ); ?></strong></span>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php /* On Sale toggle */ ?>
			<div class="wzp-shop__filter-group">
				<label class="wzp-shop__toggle-label">
					<input type="checkbox"
					       class="wzp-shop__on-sale"
					       id="wzp-shop-on-sale"
					       <?php checked( $url_on_sale ); ?>>
					<span class="wzp-shop__toggle-track"><span class="wzp-shop__toggle-thumb"></span></span>
					<?php esc_html_e( 'On Sale Only', 'woo-zee-plugin' ); ?>
				</label>
			</div>

			<button type="button" class="wzp-shop__apply-btn"><?php esc_html_e( 'Apply Filters', 'woo-zee-plugin' ); ?></button>
			<button type="button" class="wzp-shop__reset-btn"><?php esc_html_e( 'Reset', 'woo-zee-plugin' ); ?></button>

		</aside>
		<?php /* /sidebar */ ?>

		<?php /* Backdrop (mobile) */ ?>
		<div class="wzp-shop__backdrop"></div>

		<?php /* ── Main content ── */ ?>
		<div class="wzp-shop__main">

			<?php /* Loading overlay */ ?>
			<div class="wzp-shop__loading-overlay" aria-hidden="true">
				<div class="wzp-shop__spinner"></div>
			</div>

			<?php /* Product grid */ ?>
			<div class="wzp-shop__grid wzp-shop__grid--cols-<?php echo esc_attr( $columns ); ?>"
			     id="wzp-shop-grid"
			     aria-live="polite"
			     aria-busy="false">

				<?php if ( $query->have_posts() ) :
					while ( $query->have_posts() ) :
						$query->the_post();
						echo wzp_render_product_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
					endwhile;
					wp_reset_postdata();
				else : ?>
				<div class="wzp-shop__empty">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="wzp-shop__empty-icon">
						<circle cx="32" cy="32" r="28"/>
						<path d="M20 32h24M32 20v24"/>
					</svg>
					<p class="wzp-shop__empty-text"><?php esc_html_e( 'No products match your filters.', 'woo-zee-plugin' ); ?></p>
					<button type="button" class="wzp-shop__reset-btn"><?php esc_html_e( 'Clear Filters', 'woo-zee-plugin' ); ?></button>
				</div>
				<?php endif; ?>

			</div>
			<?php /* /grid */ ?>

			<?php /* Pagination */ ?>
			<div id="wzp-shop-pagination">
				<?php echo wzp_shop_pagination( $current_page, $total_pages ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>

		</div>
		<?php /* /main */ ?>

	</div>
	<?php /* /layout */ ?>

</section>
