<?php
/**
 * Genz Jewellery — One-time SEO content update script.
 *
 * Run: https://genzjewellery.com/wp-content/plugins/woo-zee-plugin/genz-content-update.php?key=GENZ_UPDATE_2026
 * ⚠ DELETE THIS FILE from the server immediately after running.
 */

define( 'GENZ_UPDATE_KEY', 'GENZ_UPDATE_2026' );

if ( ! isset( $_GET['key'] ) || $_GET['key'] !== GENZ_UPDATE_KEY ) {
	http_response_code( 403 );
	die( 'Access denied.' );
}

require_once dirname( __FILE__ ) . '/../../../wp-load.php';

if ( ! current_user_can( 'manage_options' ) ) {
	die( 'You must be logged in as an administrator.' );
}

$log = [];

// ── 1. HERO SLIDES ────────────────────────────────────────────────────────────
$existing = (array) get_option( 'wzp_hero_slides', [] );

$slides = [
	[
		'label'       => 'New Season Collection',
		'heading'     => 'Wear What Moves You',
		'description' => 'Handcrafted gold & silver jewellery designed for every version of you — from morning chai to midnight lights. Shop affordable jewellery online in Pakistan.',
		'btn_text'    => 'Shop Now ↗',
		'btn_url'     => '/shop/',
	],
	[
		'label'       => 'Stack & Style',
		'heading'     => 'Layer Up, Stand Out',
		'description' => 'Mix metals, textures, and lengths — your way. Explore Pakistan\'s trendiest jewellery collection for men and women.',
		'btn_text'    => 'Explore New Arrivals ↗',
		'btn_url'     => '/new-arrivals/',
	],
];

$new_slides = [];
foreach ( $slides as $i => $slide ) {
	$new_slides[] = array_merge(
		[ 'image_id' => isset( $existing[ $i ]['image_id'] ) ? (int) $existing[ $i ]['image_id'] : 0 ],
		$slide
	);
}
update_option( 'wzp_hero_slides', $new_slides );
$log[] = '✓ Hero slides updated (2 slides — image IDs preserved)';

// ── 2. BANNER CARDS ───────────────────────────────────────────────────────────
$existing = (array) get_option( 'wzp_banner_cards', [] );

$cards = [
	[ 'heading' => 'Sale Off 35%',    'btn_text' => 'Shop Now ↗',            'btn_url' => '/shop/?orderby=popularity', 'btn_icon' => '↗' ],
	[ 'heading' => 'Best Sellers',    'btn_text' => 'Shop Now ↗',            'btn_url' => '/shop/?orderby=popularity', 'btn_icon' => '↗' ],
	[ 'heading' => 'Unique Gifts',    'btn_text' => 'Shop Now ↗',            'btn_url' => '/shop/',                    'btn_icon' => '↗' ],
	[ 'heading' => 'Buy 1 Get 1',     'btn_text' => 'View All Collection ↗', 'btn_url' => '/shop/',                    'btn_icon' => '↗' ],
];

$new_cards = [];
foreach ( $cards as $i => $card ) {
	$new_cards[] = array_merge(
		[ 'image_id' => isset( $existing[ $i ]['image_id'] ) ? (int) $existing[ $i ]['image_id'] : 0 ],
		$card
	);
}
update_option( 'wzp_banner_cards', $new_cards );
$log[] = '✓ Banner cards updated (4 cards — image IDs preserved)';

// ── 3. LOOKBOOK ───────────────────────────────────────────────────────────────
$existing = (array) get_option( 'wzp_lookbook_options', [] );
update_option( 'wzp_lookbook_options', array_merge( $existing, [
	'label'       => "Editor's Pick",
	'heading'     => 'Bestselling Jewellery This Season',
	'description' => "Curated pieces our customers can't stop wearing",
] ) );
$log[] = '✓ Lookbook section updated';

