<?php
/** Focused dependency, default, opt-out and currency behavior check. */
define( 'ABSPATH', __DIR__ );
define( 'EHK_DIR', dirname( __DIR__ ) . '/' );
define( 'EHK_URL', 'https://shop.test/wp-content/plugins/elementor-helper-kit/' );
define( 'EHK_VERSION', '1.1.17' );
$scenario = $argv[1] ?? 'full';
if ( 'no-woo' !== $scenario ) {
	class WooCommerce {}
	function wc_get_orders() {}
}
if ( 'no-qq' !== $scenario ) {
	function qqa_account_sections() {}
}
$GLOBALS['settings'] = array();
$GLOBALS['currency'] = 'IRT';
$GLOBALS['hooks'] = array();
function get_option( $key, $default = null ) { return 'ehk_settings' === $key ? $GLOBALS['settings'] : $default; }
function get_woocommerce_currency() { return $GLOBALS['currency']; }
function add_action( $hook, $callback, $priority = 10 ) { $GLOBALS['hooks'][$hook][$priority][] = $callback; }
function add_filter( $hook, $callback, $priority = 10 ) { add_action( $hook, $callback, $priority ); }
function remove_filter( $hook, $callback, $priority ) { $GLOBALS['retired'][] = $callback; }
function remove_action( $hook, $callback, $priority ) { remove_filter( $hook, $callback, $priority ); }
function sanitize_hex_color( $s ) { return preg_match( '/^#[0-9a-f]{6}$/i', $s ) ? $s : ''; }
function sanitize_text_field( $s ) { return strip_tags( $s ); }
function current_user_can() { return true; }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_html__( $s, $domain = '' ) { return $s; }
function settings_errors() {}
function settings_fields( $group ) {}
function submit_button( $s ) {}
function checked( $a, $b = true ) { if ( $a == $b ) echo 'checked="checked"'; }
function verify( $ok, $message ) { if ( ! $ok ) throw new RuntimeException( $message ); }
function get_current_user_id() { return 1; }
function get_transient( $key ) { return false; }
function admin_url( $path ) { return 'https://shop.test/wp-admin/' . $path; }
function esc_url( $url ) { return esc_attr( $url ); }
function wp_nonce_field( $action ) {}
require EHK_DIR . 'includes/class-ehk-woocommerce-pages.php';
require EHK_DIR . 'includes/class-ehk-settings.php';
require EHK_DIR . 'includes/class-ehk-commerce.php';

$has_woo = 'no-woo' !== $scenario;
$has_orders = $has_woo && 'no-qq' !== $scenario;
verify( EHK_Settings::iranian_currency_enabled() === $has_woo, 'IRT default/dependency' );
verify( EHK_Settings::qq_account_orders_enabled() === $has_orders, 'Orders default/dependency' );
verify( ! EHK_Settings::qq_myaccount_redirect_enabled(), 'Account redirect defaults to OFF' );
ob_start(); EHK_Settings::render_page(); $html = ob_get_clean();
verify( ( false !== strpos( $html, '[woocommerce_iranian_currency]' ) ) === $has_woo, 'Currency control visibility' );
verify( ( false !== strpos( $html, '[qq_account_orders]' ) ) === $has_orders, 'Orders control visibility' );
verify( ( false !== strpos( $html, '[woocommerce_qq_myaccount_redirect]' ) ) === $has_orders, 'Redirect control visibility' );
verify( ( false !== strpos( $html, EHK_WooCommerce_Pages::ACTION ) ) === $has_woo, 'Recreate button visibility' );
foreach ( array( 'IRT', 'IRR', 'USD' ) as $currency ) {
	$GLOBALS['currency'] = $currency;
	$expected = $has_woo && 'USD' !== $currency ? '%2$s&nbsp;%1$s' : '%1$s %2$s';
	verify( EHK_Commerce::price_format( '%1$s %2$s' ) === $expected, 'Price format for ' . $currency );
}
// Currency unavailable: a missing checkbox preserves either previous choice.
foreach ( array( 0, 1 ) as $saved ) {
	$GLOBALS['settings'] = array( 'woocommerce_iranian_currency' => $saved, 'qq_account_orders' => $saved, 'woocommerce_qq_myaccount_redirect' => $saved );
	$clean = EHK_Settings::sanitize( array() );
	verify( $clean['woocommerce_iranian_currency'] === $saved, 'Hidden currency value preserved' );
	verify( $clean['qq_account_orders'] === ( $has_orders ? 0 : $saved ), 'Visible checkbox opt-out / hidden orders preserved' );
	verify( $clean['woocommerce_qq_myaccount_redirect'] === ( $has_orders ? 0 : $saved ), 'Hidden redirect choice preserved' );
}
$GLOBALS['settings'] = array();
$GLOBALS['currency'] = 'IRT';
$clean = EHK_Settings::sanitize( array( 'woocommerce_iranian_currency' => 1, 'qq_account_orders' => 1 ) );
verify( 1 === $clean['woocommerce_iranian_currency'] && 1 === $clean['qq_account_orders'], 'Enabled values saved' );
$GLOBALS['settings'] = array( 'woocommerce_iranian_currency' => 0, 'qq_account_orders' => 0 );
verify( ! EHK_Settings::iranian_currency_enabled() && ! EHK_Settings::qq_account_orders_enabled(), 'Explicit OFF wins' );
verify( EHK_Commerce::price_format( 'original' ) === 'original', 'OFF restores format' );
verify( EHK_Commerce::body_classes( array( 'theme' ) ) === array( 'theme' ), 'OFF adds no layout class' );
EHK_Commerce::init();
verify( ! function_exists( 'ehk_qqa_render_orders_section' ), 'OFF does not load orders or redirect module' );
EHK_Commerce::retire_prince_hooks();
verify( count( $GLOBALS['retired'] ) === 4, 'Retire old hooks during staggered updates' );
echo "Commerce settings ($scenario): dependencies, defaults, visible controls, saved OFF and IRT/IRR passed.\n";
