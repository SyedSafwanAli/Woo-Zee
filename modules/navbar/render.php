<?php
/**
 * Navbar Module — Render File
 *
 * Defines wzp_render_cart_drawer_items() which is shared with the AJAX
 * cart handlers in class-wzp-admin.php (loaded via include_once).
 *
 * Full navbar HTML is only output when NOT running an AJAX request.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Output cart drawer item list HTML.
 * Called on initial page load AND by AJAX cart handlers.
 */
if ( ! function_exists( 'wzp_render_cart_drawer_items' ) ) {
	function wzp_render_cart_drawer_items() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		$cart_items = WC()->cart->get_cart();

		if ( empty( $cart_items ) ) {
			?>
			<p class="wzp-cart-empty"><?php esc_html_e( 'Your bag is empty.', 'woo-zee-plugin' ); ?></p>
			<?php
			return;
		}

		foreach ( $cart_items as $cart_key => $item ) :
			$product = $item['data'];
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$img_id  = $product->get_image_id();
			$img_url = $img_id
				? wp_get_attachment_image_url( $img_id, 'thumbnail' )
				: wc_placeholder_img_src( 'thumbnail' );
			$name    = $product->get_name();
			$price   = $product->get_price_html();
			$qty     = (int) $item['quantity'];
			?>
			<div class="wzp-cart-item" data-cart-key="<?php echo esc_attr( $cart_key ); ?>">
				<div class="wzp-cart-item__img">
					<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $name ); ?>">
				</div>
				<div class="wzp-cart-item__info">
					<span class="wzp-cart-item__name"><?php echo esc_html( $name ); ?></span>
					<span class="wzp-cart-item__price"><?php echo wp_kses_post( $price ); ?></span>
					<div class="wzp-cart-item__qty">
						<button type="button" class="wzp-cart-qty-btn" data-action="minus"
						        aria-label="<?php esc_attr_e( 'Decrease quantity', 'woo-zee-plugin' ); ?>">−</button>
						<span class="wzp-cart-qty-val"><?php echo esc_html( $qty ); ?></span>
						<button type="button" class="wzp-cart-qty-btn" data-action="plus"
						        aria-label="<?php esc_attr_e( 'Increase quantity', 'woo-zee-plugin' ); ?>">+</button>
					</div>
				</div>
				<button type="button" class="wzp-cart-item__remove"
				        aria-label="<?php esc_attr_e( 'Remove item', 'woo-zee-plugin' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
				</button>
			</div>
			<?php
		endforeach;
	}
}

// ── Full navbar markup — only in non-AJAX requests ─────────────────────────

if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	return;
}

// ── Settings ──────────────────────────────────────────────────────────────────

$s = wp_parse_args(
	(array) get_option( 'wzp_navbar_settings', array() ),
	array(
		'logo_type'     => 'text',
		'logo_id'       => 0,
		'logo_text'     => get_bloginfo( 'name' ),
		'logo_url'      => '',
		'menu_id'       => '',
		'account_url'   => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ),
		'wishlist_url'  => '',
		'cart_url'      => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ),
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

// ── Logo ──────────────────────────────────────────────────────────────────────

$logo_html = '';
if ( 'image' === $s['logo_type'] && (int) $s['logo_id'] > 0 ) {
	$logo_src = $s['logo_url'] ?: wp_get_attachment_image_url( (int) $s['logo_id'], 'full' );
	if ( $logo_src ) {
		$logo_html = '<img class="wzp-nb__logo-img" src="' . esc_url( $logo_src ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '">';
	}
}
if ( ! $logo_html ) {
	$logo_html = '<span class="wzp-nb__logo-text">' . esc_html( $s['logo_text'] ?: get_bloginfo( 'name' ) ) . '</span>';
}

// ── Menu items ────────────────────────────────────────────────────────────────

// ── Mega dropdown product helper ──────────────────────────────────────────────

if ( ! function_exists( 'wzp_nb_mega_products' ) ) {
	function wzp_nb_mega_products( $item ) {
		if ( ! function_exists( 'WC' ) ) { return array(); }

		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => 3,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// If the menu item links to a product category, pull from that category.
		if ( 'product_cat' === ( $item['_object'] ?? '' ) && ! empty( $item['_object_id'] ) ) {
			$args['tax_query'] = array( array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => (int) $item['_object_id'],
			) );
		} else {
			// Fallback: featured products.
			$featured_ids = wc_get_featured_product_ids();
			if ( $featured_ids ) {
				$args['post__in'] = array_slice( $featured_ids, 0, 3 );
				$args['orderby']  = 'post__in';
			}
		}

		return get_posts( $args );
	}
}

