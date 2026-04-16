<?php
/**
 * Shop query builder — shared between the shortcode render and AJAX handler.
 *
 * Call wzp_build_shop_query_args( $filters ) to get a WP_Query args array.
 * All inputs are sanitised here; callers must not trust raw $_GET / $_POST.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wzp_build_shop_query_args' ) ) :

/**
 * Build WP_Query args from a sanitised $filters array.
 *
 * @param array $filters {
 *   per_page  int      Products per page.
 *   page      int      Current page number.
 *   orderby   string   date|price|price-desc|popularity|rating|title.
 *   cats      string[] Category slugs.
 *   min_price float    Minimum price (0 = no lower bound).
 *   max_price float    Maximum price (0 = no upper bound).
 *   on_sale   bool     Restrict to sale products.
 *   search    string   Keyword search.
 * }
 * @return array WP_Query args.
 */
function wzp_build_shop_query_args( $filters = array() ) {

	$per_page  = max( 1, min( 100, intval( $filters['per_page'] ?? 12 ) ) );
	$page      = max( 1, intval( $filters['page'] ?? 1 ) );
	$orderby   = sanitize_key( $filters['orderby'] ?? 'date' );
	$cats      = array_values( array_filter( array_map( 'sanitize_title', (array) ( $filters['cats'] ?? array() ) ) ) );
	$min_price = max( 0, floatval( $filters['min_price'] ?? 0 ) );
	$max_price = max( 0, floatval( $filters['max_price'] ?? 0 ) );
	$on_sale   = ! empty( $filters['on_sale'] );
	$search    = sanitize_text_field( $filters['search'] ?? '' );

	// ── Base args ─────────────────────────────────────────────────────────────
	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => $per_page,
		'paged'               => $page,
		'ignore_sticky_posts' => true,
	);

	// ── Ordering ──────────────────────────────────────────────────────────────
	switch ( $orderby ) {
		case 'price':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_price';
			$args['order']    = 'ASC';
			break;
		case 'price-desc':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_price';
			$args['order']    = 'DESC';
			break;
		case 'popularity':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = 'total_sales';
			$args['order']    = 'DESC';
			break;
		case 'rating':
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_wc_average_rating';
			$args['order']    = 'DESC';
			break;
		case 'title':
			$args['orderby'] = 'title';
			$args['order']   = 'ASC';
			break;
		case 'date':
		default:
			$args['orderby'] = 'date';
			$args['order']   = 'DESC';
			break;
	}

	// ── Category filter ───────────────────────────────────────────────────────
	if ( ! empty( $cats ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $cats,
				'operator' => 'IN',
			),
		);
	}

	// ── Price range ───────────────────────────────────────────────────────────
	$meta_query = array();

	if ( $min_price > 0 && $max_price > 0 && $max_price >= $min_price ) {
		$meta_query[] = array(
			'key'     => '_price',
			'value'   => array( $min_price, $max_price ),
			'compare' => 'BETWEEN',
			'type'    => 'NUMERIC',
		);
	} elseif ( $min_price > 0 ) {
		$meta_query[] = array(
			'key'     => '_price',
			'value'   => $min_price,
			'compare' => '>=',
			'type'    => 'NUMERIC',
		);
	} elseif ( $max_price > 0 ) {
		$meta_query[] = array(
			'key'     => '_price',
			'value'   => $max_price,
			'compare' => '<=',
			'type'    => 'NUMERIC',
		);
	}

	if ( ! empty( $meta_query ) ) {
		$args['meta_query'] = $meta_query;
	}

	// ── On sale ───────────────────────────────────────────────────────────────
	if ( $on_sale ) {
		$sale_ids         = wc_get_product_ids_on_sale();
		$args['post__in'] = ! empty( $sale_ids ) ? array_map( 'absint', $sale_ids ) : array( 0 );
	}

	// ── Keyword search ────────────────────────────────────────────────────────
	if ( $search !== '' ) {
		$args['s'] = $search;
	}

	return $args;
}

endif;

// ── Helper: get global price bounds across ALL published products ─────────────
if ( ! function_exists( 'wzp_shop_price_bounds' ) ) :
function wzp_shop_price_bounds() {
	global $wpdb;
	$row = $wpdb->get_row(
		"SELECT MIN(CAST(meta_value AS DECIMAL(10,2))) AS min_price,
		        MAX(CAST(meta_value AS DECIMAL(10,2))) AS max_price
		 FROM   {$wpdb->postmeta}
		 JOIN   {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
		 WHERE  meta_key    = '_price'
		 AND    meta_value  != ''
		 AND    post_status = 'publish'
		 AND    post_type   = 'product'"
	);
	return array(
		'min' => $row ? floatval( $row->min_price ) : 0,
		'max' => $row ? floatval( $row->max_price ) : 10000,
	);
}
endif;

// ── Helper: render numbered pagination HTML ───────────────────────────────────
if ( ! function_exists( 'wzp_shop_pagination' ) ) :
function wzp_shop_pagination( $current_page, $total_pages ) {
	if ( $total_pages <= 1 ) { return ''; }

	ob_start();
	echo '<nav class="wzp-shop__pagination" aria-label="' . esc_attr__( 'Shop pagination', 'woo-zee-plugin' ) . '">';

	// Prev.
	if ( $current_page > 1 ) {
		printf(
			'<button class="wzp-shop__page-btn wzp-shop__page-btn--prev" data-page="%d" aria-label="%s"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg></button>',
			$current_page - 1,
			esc_attr__( 'Previous page', 'woo-zee-plugin' )
		);
	}

	// Numbered buttons — show up to 7 with ellipsis.
	$range   = 2;
	$shown   = array();
	$pages   = array_unique( array_filter( array_merge(
		array( 1, 2 ),
		range( max( 1, $current_page - $range ), min( $total_pages, $current_page + $range ) ),
		array( $total_pages - 1, $total_pages )
	) ) );
	sort( $pages );

	$prev_p = 0;
	foreach ( $pages as $p ) {
		if ( $p < 1 || $p > $total_pages ) { continue; }
		if ( $prev_p && $p - $prev_p > 1 ) {
			echo '<span class="wzp-shop__page-ellipsis">&hellip;</span>';
		}
		printf(
			'<button class="wzp-shop__page-btn%s" data-page="%d" aria-label="%s" %s>%d</button>',
			$p === $current_page ? ' wzp-shop__page-btn--active' : '',
			$p,
			esc_attr( sprintf( __( 'Page %d', 'woo-zee-plugin' ), $p ) ),
			$p === $current_page ? 'aria-current="page"' : '',
			$p
		);
		$prev_p = $p;
	}

	// Next.
	if ( $current_page < $total_pages ) {
		printf(
			'<button class="wzp-shop__page-btn wzp-shop__page-btn--next" data-page="%d" aria-label="%s"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg></button>',
			$current_page + 1,
			esc_attr__( 'Next page', 'woo-zee-plugin' )
		);
	}

	echo '</nav>';
	return ob_get_clean();
}
endif;