// ── 4. SINGLE BANNER (New Arrivals) ──────────────────────────────────────────
$existing = (array) get_option( 'wzp_single_banner_options', [] );
update_option( 'wzp_single_banner_options', array_merge( $existing, [
	'label'       => 'Limited Time',
	'heading'     => 'New Arrivals Are Here',
	'description' => "Fresh drops every season. Be the first to wear Pakistan's most trending jewellery.",
	'btn_text'    => 'Shop New Arrivals',
	'btn_url'     => '/new-arrivals/',
] ) );
$log[] = '✓ Single banner (New Arrivals) updated';

// ── 5. TESTIMONIALS ───────────────────────────────────────────────────────────
$existing = (array) get_option( 'wzp_testimonials_data', [] );

$reviews = [
	[ 'name' => 'Ayesha Malik',     'location' => 'Karachi',    'review' => 'Absolutely love my Ethereal Glow Pendant! The quality is stunning for the price. Delivered to Karachi in 2 days. Highly recommend Genz!' ],
	[ 'name' => 'Hamza Qureshi',    'location' => 'Lahore',     'review' => 'Ordered the Zulfiqar Pendant for my brother — he was obsessed. Packaging was beautiful too. Will definitely order again!' ],
	[ 'name' => 'Fatima Siddiqui',  'location' => 'Islamabad',  'review' => 'The Islamic jewellery range is so meaningful and well-made. Gifted the Allah Pendant to my mother and she cried happy tears.' ],
	[ 'name' => 'Zara Ahmed',       'location' => 'Karachi',    'review' => 'Best jewellery shopping experience in Pakistan. The Snake Chain Necklace looks way more expensive than it is. 10/10!' ],
	[ 'name' => 'Sana Rehman',      'location' => 'Faisalabad', 'review' => 'Genz jewellery never disappoints. My 3rd order and everything is consistently great. The bracelet stacking set is 🔥' ],
	[ 'name' => 'Ali Hassan',       'location' => 'Rawalpindi', 'review' => 'The Black Onyx Pendant is exactly as shown in the photos — real, no filter needed. Fast shipping to Rawalpindi too!' ],
	[ 'name' => 'Noor Baig',        'location' => 'Lahore',     'review' => 'Bought the Violet Solitaire Bracelet as a birthday gift. She loved it so much she ordered two more herself. Quality is unmatched!' ],
];

$new_reviews = [];
foreach ( $reviews as $i => $review ) {
	$new_reviews[] = array_merge(
		[ 'avatar_id' => isset( $existing[ $i ]['avatar_id'] ) ? (int) $existing[ $i ]['avatar_id'] : 0 ],
		$review
	);
}
update_option( 'wzp_testimonials_data', $new_reviews );
$log[] = '✓ Testimonials updated (7 reviews — avatar IDs preserved)';

// ── 6. PRODUCT DETAIL — BENEFITS (Feature Icons) ─────────────────────────────
$existing = (array) get_option( 'wzp_product_detail_settings', [] );
update_option( 'wzp_product_detail_settings', array_merge( $existing, [
	'benefits' => [
		[ 'icon' => 'truck',   'title' => 'Free Shipping',   'subtitle' => 'Free delivery on all orders over Rs 2,000 across Pakistan' ],
		[ 'icon' => 'return',  'title' => 'Easy Returns',    'subtitle' => '7-day easy return & exchange — no questions asked' ],
		[ 'icon' => 'diamond', 'title' => 'Premium Quality', 'subtitle' => 'Gold-plated & sterling silver jewellery built to last' ],
		[ 'icon' => 'lock',    'title' => 'Secure Checkout', 'subtitle' => '100% safe & encrypted payments — shop with confidence' ],
	],
	'shipping' => [
		[ 'icon' => 'truck',  'text' => 'Free shipping on orders over Rs 2,000' ],
		[ 'icon' => 'clock',  'text' => 'Delivers in 3–5 business days nationwide' ],
		[ 'icon' => 'return', 'text' => '7-day easy return & exchange policy' ],
	],
] ) );
$log[] = '✓ Product detail benefits & shipping updated';

// ── 7. YOAST SEO META ─────────────────────────────────────────────────────────