// ── Menu items from WordPress native nav menu ─────────────────────────────────

$menu_items = array();
if ( $s['menu_id'] ) {
	$raw_items = wp_get_nav_menu_items( (int) $s['menu_id'] );
	if ( is_array( $raw_items ) && $raw_items ) {
		// Index all items, capturing object type for mega product queries.
		$indexed = array();
		foreach ( $raw_items as $item ) {
			$indexed[ $item->ID ] = array(
				'label'      => $item->title,
				'url'        => $item->url,
				'children'   => array(),
				'_parent'    => (int) $item->menu_item_parent,
				'_object'    => $item->object,
				'_object_id' => (int) $item->object_id,
			);
		}
		// Nest children.
		foreach ( $indexed as $id => &$node ) {
			if ( $node['_parent'] && isset( $indexed[ $node['_parent'] ] ) ) {
				$indexed[ $node['_parent'] ]['children'][] = &$node;
			}
		}
		unset( $node );
		// Collect top-level items.
		foreach ( $indexed as $id => $node ) {
			if ( ! $node['_parent'] ) {
				$menu_items[] = $node;
			}
		}
	}
}

// ── Cart count ────────────────────────────────────────────────────────────────

$cart_count = ( function_exists( 'WC' ) && WC()->cart )
	? WC()->cart->get_cart_contents_count()
	: 0;

$cart_subtotal = ( function_exists( 'WC' ) && WC()->cart )
	? WC()->cart->get_cart_subtotal()
	: '';

// ── CSS custom properties ─────────────────────────────────────────────────────

$css_vars = '--wzp-nb-bg:'     . esc_attr( $s['bg_color'] )     . ';'
          . '--wzp-nb-text:'   . esc_attr( $s['text_color'] )   . ';'
          . '--wzp-nb-hover:'  . esc_attr( $s['hover_color'] )  . ';'
          . '--wzp-nb-border:' . esc_attr( $s['border_color'] ) . ';'
          . '--wzp-nb-active:' . esc_attr( $s['active_color'] ) . ';';

