<?php
/**
 * [wzp_new_arrivals] — New Arrivals product page shortcode.
 *
 * Attributes:
 *   columns   int     Grid columns (2–5). Default 4.
 *   per_page  int     Products per page. Default 12.
 *   title     string  Section heading. Default "New Arrivals".
 *
 * Tab filters (built-in): 7 days / 30 days / 90 days / All Time
 * URL param: wzp_na_days (7|30|90|0)  wzp_na_page
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) ) { return; }

require_once WZP_PATH . 'modules/product-grid/card.php';

// ── Shortcode atts ─────────────────────────────────────────────────────────────
$atts = shortcode_atts(
	array(
		'columns'  => '4',
		'per_page' => '12',
		'title'    => __( 'New Arrivals', 'woo-zee-plugin' ),
	),
	$atts,
	'wzp_new_arrivals'
);

$columns  = max( 2, min( 5, intval( $atts['columns'] ) ) );
$per_page = max( 4, min( 60, intval( $atts['per_page'] ) ) );
$title    = sanitize_text_field( $atts['title'] );

// ── Tabs ───────────────────────────────────────────────────────────────────────
$tabs = array(
	7  => __( 'This Week',  'woo-zee-plugin' ),
	30 => __( 'This Month', 'woo-zee-plugin' ),
	90 => __( 'Last 3 Months', 'woo-zee-plugin' ),
	0  => __( 'All Time',   'woo-zee-plugin' ),
);

// phpcs:disable WordPress.Security.NonceVerification
$active_days  = isset( $_GET['wzp_na_days'] ) ? intval( $_GET['wzp_na_days'] ) : 30;
$current_page = isset( $_GET['wzp_na_page'] ) ? max( 1, intval( $_GET['wzp_na_page'] ) ) : 1;
// phpcs:enable

if ( ! array_key_exists( $active_days, $tabs ) ) {
	$active_days = 30;
}

// ── Query ──────────────────────────────────────────────────────────────────────
$query_args = array(
	'post_type'           => 'product',
	'post_status'         => 'publish',
	'posts_per_page'      => $per_page,
	'paged'               => $current_page,
	'orderby'             => 'date',
	'order'               => 'DESC',
	'ignore_sticky_posts' => true,
);

if ( $active_days > 0 ) {
	$query_args['date_query'] = array(
		array(
			'after'     => $active_days . ' days ago',
			'inclusive' => true,
		),
	);
}

$query       = new WP_Query( $query_args );
$total_found = $query->found_posts;
$total_pages = $query->max_num_pages;

// ── JSON-LD structured data ────────────────────────────────────────────────────
$ld = array(
	'@context'        => 'https://schema.org',
	'@type'           => 'ItemList',
	'name'            => $title,
	'numberOfItems'   => $total_found,
	'itemListElement' => array(),
);

$position = ( ( $current_page - 1 ) * $per_page ) + 1;
foreach ( $query->posts as $post ) {
	$p = wc_get_product( $post->ID );
	if ( ! $p instanceof WC_Product ) { continue; }
	$img_id  = $p->get_image_id();
	$ld['itemListElement'][] = array(
		'@type'    => 'ListItem',
		'position' => $position++,
		'item'     => array(
			'@type'  => 'Product',
			'name'   => $p->get_name(),
			'url'    => get_permalink( $p->get_id() ),
			'image'  => $img_id ? wp_get_attachment_image_url( $img_id, 'medium' ) : '',
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
<script type="application/ld+json"><?php echo wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>

<section class="wzp-module wzp-na"
         data-wzp-module="new-arrivals"
         data-columns="<?php echo esc_attr( $columns ); ?>"
         data-per-page="<?php echo esc_attr( $per_page ); ?>"
         data-nonce="<?php echo esc_attr( wp_create_nonce( 'wzp_na_nonce' ) ); ?>">

	<?php /* ── Header ── */ ?>
	<div class="wzp-na__header">
		<?php if ( $title ) : ?>
		<h2 class="wzp-na__title"><?php echo esc_html( $title ); ?></h2>
		<?php endif; ?>

		<?php /* ── Tab filters ── */ ?>
		<nav class="wzp-na__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Filter by date', 'woo-zee-plugin' ); ?>">
			<?php foreach ( $tabs as $days => $label ) : ?>
			<button class="wzp-na__tab<?php echo $days === $active_days ? ' wzp-na__tab--active' : ''; ?>"
			        role="tab"
			        data-days="<?php echo esc_attr( $days ); ?>"
			        aria-selected="<?php echo $days === $active_days ? 'true' : 'false'; ?>">
				<?php echo esc_html( $label ); ?>
			</button>
			<?php endforeach; ?>
		</nav>
	</div>

	<?php /* ── Results bar ── */ ?>
	<div class="wzp-na__bar">
		<p class="wzp-na__count" aria-live="polite">
			<?php if ( $total_found > 0 ) :
				printf(
					esc_html( _n( '%s product', '%s products', $total_found, 'woo-zee-plugin' ) ),
					'<strong>' . number_format_i18n( $total_found ) . '</strong>'
				);
			else :
				esc_html_e( 'No products found', 'woo-zee-plugin' );
			endif; ?>
		</p>
	</div>

	<?php /* ── Loading overlay ── */ ?>
	<div class="wzp-na__loading-overlay" aria-hidden="true">
		<div class="wzp-na__spinner"></div>
	</div>

	<?php /* ── Grid ── */ ?>
	<div class="wzp-na__grid wzp-na__grid--cols-<?php echo esc_attr( $columns ); ?>"
	     id="wzp-na-grid"
	     aria-live="polite"
	     aria-busy="false">

		<?php if ( $query->have_posts() ) :
			while ( $query->have_posts() ) :
				$query->the_post();
				echo wzp_render_product_card( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput
			endwhile;
			wp_reset_postdata();
		else : ?>
		<div class="wzp-na__empty">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="wzp-na__empty-icon">
				<circle cx="32" cy="32" r="28"/>
				<path d="M32 20v12l8 4"/>
			</svg>
			<p><?php esc_html_e( 'No new arrivals in this period.', 'woo-zee-plugin' ); ?></p>
		</div>
		<?php endif; ?>

	</div>

	<?php /* ── Pagination ── */ ?>
	<?php if ( $total_pages > 1 ) : ?>
	<div id="wzp-na-pagination" class="wzp-na__pagination">
		<?php
		require_once WZP_PATH . 'modules/shop/query.php';
		echo wzp_shop_pagination( $current_page, $total_pages ); // phpcs:ignore WordPress.Security.EscapeOutput
		?>
	</div>
	<?php endif; ?>

</section>
