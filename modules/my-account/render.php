<?php
/**
 * [wzp_my_account] — Custom My Account page.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'WC' ) ) { return; }

// ── Determine active endpoint ──────────────────────────────────────────────────
$current_endpoint = WC()->query->get_current_endpoint();
if ( ! $current_endpoint ) {
	$current_endpoint = 'dashboard';
}

// ── Nav items ──────────────────────────────────────────────────────────────────
$nav_items = array(
	'dashboard'       => array(
		'label' => __( 'Dashboard', 'woo-zee-plugin' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
	),
	'orders'          => array(
		'label' => __( 'Orders', 'woo-zee-plugin' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
	),
	'downloads'       => array(
		'label' => __( 'Downloads', 'woo-zee-plugin' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
	),
	'edit-address'    => array(
		'label' => __( 'Addresses', 'woo-zee-plugin' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
	),
	'edit-account'    => array(
		'label' => __( 'Account Details', 'woo-zee-plugin' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
	),
	'customer-logout' => array(
		'label' => __( 'Logout', 'woo-zee-plugin' ),
		'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
	),
);

if ( ! get_option( 'woocommerce_enable_myaccount_downloads' ) ) {
	unset( $nav_items['downloads'] );
}

// ── User info ──────────────────────────────────────────────────────────────────
$is_logged_in = is_user_logged_in();
$user         = $is_logged_in ? wp_get_current_user() : null;
$user_name    = $is_logged_in ? ( $user->first_name ?: $user->display_name ) : '';
$user_email   = $is_logged_in ? $user->user_email : '';
$avatar_url   = $is_logged_in ? get_avatar_url( $user->ID, array( 'size' => 80 ) ) : '';
$member_since = $is_logged_in ? date_i18n( 'M Y', strtotime( $user->user_registered ) ) : '';

// ── Page title map ─────────────────────────────────────────────────────────────
$endpoint_titles = array(
	'dashboard'    => __( 'My Dashboard', 'woo-zee-plugin' ),
	'orders'       => __( 'My Orders', 'woo-zee-plugin' ),
	'view-order'   => __( 'Order Details', 'woo-zee-plugin' ),
	'downloads'    => __( 'Downloads', 'woo-zee-plugin' ),
	'edit-address' => __( 'My Addresses', 'woo-zee-plugin' ),
	'edit-account' => __( 'Account Details', 'woo-zee-plugin' ),
);
$page_title = $endpoint_titles[ $current_endpoint ] ?? __( 'My Account', 'woo-zee-plugin' );
?>

<div class="wzp-account wzp-module" data-wzp-module="my-account">

<?php if ( $is_logged_in ) : ?>

	<!-- ══ Logged-in layout ══ -->
	<div class="wzp-account__layout">

		<!-- Sidebar -->
		<aside class="wzp-account__sidebar">

			<div class="wzp-account__user">
				<?php if ( $avatar_url ) : ?>
				<div class="wzp-account__avatar-wrap">
					<img src="<?php echo esc_url( $avatar_url ); ?>"
					     alt="<?php echo esc_attr( $user_name ); ?>"
					     class="wzp-account__avatar"
					     width="52" height="52">
					<span class="wzp-account__avatar-ring" aria-hidden="true"></span>
				</div>
				<?php endif; ?>
				<div class="wzp-account__user-info">
					<p class="wzp-account__greeting"><?php esc_html_e( 'Hello,', 'woo-zee-plugin' ); ?></p>
					<p class="wzp-account__name"><?php echo esc_html( $user_name ); ?></p>
					<p class="wzp-account__since">
						<?php printf( esc_html__( 'Member since %s', 'woo-zee-plugin' ), esc_html( $member_since ) ); ?>
					</p>
				</div>
			</div>

			<nav class="wzp-account__nav" aria-label="<?php esc_attr_e( 'Account navigation', 'woo-zee-plugin' ); ?>">
				<?php foreach ( $nav_items as $endpoint => $item ) :
					$is_logout = 'customer-logout' === $endpoint;
					$is_active = $current_endpoint === $endpoint || ( 'dashboard' === $endpoint && '' === $current_endpoint );
					$nav_url   = $is_logout
						? wc_logout_url()
						: wc_get_endpoint_url( $endpoint, '', wc_get_page_permalink( 'myaccount' ) );
				?>
				<a href="<?php echo esc_url( $nav_url ); ?>"
				   class="wzp-account__nav-item<?php echo $is_active ? ' wzp-account__nav-item--active' : ''; ?><?php echo $is_logout ? ' wzp-account__nav-item--logout' : ''; ?>"
				   <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
					<span class="wzp-account__nav-icon" aria-hidden="true"><?php echo $item['icon']; // phpcs:ignore ?></span>
					<span class="wzp-account__nav-label"><?php echo esc_html( $item['label'] ); ?></span>
					<svg class="wzp-account__nav-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
				</a>
				<?php endforeach; ?>
			</nav>

		</aside>

		<!-- Main -->
		<main class="wzp-account__main">
			<div class="wzp-account__topbar">
				<h1 class="wzp-account__title"><?php echo esc_html( $page_title ); ?></h1>
				<?php if ( 'orders' === $current_endpoint ) : ?>
				<span class="wzp-account__subtitle"><?php esc_html_e( 'Track and manage your purchases', 'woo-zee-plugin' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="wzp-account__content">
				<?php do_action( 'woocommerce_account_content' ); ?>
			</div>
		</main>

	</div>

<?php else : ?>

	<!-- ══ Logged-out: Login + Register side by side ══ -->
	<div class="wzp-account__auth">

		<!-- Brand mark -->
		<div class="wzp-account__auth-brand">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
		</div>

		<h1 class="wzp-account__auth-title"><?php esc_html_e( 'Welcome Back', 'woo-zee-plugin' ); ?></h1>
		<p class="wzp-account__auth-sub"><?php esc_html_e( 'Sign in to your account or create a new one.', 'woo-zee-plugin' ); ?></p>

		<div class="wzp-account__auth-cols">

			<!-- Login -->
			<div class="wzp-account__auth-col">
				<div class="wzp-account__auth-card">
					<h2 class="wzp-account__auth-card-title">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
						<?php esc_html_e( 'Sign In', 'woo-zee-plugin' ); ?>
					</h2>
					<?php woocommerce_login_form( array( 'redirect' => wc_get_page_permalink( 'myaccount' ) ) ); ?>
				</div>
			</div>

			<!-- Register -->
			<?php if ( get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes' ) : ?>
			<div class="wzp-account__auth-col">
				<div class="wzp-account__auth-card wzp-account__auth-card--register">
					<h2 class="wzp-account__auth-card-title">
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
						<?php esc_html_e( 'Create Account', 'woo-zee-plugin' ); ?>
					</h2>
					<?php // woocommerce_registration_form() removed in WC 3.x — render form directly. ?>
					<form method="post" class="woocommerce-form woocommerce-form-register register">
						<?php do_action( 'woocommerce_register_form_start' ); ?>

						<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?></label>
							<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo esc_attr( isset( $_POST['username'] ) ? wp_unslash( $_POST['username'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>">
						</p>
						<?php endif; ?>

						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?></label>
							<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo esc_attr( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>">
						</p>

						<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?></label>
							<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password">
						</p>
						<?php endif; ?>

						<?php do_action( 'woocommerce_register_form' ); ?>

						<p class="woocommerce-form-row form-row">
							<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
							<button type="submit" class="woocommerce-Button button" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>">
								<?php esc_html_e( 'Register', 'woocommerce' ); ?>
							</button>
						</p>

						<?php do_action( 'woocommerce_register_form_end' ); ?>
					</form>
				</div>
			</div>
			<?php else : ?>
			<!-- Register disabled — show benefits panel -->
			<div class="wzp-account__auth-col">
				<div class="wzp-account__auth-benefits">
					<h2 class="wzp-account__auth-benefits-title"><?php esc_html_e( 'Why Create an Account?', 'woo-zee-plugin' ); ?></h2>
					<ul class="wzp-account__auth-benefits-list">
						<?php
						$benefits = array(
							__( 'Track your orders in real time', 'woo-zee-plugin' ),
							__( 'Save your shipping addresses', 'woo-zee-plugin' ),
							__( 'Manage your wishlist', 'woo-zee-plugin' ),
							__( 'Get exclusive member offers', 'woo-zee-plugin' ),
						);
						foreach ( $benefits as $b ) : ?>
						<li>
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
							<?php echo esc_html( $b ); ?>
						</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
			<?php endif; ?>

		</div><!-- /.wzp-account__auth-cols -->

	</div><!-- /.wzp-account__auth -->

<?php endif; ?>

</div><!-- /.wzp-account -->