?>
<header class="wzp-nb wzp-nb--sticky"
        style="<?php echo $css_vars; ?>"
        role="banner">
	<div class="wzp-nb__inner">

		<!-- Nav (left) -->
		<nav class="wzp-nb__nav" aria-label="<?php esc_attr_e( 'Main navigation', 'woo-zee-plugin' ); ?>">
			<ul class="wzp-nb__menu" role="list">
				<?php foreach ( $menu_items as $item ) :
					$label    = esc_html( $item['label'] ?? '' );
					$url      = esc_url( $item['url'] ?? '#' );
					$children = is_array( $item['children'] ?? null ) ? $item['children'] : array();
					if ( ! $label ) { continue; }
					$has_mega = ! empty( $children );
				?>
					<li class="wzp-nb__item<?php echo $has_mega ? ' wzp-nb__item--has-mega' : ''; ?>">
						<a class="wzp-nb__link"
						   href="<?php echo $url; ?>"
						   <?php echo $has_mega ? 'aria-haspopup="true" aria-expanded="false"' : ''; ?>>
							<?php echo $label; ?>
							<?php if ( $has_mega ) : ?>
								<svg class="wzp-nb__chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
							<?php endif; ?>
						</a>

						<?php if ( $has_mega ) :
							$mega_products = wzp_nb_mega_products( $item );
						?>
						<div class="wzp-nb__mega" hidden>
							<div class="wzp-nb__mega-inner">

								<!-- Sub-links column -->
								<div class="wzp-nb__mega-links">
									<p class="wzp-nb__mega-heading"><?php esc_html_e( 'Browse', 'woo-zee-plugin' ); ?></p>
									<ul class="wzp-nb__mega-list" role="list">
										<?php foreach ( $children as $child ) :
											$cl = esc_html( $child['label'] ?? '' );
											$cu = esc_url( $child['url'] ?? '#' );
											if ( ! $cl ) { continue; }
										?>
											<li>
												<a class="wzp-nb__mega-link" href="<?php echo $cu; ?>">
													<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
													<?php echo $cl; ?>
												</a>
											</li>
										<?php endforeach; ?>
									</ul>
									<a class="wzp-nb__mega-view-all" href="<?php echo $url; ?>">
										<?php esc_html_e( 'View All', 'woo-zee-plugin' ); ?>
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
									</a>
								</div>

								<!-- Featured products column -->
								<?php if ( $mega_products ) : ?>
								<div class="wzp-nb__mega-products">
									<p class="wzp-nb__mega-heading"><?php esc_html_e( 'Featured', 'woo-zee-plugin' ); ?></p>
									<div class="wzp-nb__mega-grid">
										<?php foreach ( $mega_products as $mp ) :
											$mp_obj   = wc_get_product( $mp->ID );
											if ( ! $mp_obj ) { continue; }
											$mp_img   = get_the_post_thumbnail_url( $mp->ID, 'large' ) ?: wc_placeholder_img_src( 'large' );
											$mp_name  = esc_html( $mp_obj->get_name() );
											$mp_price = $mp_obj->get_price_html();
											$mp_url   = esc_url( get_permalink( $mp->ID ) );
										?>
											<a class="wzp-nb__mega-card" href="<?php echo $mp_url; ?>">
												<div class="wzp-nb__mega-card__img">
													<img src="<?php echo esc_url( $mp_img ); ?>" alt="<?php echo $mp_name; ?>" loading="lazy">
												</div>
												<div class="wzp-nb__mega-card__body">
													<span class="wzp-nb__mega-card__name"><?php echo $mp_name; ?></span>
													<span class="wzp-nb__mega-card__price"><?php echo wp_kses_post( $mp_price ); ?></span>
												</div>
											</a>
										<?php endforeach; ?>
									</div>
								</div>
								<?php endif; ?>

							</div>
						</div>
						<?php endif; ?>

					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<!-- Brand (center) -->
		<a class="wzp-nb__brand"
		   href="<?php echo esc_url( home_url( '/' ) ); ?>"
		   aria-label="<?php esc_attr_e( 'Go to homepage', 'woo-zee-plugin' ); ?>">
			<?php echo $logo_html; ?>
		</a>

		<!-- Utils (right) -->
		<div class="wzp-nb__utils">

			<?php if ( '1' === $s['show_search'] ) : ?>
				<button type="button" class="wzp-nb__util wzp-nb__search-trigger"
				        aria-label="<?php esc_attr_e( 'Open search', 'woo-zee-plugin' ); ?>"
				        aria-expanded="false">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
				</button>
			<?php endif; ?>

			<?php if ( '1' === $s['show_account'] ) : ?>
				<a class="wzp-nb__util wzp-nb__account-icon"
				   href="<?php echo esc_url( $s['account_url'] ); ?>"
				   aria-label="<?php esc_attr_e( 'My account', 'woo-zee-plugin' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
				</a>
			<?php endif; ?>

			<?php if ( '1' === $s['show_wishlist'] ) : ?>
				<a class="wzp-nb__util wzp-nb__wishlist-icon"
				   href="<?php echo esc_url( $s['wishlist_url'] ?: '#' ); ?>"
				   aria-label="<?php esc_attr_e( 'Wishlist', 'woo-zee-plugin' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
					<span class="wzp-nb__badge wzp-nb__wishlist-count" aria-live="polite"></span>
				</a>
			<?php endif; ?>

			<?php if ( '1' === $s['show_cart'] ) : ?>
				<button type="button" class="wzp-nb__util wzp-nb__cart-trigger"
				        aria-label="<?php esc_attr_e( 'Open shopping bag', 'woo-zee-plugin' ); ?>"
				        aria-expanded="false">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
					<span class="wzp-nb__badge wzp-nb__cart-count" aria-live="polite"><?php echo esc_html( $cart_count ); ?></span>
				</button>
			<?php endif; ?>

			<!-- Hamburger (mobile only) -->
			<button type="button" class="wzp-nb__hamburger"
			        aria-label="<?php esc_attr_e( 'Toggle menu', 'woo-zee-plugin' ); ?>"
			        aria-expanded="false"
			        aria-controls="wzp-nb-mobile-panel">
				<span class="wzp-nb__bar"></span>
				<span class="wzp-nb__bar"></span>
				<span class="wzp-nb__bar"></span>
			</button>

		</div>

	</div>