// Homepage
$home_id = (int) get_option( 'page_on_front' );
if ( $home_id ) {
	update_post_meta( $home_id, '_yoast_wpseo_title',    'Buy Jewellery Online in Pakistan | Gold & Silver Jewellery | Genz' );
	update_post_meta( $home_id, '_yoast_wpseo_metadesc', 'Shop affordable gold & silver jewellery online in Pakistan. Explore necklaces, bracelets, earrings & Islamic jewellery. Fast delivery. Easy returns. Shop Genz now.' );
	update_post_meta( $home_id, '_yoast_wpseo_focuskw',  'jewellery online Pakistan' );
	$log[] = "✓ Homepage Yoast SEO updated (ID: {$home_id})";
} else {
	$log[] = '⚠ Homepage ID not found — set a static front page in Settings → Reading';
}

// Shop page
$shop_id = (int) get_option( 'woocommerce_shop_page_id' );
if ( $shop_id ) {
	update_post_meta( $shop_id, '_yoast_wpseo_title',    'Shop All Jewellery Online | Genz Jewellery Pakistan' );
	update_post_meta( $shop_id, '_yoast_wpseo_metadesc', 'Browse Pakistan\'s trendiest jewellery collection — necklaces, chains, earrings, bangles & more. Starting from Rs 750. Order now with fast nationwide delivery.' );
	update_post_meta( $shop_id, '_yoast_wpseo_focuskw',  'shop jewellery online Pakistan' );
	$log[] = "✓ Shop page Yoast SEO updated (ID: {$shop_id})";
} else {
	$log[] = '⚠ Shop page ID not found';
}

// New Arrivals page
$new_arrivals = get_page_by_path( 'new-arrivals' );
if ( $new_arrivals ) {
	update_post_meta( $new_arrivals->ID, '_yoast_wpseo_title',    'New Arrivals | Latest Jewellery Collection | Genz Pakistan' );
	update_post_meta( $new_arrivals->ID, '_yoast_wpseo_metadesc', 'Discover our latest jewellery drops — fresh styles added every season. Shop new earrings, necklaces, bracelets & chains. Fast delivery across Pakistan.' );
	update_post_meta( $new_arrivals->ID, '_yoast_wpseo_focuskw',  'new jewellery arrivals Pakistan' );
	$log[] = "✓ New Arrivals Yoast SEO updated (ID: {$new_arrivals->ID})";
} else {
	$log[] = '⚠ New Arrivals page not found — check slug is exactly "new-arrivals"';
}

// ── OUTPUT ────────────────────────────────────────────────────────────────────
header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head><title>Genz Content Update</title></head>
<body style="font-family:monospace;padding:30px;background:#f1f1f1;">
<div style="background:#fff;padding:24px;border-radius:8px;max-width:700px;margin:0 auto;box-shadow:0 2px 8px rgba(0,0,0,.1)">
	<h2 style="margin-top:0;color:#1d2327">✅ Genz Jewellery — Content Update Complete</h2>
	<ul style="line-height:2">
		<?php foreach ( $log as $line ) : ?>
			<li style="color:<?php echo strpos( $line, '⚠' ) === false ? '#2e7d32' : '#e65100'; ?>">
				<?php echo esc_html( $line ); ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<hr>
	<p style="color:#c62828;font-weight:bold;">
		⚠ IMPORTANT: Delete this file from your server NOW.<br>
		Path: <code>wp-content/plugins/woo-zee-plugin/genz-content-update.php</code>
	</p>
	<h3>Manual steps still needed:</h3>
	<ul>
		<li>FAQ section → update in Divi page builder on the homepage</li>
		<li>Newsletter shortcode heading/subtext → update in page where <code>[wzp_newsletter]</code> is placed</li>
		<li>Instagram gallery heading/badge → update in Divi module where feed is placed</li>
		<li>Footer "About Store" text → update in Divi footer builder or Widgets</li>
		<li>Install Yoast SEO plugin if not installed (for meta to work)</li>
	</ul>
</div>
</body>
</html>
