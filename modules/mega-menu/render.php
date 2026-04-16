<?php
/**
 * [wzp_mega_menu] — Render function.
 *
 * Shortcode: [wzp_mega_menu menu_id="nav_abc123"]
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wzp_render_mega_menu' ) ) :

function wzp_render_mega_menu( $atts ) {

	$atts    = shortcode_atts( array( 'menu_id' => '' ), $atts, 'wzp_mega_menu' );
	$menu_id = sanitize_key( $atts['menu_id'] );

	if ( ! $menu_id ) { return ''; }

	$menus = (array) get_option( 'wzp_saved_menus', array() );
	$menu  = null;
	foreach ( $menus as $m ) {
		if ( isset( $m['id'] ) && $m['id'] === $menu_id ) {
			$menu = $m;
			break;
		}
	}
	if ( ! $menu ) { return ''; }

	$items = is_array( $menu['items'] ) ? $menu['items'] : array();
	$uid   = esc_attr( $menu_id );

	ob_start();
	?>
	<nav class="wzp-mega-nav" data-wzp-module="mega-menu" data-menu-id="<?php echo $uid; ?>">

		<div class="wzp-mega-nav__bar">

			<ul class="wzp-mega-nav__list" role="menubar">
				<?php foreach ( $items as $item ) :
					$has_dd  = ! empty( $item['has_dropdown'] );
					$cols    = ( $has_dd && is_array( $item['columns'] ) ) ? $item['columns'] : array();
					$text    = esc_html( $item['text'] ?? '' );
					$url     = esc_url( $item['url'] ?? '#' );
					$target  = ( isset( $item['target'] ) && $item['target'] === '_blank' ) ? ' target="_blank" rel="noopener"' : '';
				?>
				<li class="wzp-mega-nav__item<?php echo $has_dd ? ' wzp-mega-nav__item--has-dropdown' : ''; ?>"
				    role="none">

					<a class="wzp-mega-nav__link"
					   href="<?php echo $url; ?>"
					   <?php echo $target; ?>
					   role="menuitem"
					   <?php echo $has_dd ? 'aria-haspopup="true" aria-expanded="false"' : ''; ?>>
						<?php echo $text; ?>
						<?php if ( $has_dd ) : ?>
						<svg class="wzp-nav-chevron" viewBox="0 0 10 6" fill="none" aria-hidden="true">
							<path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
						<?php endif; ?>
					</a>

					<?php if ( $has_dd && ! empty( $cols ) ) : ?>
					<div class="wzp-mega-dropdown" role="menu" hidden>
						<div class="wzp-mega-dropdown__inner">
							<?php foreach ( $cols as $col ) :
								$type  = $col['type'] ?? 'links';
								$title = esc_html( $col['title'] ?? '' );
							?>
							<div class="wzp-mega-dropdown__col<?php echo $type === 'featured' ? ' wzp-mega-dropdown__col--featured' : ''; ?>">

								<?php if ( $title ) : ?>
								<span class="wzp-mega-dropdown__col-title"><?php echo $title; ?></span>
								<?php endif; ?>

								<?php if ( $type === 'links' ) : ?>
								<ul class="wzp-mega-dropdown__links" role="none">
									<?php foreach ( (array) ( $col['links'] ?? array() ) as $link ) :
										if ( empty( $link['text'] ) ) { continue; }
									?>
									<li role="none">
										<a href="<?php echo esc_url( $link['url'] ?? '#' ); ?>" role="menuitem">
											<?php echo esc_html( $link['text'] ); ?>
										</a>
									</li>
									<?php endforeach; ?>
								</ul>

								<?php elseif ( $type === 'featured' ) :
									$img_url = ! empty( $col['image_id'] )
										? wp_get_attachment_image_url( absint( $col['image_id'] ), 'medium' )
										: ( $col['image_url'] ?? '' );
								?>
								<a href="<?php echo esc_url( $col['prod_url'] ?? '#' ); ?>"
								   class="wzp-mega-dropdown__featured" role="menuitem">
									<?php if ( $img_url ) : ?>
									<div class="wzp-mega-dropdown__featured-img">
										<img src="<?php echo esc_url( $img_url ); ?>"
										     alt="<?php echo esc_attr( $col['prod_name'] ?? '' ); ?>"
										     loading="lazy">
									</div>
									<?php endif; ?>
									<div class="wzp-mega-dropdown__featured-info">
										<span class="wzp-mega-dropdown__prod-name">
											<?php echo esc_html( $col['prod_name'] ?? '' ); ?>
										</span>
										<span class="wzp-mega-dropdown__prod-price">
											<?php echo esc_html( $col['prod_price'] ?? '' ); ?>
										</span>
									</div>
								</a>
								<?php endif; ?>

							</div>
							<?php endforeach; ?>
						</div>
					</div>
					<?php endif; ?>

				</li>
				<?php endforeach; ?>
			</ul>

			<button class="wzp-mega-nav__hamburger"
			        aria-label="<?php esc_attr_e( 'Open menu', 'woo-zee-plugin' ); ?>"
			        aria-expanded="false"
			        aria-controls="wzp-mobile-<?php echo $uid; ?>">
				<span class="wzp-hamburger-bar"></span>
				<span class="wzp-hamburger-bar"></span>
				<span class="wzp-hamburger-bar"></span>
			</button>

		</div><!-- /.wzp-mega-nav__bar -->

		<!-- Mobile Panel -->
		<div class="wzp-mega-nav__mobile"
		     id="wzp-mobile-<?php echo $uid; ?>"
		     hidden>
			<ul class="wzp-mobile-nav__list">
				<?php foreach ( $items as $item ) :
					$has_dd = ! empty( $item['has_dropdown'] );
					$cols   = ( $has_dd && is_array( $item['columns'] ) ) ? $item['columns'] : array();
				?>
				<li class="wzp-mobile-nav__item<?php echo $has_dd ? ' wzp-mobile-nav__item--has-children' : ''; ?>">

					<?php if ( $has_dd ) : ?>
					<button class="wzp-mobile-nav__toggle" aria-expanded="false">
						<span><?php echo esc_html( $item['text'] ?? '' ); ?></span>
						<svg class="wzp-nav-chevron" viewBox="0 0 10 6" fill="none" aria-hidden="true">
							<path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
						</svg>
					</button>
					<div class="wzp-mobile-nav__sub" hidden>
						<?php foreach ( $cols as $col ) :
							if ( ( $col['type'] ?? 'links' ) !== 'links' ) { continue; }
						?>
						<div class="wzp-mobile-nav__col">
							<?php if ( ! empty( $col['title'] ) ) : ?>
							<span class="wzp-mobile-nav__col-title"><?php echo esc_html( $col['title'] ); ?></span>
							<?php endif; ?>
							<ul>
								<?php foreach ( (array) ( $col['links'] ?? array() ) as $link ) :
									if ( empty( $link['text'] ) ) { continue; }
								?>
								<li>
									<a href="<?php echo esc_url( $link['url'] ?? '#' ); ?>">
										<?php echo esc_html( $link['text'] ); ?>
									</a>
								</li>
								<?php endforeach; ?>
							</ul>
						</div>
						<?php endforeach; ?>
					</div>

					<?php else : ?>
					<a class="wzp-mobile-nav__link"
					   href="<?php echo esc_url( $item['url'] ?? '#' ); ?>"
					   <?php echo ( isset( $item['target'] ) && $item['target'] === '_blank' ) ? 'target="_blank" rel="noopener"' : ''; ?>>
						<?php echo esc_html( $item['text'] ?? '' ); ?>
					</a>
					<?php endif; ?>

				</li>
				<?php endforeach; ?>
			</ul>
		</div><!-- /.wzp-mega-nav__mobile -->

	</nav>
	<?php
	return ob_get_clean();
}

endif;

echo wzp_render_mega_menu( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