</header>

<!-- ── Search Overlay ────────────────────────────────────────────────────────── -->
<div class="wzp-nb-search"
     role="dialog"
     aria-label="<?php esc_attr_e( 'Search', 'woo-zee-plugin' ); ?>"
     aria-modal="true"
     hidden>

	<!-- Close button -->
	<button type="button" class="wzp-nb-search__close"
	        aria-label="<?php esc_attr_e( 'Close search', 'woo-zee-plugin' ); ?>">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
	</button>

	<div class="wzp-nb-search__body">

		<!-- Pill search input -->
		<form role="search"
		      method="get"
		      action="<?php echo esc_url( home_url( '/' ) ); ?>"
		      class="wzp-nb-search__form">
			<div class="wzp-nb-search__pill">
				<input type="search"
				       name="s"
				       id="wzp-search-input"
				       class="wzp-nb-search__input"
				       placeholder="<?php esc_attr_e( 'Enter Your Keywords', 'woo-zee-plugin' ); ?>"
				       aria-label="<?php esc_attr_e( 'Search', 'woo-zee-plugin' ); ?>"
				       autocomplete="off">
				<button type="submit" class="wzp-nb-search__submit" aria-label="<?php esc_attr_e( 'Search', 'woo-zee-plugin' ); ?>">
					<span class="wzp-nb-search__spinner" aria-hidden="true"></span>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
				</button>
			</div>
			<input type="hidden" name="post_type" value="product">
		</form>

		<!-- Trending tags -->
		<div class="wzp-nb-search__trending">
			<span class="wzp-nb-search__trending-label"><?php esc_html_e( 'Search Trending :', 'woo-zee-plugin' ); ?></span>
			<div class="wzp-nb-search__trending-tags">
				<?php
				$trending = array( 'Gold', 'Silver', 'Anklets', 'Bracelets' );
				foreach ( $trending as $tag ) :
				?>
				<button type="button" class="wzp-nb-search__tag" data-term="<?php echo esc_attr( $tag ); ?>">
					<?php echo esc_html( $tag ); ?>
				</button>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- AJAX results -->
		<div class="wzp-nb-search__results" id="wzp-search-results" role="listbox" aria-live="polite" hidden></div>

	</div>
</div>

<!-- ── Cart Drawer ───────────────────────────────────────────────────────────── -->
<div class="wzp-cart-backdrop"></div>

