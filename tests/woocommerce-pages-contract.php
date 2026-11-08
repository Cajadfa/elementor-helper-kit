<?php
/** Focused page replacement/rollback and account-redirect contract, without WP. */
define( 'ABSPATH', __DIR__ );
define( 'OBJECT', 'OBJECT' );
define( 'EMPTY_TRASH_DAYS', ( $argv[1] ?? '' ) === 'no-trash' ? 0 : 30 );
class WooCommerce {}
class WP_Error {
	private $code, $message;
	public function __construct( $code, $message ) { $this->code = $code; $this->message = $message; }
	public function get_error_message() { return $this->message; }
}
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function absint( $v ) { return abs( (int) $v ); }
function wc_get_orders() {}
function qqa_account_sections() {}
function add_action() {}
function get_option( $k, $default = false ) { return $GLOBALS['options'][$k] ?? $default; }
function update_option( $k, $v ) {
	if ( ( $GLOBALS['fail_option'] ?? '' ) === $k ) { $GLOBALS['fail_option'] = ''; return false; }
	$GLOBALS['options'][$k] = $v; return true;
}
function add_option( $k, $v, $deprecated = '', $autoload = false ) {
	if ( isset( $GLOBALS['options'][$k] ) ) return false;
	return update_option( $k, $v );
}
function delete_option( $k ) { unset( $GLOBALS['options'][$k] ); }
function delete_transient( $k ) {}
function current_user_can() { return $GLOBALS['allowed']; }
function get_post( $id ) { return isset( $GLOBALS['posts'][$id] ) ? clone $GLOBALS['posts'][$id] : null; }
function get_post_status( $id ) { $p = get_post( $id ); return $p ? $p->post_status : false; }
function get_page_template_slug( $id ) { return $GLOBALS['posts'][$id]->meta_input['_wp_page_template'] ?? ''; }
function get_page_by_path( $slug, $format = OBJECT, $type = 'page' ) {
	foreach ( $GLOBALS['posts'] as $post ) if ( $post->post_name === $slug ) return clone $post;
	return null;
}
function wp_generate_uuid4() { return 'uuid-' . ++$GLOBALS['uuid']; }
function wp_insert_post( $args, $error = false ) {
	$id = ++$GLOBALS['next_id']; $args['ID'] = $id;
	$GLOBALS['posts'][$id] = (object) $args; return $id;
}
function wp_update_post( $args, $error = false ) {
	$id = $args['ID']; if ( ! isset( $GLOBALS['posts'][$id] ) ) return new WP_Error( 'missing', 'Missing page' );
	if ( ( $args['post_status'] ?? '' ) === 'publish' && ( $args['post_name'] ?? '' ) === ( $GLOBALS['fail_publish'] ?? 'none' ) ) {
		$GLOBALS['fail_publish'] = 'none'; return new WP_Error( 'publish', 'Injected failure' );
	}
	foreach ( $args as $key => $value ) $GLOBALS['posts'][$id]->$key = $value;
	return $id;
}
function wp_trash_post( $id ) {
	if ( 0 === EMPTY_TRASH_DAYS ) throw new RuntimeException( 'Must never call permanent-delete trash path' );
	$GLOBALS['posts'][$id]->post_status = 'trash'; $GLOBALS['posts'][$id]->post_name .= '__trashed'; return get_post( $id );
}
function wp_untrash_post( $id ) { $GLOBALS['posts'][$id]->post_status = 'draft'; return get_post( $id ); }
function wp_delete_post( $id, $force ) {
	if ( $id < 100 ) throw new RuntimeException( 'Old content must never be permanently deleted' );
	unset( $GLOBALS['posts'][$id] ); return true;
}
function flush_rewrite_rules( $hard ) { if ( $hard ) throw new RuntimeException( 'Use soft rewrite flush' ); }
function is_account_page() { return $GLOBALS['query_id'] === (int) get_option( 'woocommerce_myaccount_page_id' ); }
function qqa_is_account() { return $GLOBALS['query_id'] === (int) get_option( 'qqa_account_page_id' ); }
function get_queried_object_id() { return $GLOBALS['query_id']; }
function is_wc_endpoint_url( $endpoint ) { return ( $GLOBALS['endpoint'] ?? '' ) === $endpoint; }
function get_query_var( $key ) { return $key === 'view-order' ? 82 : ''; }
function get_permalink( $id ) { return 'https://test.local/atriat/' . get_post( $id )->post_name . '/'; }
function wp_validate_redirect( $url, $fallback ) { return $url; }
function add_query_arg( $key, $val, $url ) { return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $key . '=' . $val; }
function verify( $ok, $message ) { if ( ! $ok ) throw new RuntimeException( $message ); }
function reset_fixture() {
	$GLOBALS['options'] = array( 'woocommerce_cart_page_id' => 11, 'woocommerce_checkout_page_id' => 12, 'woocommerce_myaccount_page_id' => 13, 'qqa_account_page_id' => 70 );
	$GLOBALS['posts'] = array(); $GLOBALS['next_id'] = 100; $GLOBALS['uuid'] = 0; $GLOBALS['allowed'] = true;
	$GLOBALS['fail_publish'] = 'none'; $GLOBALS['fail_option'] = ''; $GLOBALS['endpoint'] = ''; $GLOBALS['query_id'] = 13;
	foreach ( array( 11 => 'cart', 12 => 'checkout', 13 => 'my-account', 70 => 'customer-panel' ) as $id => $slug ) {
		$GLOBALS['posts'][$id] = (object) array( 'ID' => $id, 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_content' => 'Old page content', 'meta_input' => array() );
	}
	$_SERVER['REQUEST_METHOD'] = 'GET';
}
require dirname( __DIR__ ) . '/includes/class-ehk-settings.php';
require dirname( __DIR__ ) . '/includes/class-ehk-woocommerce-pages.php';

reset_fixture();
$new = EHK_WooCommerce_Pages::recreate();
verify( ! is_wp_error( $new ), 'Recreate should succeed' );
foreach ( EHK_WooCommerce_Pages::definitions() as $slug => $definition ) {
	$p = get_post( $new[$slug] );
	verify( $p->post_name === $slug && $p->post_status === 'publish' && $p->post_content === $definition['shortcode'], 'Exact canonical slug and shortcode: ' . $slug );
	verify( get_page_template_slug( $p->ID ) === 'default', 'Default WordPress page template' );
	$option = 'woocommerce_' . ( $slug === 'my-account' ? 'myaccount' : $slug ) . '_page_id';
	verify( get_option( $option ) === $p->ID, 'WooCommerce assignment: ' . $slug );
}
foreach ( array( 11, 12, 13 ) as $id ) verify( get_post_status( $id ) === 'trash' && get_post( $id )->post_content === 'Old page content', 'Old content retained in Trash' );
verify( get_post_status( 70 ) === 'publish', 'QQ account is untouched' );
verify( ! get_option( EHK_WooCommerce_Pages::LOCK ), 'Lock released' );

foreach ( array( 'publish', 'assignment' ) as $failure ) {
	reset_fixture(); $before = $GLOBALS['options'];
	if ( $failure === 'publish' ) $GLOBALS['fail_publish'] = 'checkout';
	else $GLOBALS['fail_option'] = 'woocommerce_checkout_page_id';
	verify( is_wp_error( EHK_WooCommerce_Pages::recreate() ), 'Injected failure must fail' );
	verify( $GLOBALS['options'] === $before && count( $GLOBALS['posts'] ) === 4, 'Rollback removes only fresh pages and restores options' );
	foreach ( array( 11 => 'cart', 12 => 'checkout', 13 => 'my-account' ) as $id => $slug ) verify( get_post_status( $id ) === 'publish' && get_post( $id )->post_name === $slug, 'Rollback restores original status and slug' );
}
reset_fixture(); $GLOBALS['posts'][11]->post_name = 'old-cart';
$GLOBALS['posts'][99] = clone $GLOBALS['posts'][11]; $GLOBALS['posts'][99]->ID = 99; $GLOBALS['posts'][99]->post_name = 'cart';
verify( is_wp_error( EHK_WooCommerce_Pages::recreate() ) && get_post_status( 99 ) === 'publish' && count( $GLOBALS['posts'] ) === 5, 'Unrelated slug collision stops before page changes' );
reset_fixture(); $GLOBALS['options']['qqa_account_page_id'] = 13;
verify( is_wp_error( EHK_WooCommerce_Pages::recreate() ) && get_post_status( 13 ) === 'publish', 'Protect shared QQ account page' );
reset_fixture(); $GLOBALS['allowed'] = false;
verify( is_wp_error( EHK_WooCommerce_Pages::recreate() ) && count( $GLOBALS['posts'] ) === 4, 'Permission enforced' );

reset_fixture();
verify( EHK_WooCommerce_Pages::account_redirect_target() === '', 'Redirect defaults to OFF' );
$GLOBALS['options']['ehk_settings'] = array( 'woocommerce_qq_myaccount_redirect' => 1 );
verify( EHK_WooCommerce_Pages::account_redirect_target() === 'https://test.local/atriat/customer-panel/', 'Configured QQ page supports subdirectory and guests' );
$GLOBALS['endpoint'] = 'view-order';
verify( EHK_WooCommerce_Pages::account_redirect_target() === 'https://test.local/atriat/customer-panel/?section=orders&order_id=82', 'Woo deep link maps to QQ owner-checked order view' );
foreach ( array( 'lost-password', 'customer-logout' ) as $endpoint ) {
	$GLOBALS['endpoint'] = $endpoint; verify( EHK_WooCommerce_Pages::account_redirect_target() === '', 'Keep Woo auth endpoint: ' . $endpoint );
}
$GLOBALS['endpoint'] = ''; $_SERVER['REQUEST_METHOD'] = 'POST';
verify( EHK_WooCommerce_Pages::account_redirect_target() === '', 'Do not intercept account form POSTs' );
$_SERVER['REQUEST_METHOD'] = 'GET'; $GLOBALS['query_id'] = 70;
verify( EHK_WooCommerce_Pages::account_redirect_target() === '', 'No redirect loop on QQ account' );
$GLOBALS['query_id'] = 13; $GLOBALS['posts'][70]->post_status = 'draft';
verify( EHK_WooCommerce_Pages::account_redirect_target() === '', 'No redirect to missing/unpublished account page' );
echo 'Woo pages: shortcode replacement, retained content, failure rollback, collision protection and optional QQ routing passed (Trash days: ' . EMPTY_TRASH_DAYS . ").\n";
