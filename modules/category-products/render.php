<?php
/**
 * [wzp_category_products] — Category product listing shortcode.
 *
 * Attributes:
 *   category      string  Category slug (required).
 *   columns       int     Grid columns 2–5. Default 4.
 *   per_page      int     Products per page. Default 12.
 *   show_subcats  bool    Show subcategory cards above grid. Default true.
 *   show_banner   bool    Show category image banner. Default true.
 *   show_desc     bool    Show category description. Default true.
 *
 * URL params:
 *   wzp_cp_sort   string  Sort slug.
 *   wzp_cp_page   int     Page number.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) ) { return; }

require_once WZP_PATH . 'modules/product-grid/card.php';
require_once WZP_PATH . 'modules/shop/query.php';

// ── Shortcode atts ─────────────────────────────────────────────────────────────
$atts = shortcode_atts(
	array(
		'category'     => '',
		'columns'      => '4',
		'per_page'     => '12',
		'show_subcats' => 'true',
		'show_banner'  => 'true',
		'show_desc'    => 'true',
	),
	$atts,
	'wzp_category_products'
);

$cat_slug     = sanitize_title( $atts['category'] );
$columns      = max( 2, min( 5, intval( $atts['columns'] ) ) );
$per_page     = max( 4, min( 60, intval( $atts['per_page'] ) ) );
$show_subcats = filter_var( $atts['show_subcats'], FILTER_VALIDATE_BOOLEAN );
$show_banner  = filter_var( $atts['show_banner'],  FILTER_VALIDATE_BOOLEAN );
$show_desc    = filter_var( $atts['show_desc'],    FILTER_VALIDATE_BOOLEAN );

// Auto-detect current category from the queried object (works in Divi Theme Builder).
if ( ! $cat_slug ) {
	$queried = get_queried_object();
	if ( $queried instanceof WP_Term && $queried->taxonomy === 'product_cat' ) {
		$cat_slug = $queried->slug;
	}
}

if ( ! $cat_slug ) { return; }

// ── Category term ──────────────────────────────────────────────────────────────
$term = get_term_by( 'slug', $cat_slug, 'product_cat' );
if ( ! $term instanceof WP_Term ) { return; }

$cat_name  = $term->name;
$cat_desc  = $term->description;
$cat_img   = '';
$img_id    = absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
if ( $img_id ) {
	$cat_img = wp_get_attachment_image_url( $img_id, 'large' );
}

// ── URL params ─────────────────────────────────────────────────────────────────
// phpcs:disable WordPress.Security.NonceVerification
$sort_options = array(
	'date'       => __( 'Newest',            'woo-zee-plugin' ),
	'popularity' => __( 'Most Popular',      'woo-zee-plugin' ),
	'rating'     => __( 'Top Rated',         'woo-zee-plugin' ),
	'price'      => __( 'Price: Low → High', 'woo-zee-plugin' ),
	'price-desc' => __( 'Price: High → Low', 'woo-zee-plugin' ),
	'title'      => __( 'A → Z',             'woo-zee-plugin' ),
);

$active_sort  = isset( $_GET['wzp_cp_sort'] ) && array_key_exists( $_GET['wzp_cp_sort'], $sort_options )
	? sanitize_key( $_GET['wzp_cp_sort'] )
	: 'date';
$current_page = isset( $_GET['wzp_cp_page'] ) ? max( 1, intval( $_GET['wzp_cp_page'] ) ) : 1;
// phpcs:enable

// ── Subcategories ──────────────────────────────────────────────────────────────
$subcats = array();
if ( $show_subcats ) {
	$subcats = get_terms( array(
		'taxonomy'   => 'product_cat',
		'parent'     => $term->term_id,
		'hide_empty' => true,
		'orderby'    => 'name',
	) );
	if ( is_wp_error( $subcats ) ) { $subcats = array(); }
}

// ── Query ──────────────────────────────────────────────────────────────────────
$query_args = wzp_build_shop_query_args( array(
	'per_page' => $per_page,
	'page'     => $current_page,
	'orderby'  => $active_sort,
	'cats'     => array( $cat_slug ),
) );

$query       = new WP_Query( $query_args );
$total_found = $query->found_posts;
$total_pages = $query->max_num_pages;
$from        = ( ( $current_page - 1 ) * $per_page ) + 1;
$to          = min( $current_page * $per_page, $total_found );

// ── Breadcrumb + JSON-LD ──────────────────────────────────────────────────────
$ancestors   = get_ancestors( $term->term_id, 'product_cat', 'taxonomy' );
$breadcrumbs = array(
	array( 'name' => __( 'Home', 'woo-zee-plugin' ),  'url' => home_url( '/' ) ),
	array( 'name' => __( 'Shop', 'woo-zee-plugin' ),  'url' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ),
);

foreach ( array_reverse( $ancestors ) as $ancestor_id ) {
	$anc = get_term( $ancestor_id, 'product_cat' );
	if ( $anc instanceof WP_Term ) {
		$breadcrumbs[] = array( 'name' => $anc->name, 'url' => get_term_link( $anc ) );
	}
}
$breadcrumbs[] = array( 'name' => $cat_name, 'url' => get_term_link( $term ) );

$ld_breadcrumb = array(
	'@context'        => 'https://schema.org',
	'@type'           => 'BreadcrumbList',
	'itemListElement' => array(),
);
foreach ( $breadcrumbs as $i => $crumb ) {
	$ld_breadcrumb['itemListElement'][] = array(
		'@type'    => 'ListItem',
		'position' => $i + 1,
		'name'     => $crumb['name'],
		'item'     => $crumb['url'],
	);
}

$ld_items = array(
	'@context'        => 'https://schema.org',
	'@type'           => 'ItemList',
	'name'            => $cat_name,
	'numberOfItems'   => $total_found,
	'itemListElement' => array(),
);
$position = $from;
foreach ( $query->posts as $post ) {
	$p      = wc_get_product( $post->ID );
	if ( ! $p instanceof WC_Product ) { continue; }
	$img_pid = $p->get_image_id();
	$ld_items['itemListElement'][] = array(
		'@type'    => 'ListItem',
		'position' => $position++,
		'item'     => array(
			'@type'  => 'Product',
			'name'   => $p->get_name(),
			'url'    => get_permalink( $p->get_id() ),
			'image'  => $img_pid ? wp_get_attachment_image_url( $img_pid, 'medium' ) : '',
			'offers' => array(
				'@type'         => 'Offer',
				'price'         => $p->get_price(),
				'priceCurrency' => get_woocommerce_currency(),
				'availability'  => $p->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
			),
		),
	);
}
?>
<script type="application/ld+json"><?php echo wp_json_encode( $ld_breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
<script type="application/ld+json"><?php echo wp_json_encode( $ld_items,      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>

<section class="wzp-module wzp-cp"
         data-wzp-module="category-products"
         data-cat="<?php echo esc_attr( $cat_slug ); ?>"
         data-columns="<?php echo esc_attr( $columns ); ?>"
         data-per-page="<?php echo esc_attr( $per_page ); ?>"
         data-nonce="<?php echo esc_attr( wp_create_nonce( 'wzp_cp_nonce' ) ); ?>">

	<?php /* ── Category banner ── */ ?>
	<?php if ( $show_banner && $cat_img ) : ?>
	<div class="wzp-cp__banner" style="background-image: url('<?php echo esc_url( $cat_img ); ?>');">
		<div class="wzp-cp__banner-inner">
			<?php /* Breadcrumb */ ?>
			<nav class="wzp-cp__breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'woo-zee-plugin' ); ?>">
				<?php foreach ( $breadcrumbs as $i => $crumb ) : ?>
					<?php if ( $i > 0 ) : ?><span class="wzp-cp__breadcrumb-sep" aria-hidden="true">/</span><?php endif; ?>
					<?php if ( $i < count( $breadcrumbs ) - 1 ) : ?>
						<a href="<?php echo esc_url( $crumb['url'] ); ?>" class="wzp-cp__breadcrumb-link"><?php echo esc_html( $crumb['name'] ); ?></a>
					<?php else : ?>
						<span class="wzp-cp__breadcrumb-current" aria-current="page"><?php echo esc_html( $crumb['name'] ); ?></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>
			<h1 class="wzp-cp__banner-title"><?php echo esc_html( $cat_name ); ?></h1>
			<?php if ( $total_found > 0 ) : ?>
			<p class="wzp-cp__banner-count">
				<?php printf( esc_html( _n( '%s product', '%s products', $total_found, 'woo-zee-plugin' ) ), number_format_i18n( $total_found ) ); ?>
			</p>
			<?php endif; ?>
		</div>
	</div>
	<?php elseif ( ! $show_banner || ! $cat_img ) : ?>
	<?php /* Minimal header when no banner image */ ?>
	<div class="wzp-cp__header">
		<nav class="wzp-cp__breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'woo-zee-plugin' ); ?>">
			<?php foreach ( $breadcrumbs as $i => $crumb ) : ?>
				<?php if ( $i > 0 ) : ?><span class="wzp-cp__breadcrumb-sep" aria-hidden="true">/</span><?php endif; ?>
				<?php if ( $i < count( $breadcrumbs ) - 1 ) : ?>
					<a href="<?php echo esc_url( $crumb['url'] ); ?>" class="wzp-cp__breadcrumb-link"><?php echo esc_html( $crumb['name'] ); ?></a>
				<?php else : ?>
					<span class="wzp-cp__breadcrumb-current" aria-current="page"><?php echo esc_html( $crumb['name'] ); ?></span>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>
		<h1 class="wzp-cp__title"><?php echo esc_html( $cat_name ); ?></h1>
	</div>
	<?php endif; ?>

	<?php /* ── Category description ── */ ?>
	<?php if ( $show_desc && $cat_desc ) : ?>
	<div class="wzp-cp__desc"><?php echo wp_kses_post( $cat_desc ); ?></div>
	<?php endif; ?>

	<?php /* ── Subcategories ── */ ?>
	<?php if ( ! empty( $subcats ) ) : ?>
	<div class="wzp-cp__subcats">
		<?php foreach ( $subcats as $sub ) :
			$sub_img_id  = absint( get_term_meta( $sub->term_id, 'thumbnail_id', true ) );
			$sub_img_url = $sub_img_id
				? wp_get_attachment_image_url( $sub_img_id, 'medium' )
				: wc_placeholder_img_src( 'medium' );
		?>
		<a href="<?php echo esc_url( get_term_link( $sub ) ); ?>"
		   class="wzp-cp__subcat"
		   aria-label="<?php echo esc_attr( $sub->name ); ?>">
			<div class="wzp-cp__subcat-img-wrap">
				<img src="<?php echo esc_url( $sub_img_url ); ?>"
				     alt="<?php echo esc_attr( $sub->name ); ?>"
				     class="wzp-cp__subcat-img"
				     loading="lazy">
			</div>
			<span class="wzp-cp__subcat-name"><?php echo esc_html( $sub->name ); ?></span>
			<span class="wzp-cp__subcat-count"><?php printf( esc_html( _n( '%d item', '%d items', $sub->count, 'woo-zee-plugin' ) ), $sub->count ); ?></span>
		</a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<?php /* ── Toolbar ── */ ?>
	<div class="wzp-cp__toolbar">
		<p class="wzp-cp__count" aria-live="polite">
			<?php if ( $total_found > 0 ) :
				printf(
					esc_html__( 'Showing %1$s–%2$s of %3$s products', 'woo-zee-plugin' ),
					'<strong>' . number_format_i18n( $from ) . '</strong>',
					'<strong>' . number_format_i18n( $to ) . '</strong>',
					'<strong>' . number_format_i18n( $total_found ) . '</strong>'
				);
			else :
				esc_html_e( 'No products found', 'woo-zee-plugin' );
			endif; ?>
		</p>

		<label class="screen-reader-text" for="wzp-cp-sort"><?php esc_html_e( 'Sort by', 'woo-zee-plugin' ); ?></label>
		<select id="wzp-cp-sort" class="wzp-cp__sort">
			<?php foreach ( $sort_options as $val => $label ) : ?>
			<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $active_sort, $val ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
			<?php endforeach; ?>
		</select>
	</div>

	<?php /* ── Loading overlay ── */ ?>
	<div class="wzp-cp__loading-overlay" aria-hidden="true">
		<div class="wzp-cp__spinner"></div>
	</div>

	<?php /* ── Product grid ── */ ?>
	<div class="wzp-cp__grid wzp-cp__grid--cols-<?php echo esc_attr( $columns ); ?>"
	     id="wzp-cp-grid"
	     aria-live="polite"
	     aria-busy="false">

		<?php if ( $query->have_posts() ) :
			while ( $query->have_posts() ) :
				$query->the_post();
				echo wzp_render_product_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
			endwhile;
			wp_reset_postdata();
		else : ?>
		<div class="wzp-cp__empty">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="wzp-cp__empty-icon">
				<circle cx="32" cy="32" r="28"/>
				<path d="M20 32h24M32 20v24"/>
			</svg>
			<p><?php esc_html_e( 'No products in this category yet.', 'woo-zee-plugin' ); ?></p>
		</div>
		<?php endif; ?>

	</div>

	<?php /* ── Pagination ── */ ?>
	<div id="wzp-cp-pagination" class="wzp-cp__pagination">
		<?php echo wzp_shop_pagination( $current_page, $total_pages ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>

</section>