<aside class="wzp-cart-drawer"
       role="dialog"
       aria-label="<?php esc_attr_e( 'Shopping bag', 'woo-zee-plugin' ); ?>"
       aria-modal="true">
	<div class="wzp-cart-drawer__header">
		<h2 class="wzp-cart-drawer__title">
			<?php esc_html_e( 'Your Bag', 'woo-zee-plugin' ); ?>
			<span class="wzp-cart-drawer__count">(<?php echo esc_html( $cart_count ); ?>)</span>
		</h2>
		<button type="button" class="wzp-cart-drawer__close"
		        aria-label="<?php esc_attr_e( 'Close cart', 'woo-zee-plugin' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
		</button>
	</div>

	<div class="wzp-cart-drawer__items" aria-live="polite">
		<?php wzp_render_cart_drawer_items(); ?>
	</div>

	<div class="wzp-cart-drawer__footer">
		<div class="wzp-cart-drawer__subtotal">
			<span><?php esc_html_e( 'Subtotal', 'woo-zee-plugin' ); ?></span>
			<span class="wzp-cart-drawer__subtotal-val"><?php echo wp_kses_post( $cart_subtotal ); ?></span>
		</div>
		<a href="<?php echo esc_url( $s['cart_url'] ?: ( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '#' ) ); ?>"
		   class="wzp-cart-drawer__view-cart">
			<?php esc_html_e( 'View Cart', 'woo-zee-plugin' ); ?>
		</a>
		<a href="<?php echo esc_url( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ) ); ?>"
		   class="wzp-cart-drawer__checkout">
			<?php esc_html_e( 'Checkout', 'woo-zee-plugin' ); ?>
		</a>
	</div>
</aside>

<!-- ── Mobile Panel ──────────────────────────────────────────────────────────── -->
<div class="wzp-nb-mobile" id="wzp-nb-mobile-panel" hidden>

	<!-- Header: logo + close -->
	<div class="wzp-nb-mobile__header">
		<a class="wzp-nb-mobile__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php echo $logo_html; ?>
		</a>
		<button type="button" class="wzp-nb-mobile__close"
		        aria-label="<?php esc_attr_e( 'Close menu', 'woo-zee-plugin' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
		</button>
	</div>

	<ul class="wzp-nb-mobile__menu" role="list">
		<?php foreach ( $menu_items as $item ) :
			$label    = esc_html( $item['label'] ?? '' );
			$url      = esc_url( $item['url'] ?? '#' );
			$children = is_array( $item['children'] ?? null ) ? $item['children'] : array();
			if ( ! $label ) { continue; }
		?>
			<li class="wzp-nb-mobile__item<?php echo $children ? ' wzp-nb-mobile__item--has-sub' : ''; ?>">
				<?php if ( $children ) : ?>
					<button type="button" class="wzp-nb-mobile__link wzp-nb-mobile__toggle"
					        aria-expanded="false">
						<?php echo $label; ?>
						<svg class="wzp-nb-mobile__chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
					</button>
					<ul class="wzp-nb-mobile__sub" hidden role="list">
						<?php foreach ( $children as $child ) :
							if ( empty( $child['label'] ) ) { continue; }
						?>
							<li>
								<a class="wzp-nb-mobile__sub-link"
								   href="<?php echo esc_url( $child['url'] ?? '#' ); ?>">
									<?php echo esc_html( $child['label'] ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<a class="wzp-nb-mobile__link" href="<?php echo $url; ?>"><?php echo $label; ?></a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<!-- Footer utils -->
	<div class="wzp-nb-mobile__footer">
		<?php if ( '1' === $s['show_account'] ) : ?>
		<a class="wzp-nb-mobile__util-link" href="<?php echo esc_url( $s['account_url'] ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
			<?php esc_html_e( 'My Account', 'woo-zee-plugin' ); ?>
		</a>
		<?php endif; ?>
		<?php if ( '1' === $s['show_wishlist'] && $s['wishlist_url'] ) : ?>
		<a class="wzp-nb-mobile__util-link" href="<?php echo esc_url( $s['wishlist_url'] ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
			<?php esc_html_e( 'Wishlist', 'woo-zee-plugin' ); ?>
		</a>
		<?php endif; ?>
		<?php if ( '1' === $s['show_cart'] ) : ?>
		<a class="wzp-nb-mobile__util-link" href="<?php echo esc_url( $s['cart_url'] ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
			<?php esc_html_e( 'Cart', 'woo-zee-plugin' ); ?>
		</a>
		<?php endif; ?>
	</div>

</div>
