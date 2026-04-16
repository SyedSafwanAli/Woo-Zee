<?php
/**
 * [wzp_instagram_feed] — Render function and shortcode entry point.
 *
 * Shortcode:  [wzp_instagram_feed count="6"]
 * Settings:   get_option('wzp_instagram_options', [])
 *
 * Options:
 *   access_token (string) — Instagram Graph API long-lived token
 *   username     (string) — display username (not used in API call)
 *   count        (int)    — default number of images to display (1-12)
 *
 * Caching: fetched feed is cached in a transient keyed 'wzp_instagram_feed'
 * for HOUR_IN_SECONDS. Clear via admin "Clear Cache" or on settings save.
 *
 * Fallback: if no token is saved, the API fails, or the transient is empty,
 * renders $count placeholder tiles so the layout is never broken.
 *
 * @package WooZeePlugin
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wzp_render_instagram_feed' ) ) :

/**
 * Build and return the Instagram feed HTML string.
 *
 * @param array $atts Shortcode attributes (count override).
 * @return string     Escaped HTML.
 */
function wzp_render_instagram_feed( $atts ) {

	// ── Load saved options ────────────────────────────────────────────────────
	$ig_opts = wp_parse_args(
		(array) get_option( 'wzp_instagram_options', array() ),
		array(
			'access_token' => '',
			'username'     => '',
			'count'        => 6,
		)
	);

	$token    = sanitize_text_field( $ig_opts['access_token'] );
	$ig_count = min( 12, max( 1, absint( $ig_opts['count'] ) ) );

	// Shortcode attribute overrides saved count.
	$atts  = shortcode_atts( array( 'count' => $ig_count ), $atts, 'wzp_instagram_feed' );
	$count = min( 12, max( 1, absint( $atts['count'] ) ) );

	// ── Transient cache ───────────────────────────────────────────────────────
	$transient_key = 'wzp_instagram_feed';
	$posts         = false;

	if ( ! empty( $token ) ) {
		$posts = get_transient( $transient_key );

		if ( false === $posts ) {
			// ── Live API call ─────────────────────────────────────────────────
			$api_url = add_query_arg(
				array(
					'fields'       => 'id,media_url,permalink,media_type',
					'limit'        => $count,
					'access_token' => $token,
				),
				'https://graph.instagram.com/me/media'
			);

			$response = wp_remote_get(
				$api_url,
				array(
					'timeout'    => 10,
					'user-agent' => 'WooZeePlugin/' . WZP_VERSION,
				)
			);

			if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
				$body = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( ! empty( $body['data'] ) && is_array( $body['data'] ) ) {
					// Keep IMAGE posts only and trim to requested count.
					$filtered = array_values(
						array_filter(
							$body['data'],
							fn( $p ) => isset( $p['media_type'] ) && 'IMAGE' === $p['media_type']
						)
					);
					$posts = array_slice( $filtered, 0, $count );
					set_transient( $transient_key, $posts, HOUR_IN_SECONDS );
				}
			}
		}
	}

	// Normalise: treat empty array and false the same way.
	if ( empty( $posts ) ) {
		$posts = false;
	}

	// ── Reusable inline SVG icon ──────────────────────────────────────────────
	$svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" '
	          .      'width="32" height="32" viewBox="0 0 24 24" '
	          .      'fill="none" stroke="currentColor" stroke-width="1.5" '
	          .      'aria-hidden="true" focusable="false">'
	          .   '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>'
	          .   '<circle cx="12" cy="12" r="4"/>'
	          .   '<circle cx="17.5" cy="6.5" r="1" fill="currentColor"/>'
	          . '</svg>';

	// ── Render ────────────────────────────────────────────────────────────────
	ob_start();
	?>
	<div class="wzp-module" data-wzp-module="instagram-feed">
		<div class="wzp-instagram-feed"
		     role="list"
		     aria-label="<?php esc_attr_e( 'Instagram photos', 'woo-zee-plugin' ); ?>">

			<?php if ( $posts ) : ?>

				<?php foreach ( $posts as $post ) : ?>
					<?php
					$media_url = esc_url( $post['media_url'] ?? '' );
					$permalink = esc_url( $post['permalink'] ?? 'https://www.instagram.com/' );
					if ( ! $media_url ) { continue; }
					?>
					<a href="<?php echo $permalink; ?>"
					   target="_blank"
					   rel="noopener noreferrer"
					   class="wzp-instagram-item"
					   role="listitem"
					   aria-label="<?php esc_attr_e( 'View on Instagram', 'woo-zee-plugin' ); ?>">
						<img src="<?php echo $media_url; ?>"
						     alt="<?php esc_attr_e( 'Instagram post', 'woo-zee-plugin' ); ?>"
						     loading="lazy"
						     decoding="async">
						<div class="wzp-instagram-overlay">
							<?php echo $svg_icon; // phpcs:ignore — static SVG string, no user input ?>
						</div>
					</a>
				<?php endforeach; ?>

			<?php else : ?>

				<?php /* Placeholder tiles — shown when no token is set or API fails */ ?>
				<?php for ( $i = 0; $i < $count; $i++ ) : ?>
					<div class="wzp-instagram-item wzp-instagram-placeholder"
					     role="listitem"
					     aria-hidden="true">
						<div class="wzp-instagram-overlay">
							<?php echo $svg_icon; // phpcs:ignore — static SVG ?>
						</div>
					</div>
				<?php endfor; ?>

			<?php endif; ?>

		</div>
	</div>
	<?php
	return ob_get_clean();
}

endif; // function_exists

// ── Entry point ───────────────────────────────────────────────────────────────
echo wzp_render_instagram_feed( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput
