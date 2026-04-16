<?php
/**
 * Newsletter signup form shortcode.
 *
 * Shortcode: [wzp_newsletter]
 * Optional attributes:
 *   heading    — overrides default heading text
 *   subtext    — overrides default sub-text
 *   btn_text   — overrides button label (default: Submit)
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

$heading  = ! empty( $atts['heading'] )  ? esc_html( $atts['heading'] )  : '';
$subtext  = ! empty( $atts['subtext'] )  ? esc_html( $atts['subtext'] )  : '';
$btn_text = ! empty( $atts['btn_text'] ) ? esc_html( $atts['btn_text'] ) : '';

$uid = 'wzp-nl-' . wp_unique_id();
?>
<div class="wzp-newsletter" id="<?php echo esc_attr( $uid ); ?>">
	<?php if ( $heading ) : ?>
		<p class="wzp-newsletter__heading"><?php echo $heading; // already escaped ?></p>
	<?php endif; ?>
	<?php if ( $subtext ) : ?>
		<p class="wzp-newsletter__subtext"><?php echo $subtext; // already escaped ?></p>
	<?php endif; ?>

	<form class="wzp-newsletter__form" novalidate>
		<?php wp_nonce_field( 'wzp_newsletter_nonce', 'wzp_nl_nonce', false ); ?>
		<input
			type="email"
			class="wzp-newsletter__input"
			placeholder="<?php esc_attr_e( 'Your Email Address', 'woo-zee-plugin' ); ?>"
			required
			autocomplete="email"
		>
		<button type="submit" class="wzp-newsletter__btn" aria-label="<?php esc_attr_e( 'Submit', 'woo-zee-plugin' ); ?>">
			<?php if ( $btn_text ) : ?>
				<?php echo $btn_text; // already escaped ?>
			<?php else : ?>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true">
					<line x1="5" y1="12" x2="19" y2="12"></line>
					<polyline points="12 5 19 12 12 19"></polyline>
				</svg>
			<?php endif; ?>
		</button>
	</form>

	<p class="wzp-newsletter__msg" aria-live="polite"></p>
</div>

<script>
( function () {
	'use strict';
	var wrap = document.getElementById( <?php echo wp_json_encode( $uid ); ?> );
	if ( ! wrap ) { return; }

	var form  = wrap.querySelector( '.wzp-newsletter__form' );
	var input = wrap.querySelector( '.wzp-newsletter__input' );
	var btn   = wrap.querySelector( '.wzp-newsletter__btn' );
	var msg   = wrap.querySelector( '.wzp-newsletter__msg' );

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		var email = input.value.trim();
		if ( ! email ) { return; }

		btn.disabled   = true;
		msg.className  = 'wzp-newsletter__msg';
		msg.textContent = '';

		var data = new FormData();
		data.append( 'action', 'wzp_newsletter_subscribe' );
		data.append( 'nonce',  form.querySelector( '[name="wzp_nl_nonce"]' ).value );
		data.append( 'email',  email );

		fetch( <?php echo wp_json_encode( esc_url( admin_url( 'admin-ajax.php' ) ) ); ?>, {
			method: 'POST',
			credentials: 'same-origin',
			body: data,
		} )
		.then( function ( r ) { return r.json(); } )
		.then( function ( res ) {
			if ( res.success ) {
				msg.classList.add( 'wzp-newsletter__msg--ok' );
				msg.textContent = res.data.message || <?php echo wp_json_encode( __( 'Thank you for subscribing!', 'woo-zee-plugin' ) ); ?>;
				input.value = '';
			} else {
				msg.classList.add( 'wzp-newsletter__msg--err' );
				msg.textContent = res.data.message || <?php echo wp_json_encode( __( 'Something went wrong. Please try again.', 'woo-zee-plugin' ) ); ?>;
			}
		} )
		.catch( function () {
			msg.classList.add( 'wzp-newsletter__msg--err' );
			msg.textContent = <?php echo wp_json_encode( __( 'Connection error. Please try again.', 'woo-zee-plugin' ) ); ?>;
		} )
		.finally( function () {
			btn.disabled = false;
		} );
	} );
} )();
</script>
