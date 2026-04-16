<?php
/**
 * [wzp_search_results] — Product search results grid.
 *
 * Reads the search query from WordPress native ?s= or ?wzp_s= param.
 * Reuses the shop query builder, product card, and grid CSS.
 *
 * Attributes:
 *   columns  int    Grid columns (2–5). Default 4.
 *   per_page int    Products per page. Default 12.
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
		'columns'  => '4',
		'per_page' => '12',
	),
	$atts,
	'wzp_search_results'
);

$columns  = max( 2, min( 5, intval( $atts['columns'] ) ) );
$per_page = max( 4, min( 60, intval( $atts['per_page'] ) ) );

// ── Read search query ─────────────────────────────────────────────────────────
// phpcs:disable WordPress.Security.NonceVerification
$search_query = '';
if ( ! empty( $_GET['s'] ) ) {
	$search_query = sanitize_text_field( wp_unslash( $_GET['s'] ) );
} elseif ( ! empty( $_GET['wzp_s'] ) ) {
	$search_query = sanitize_text_field( wp_unslash( $_GET['wzp_s'] ) );
} elseif ( function_exists( 'get_search_query' ) && get_search_query() ) {
	$search_query = sanitize_text_field( get_search_query() );
}

$url_sort     = isset( $_GET['wzp_sort'] ) ? sanitize_key( $_GET['wzp_sort'] ) : 'date';
$current_page = isset( $_GET['wzp_page'] ) ? max( 1, intval( $_GET['wzp_page'] ) ) : 1;
// phpcs:enable

// ── Sort options ──────────────────────────────────────────────────────────────
$sort_options = array(
	'date'       => __( 'Newest',       'woo-zee-plugin' ),
	'popularity' => __( 'Most Popular', 'woo-zee-plugin' ),
	'rating'     => __( 'Top Rated',    'woo-zee-plugin' ),
	'price'      => __( 'Price: Low → High', 'woo-zee-plugin' ),
	'price-desc' => __( 'Price: High → Low', 'woo-zee-plugin' ),
	'title'      => __( 'A → Z',        'woo-zee-plugin' ),
);

// ── Run query ─────────────────────────────────────────────────────────────────
$query = new WP_Query( wzp_build_shop_query_args( array(
	'per_page' => $per_page,
	'page'     => $current_page,
	'orderby'  => $url_sort,
	'search'   => $search_query,
) ) );

$total_found = $query->found_posts;
$total_pages = $query->max_num_pages;
$from        = ( ( $current_page - 1 ) * $per_page ) + 1;
$to          = min( $current_page * $per_page, $total_found );

// ── Current page base URL for sort/pagination ─────────────────────────────────
$base_url = strtok( ( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ), '?' );

?>
<div class="wzp-search-results">

	<?php /* ── Header ── */ ?>
	<div class="wzp-search-results__header">
		<h1 class="wzp-search-results__title">
			<?php if ( $search_query ) : ?>
				<?php
				printf(
					/* translators: %s = search term */
					esc_html__( 'Search results for "%s"', 'woo-zee-plugin' ),
					'<em>' . esc_html( $search_query ) . '</em>'
				);
				?>
			<?php else : ?>
				<?php esc_html_e( 'All Products', 'woo-zee-plugin' ); ?>
			<?php endif; ?>
		</h1>
	</div>

	<?php /* ── Top bar ── */ ?>
	<div class="wzp-shop__topbar wzp-search-results__topbar">

		<p class="wzp-shop__results-count" aria-live="polite">
			<?php if ( $total_found > 0 ) : ?>
				<?php
				printf(
					/* translators: 1: from, 2: to, 3: total */
					esc_html__( 'Showing %1$s–%2$s of %3$s products', 'woo-zee-plugin' ),
					'<strong>' . esc_html( $from ) . '</strong>',
					'<strong>' . esc_html( $to ) . '</strong>',
					'<strong>' . esc_html( $total_found ) . '</strong>'
				);
				?>
			<?php elseif ( $search_query ) : ?>
				<?php esc_html_e( 'No products found', 'woo-zee-plugin' ); ?>
			<?php endif; ?>
		</p>

		<?php if ( $total_found > 0 ) : ?>
		<div class="wzp-shop__topbar-right">
			<label class="screen-reader-text" for="wzp-search-sort"><?php esc_html_e( 'Sort by', 'woo-zee-plugin' ); ?></label>
			<select id="wzp-search-sort" class="wzp-shop__sort wzp-search-results__sort" aria-label="<?php esc_attr_e( 'Sort products', 'woo-zee-plugin' ); ?>">
				<?php foreach ( $sort_options as $val => $label ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $url_sort, $val ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php endif; ?>

	</div>

	<?php /* ── Product grid ── */ ?>
	<?php if ( $query->have_posts() ) : ?>

	<div class="wzp-shop__grid wzp-shop__grid--cols-<?php echo esc_attr( $columns ); ?>"
	     data-wzp-search-grid>
		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
			<?php echo wzp_render_product_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endwhile; ?>
		<?php wp_reset_postdata(); ?>
	</div>

	<?php /* ── Pagination ── */ ?>
	<?php if ( $total_pages > 1 ) : ?>
		<div class="wzp-search-results__pagination">
			<?php echo wzp_shop_pagination( $current_page, $total_pages ); // phpcs:ignore ?>
		</div>
	<?php endif; ?>

	<?php else : /* No results */ ?>
		<?php wp_reset_postdata(); ?>

		<div class="wzp-search-results__empty">
			<svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<circle cx="28" cy="28" r="20" stroke="#d0cbc5" stroke-width="2.5"/>
				<line x1="43" y1="43" x2="57" y2="57" stroke="#d0cbc5" stroke-width="2.5" stroke-linecap="round"/>
				<line x1="20" y1="28" x2="36" y2="28" stroke="#d0cbc5" stroke-width="2" stroke-linecap="round"/>
			</svg>
			<p class="wzp-search-results__empty-title">
				<?php
				printf(
					esc_html__( 'No results for "%s"', 'woo-zee-plugin' ),
					'<strong>' . esc_html( $search_query ) . '</strong>'
				);
				?>
			</p>
			<p class="wzp-search-results__empty-sub">
				<?php esc_html_e( 'Try different keywords or browse our collections.', 'woo-zee-plugin' ); ?>
			</p>
			<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>"
			   class="wzp-search-results__empty-btn">
				<?php esc_html_e( 'Browse All Products', 'woo-zee-plugin' ); ?>
			</a>
		</div>

	<?php endif; ?>

</div>

<?php
// ── Inline JS — sort and pagination via URL params (no AJAX needed) ───────────
?>
<script>
(function(){
	var sort = document.getElementById('wzp-search-sort');
	if(sort){
		sort.addEventListener('change', function(){
			var url = new URL(window.location.href);
			url.searchParams.set('wzp_sort', this.value);
			url.searchParams.delete('wzp_page');
			window.location.href = url.toString();
		});
	}

	// Pagination buttons (reuses .wzp-shop__page-btn from shop CSS)
	document.addEventListener('click', function(e){
		var btn = e.target.closest('[data-wzp-search-grid] ~ .wzp-search-results__pagination .wzp-shop__page-btn, .wzp-search-results__pagination .wzp-shop__page-btn');
		if(!btn) return;
		var page = btn.dataset.page;
		if(!page) return;
		var url = new URL(window.location.href);
		url.searchParams.set('wzp_page', page);
		window.location.href = url.toString();
	});
})();
</script>
