<?php
/** Safe redirect and one-time account confirmation contract. */
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
class EHK_Settings {
 public static function qq_account_orders_enabled() { return true; }
}

function add_action() {}
function absint( $n ) { return abs( (int) $n ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function ehk_qqa_orders_url() { return 'https://shop.test/account?section=orders'; }
function add_query_arg( $key, $val, $url ) { return $url . '&' . rawurlencode($key) . '=' . rawurlencode((string) $val); }
function ehk_qqa_order_is_owned( $order, $user ) {
	return $order instanceof WC_Order && $user > 0 && $order->get_customer_id() === $user;
}
$GLOBALS['transients'] = array();
$GLOBALS['user_meta'] = array();
function get_transient( $key ) { return $GLOBALS['transients'][$key] ?? false; }
function set_transient( $key, $val, $ttl ) { $GLOBALS['transients'][$key] = $val; return true; }
function delete_transient( $key ) { unset( $GLOBALS['transients'][$key] ); return true; }
function get_user_meta( $id, $key, $single = false ) { return $GLOBALS['user_meta'][$id][$key] ?? ''; }
function update_user_meta( $id, $key, $val ) { $GLOBALS['user_meta'][$id][$key] = $val; return true; }
class WC_Order {
	private $id,$customer,$key,$status;
	public function __construct( $id, $customer, $key, $status ) {
		$this->id=$id; $this->customer=$customer; $this->key=$key; $this->status=$status;
	}
	public function get_id() { return $this->id; }
	public function get_customer_id() { return $this->customer; }
	public function get_order_key() { return $this->key; }
	public function has_status( $statuses ) { return in_array( $this->status, (array) $statuses, true ); }
}
$GLOBALS['orders'] = array(
	82 => new WC_Order( 82, 21, 'wc_good_secure_key', 'processing' ),
	83 => new WC_Order( 83, 21, 'wc_completed_key', 'completed' ),
	84 => new WC_Order( 84, 22, 'wc_other_customer_key', 'processing' ),
	85 => new WC_Order( 85, 21, 'wc_failed_key', 'failed' ),
	86 => new WC_Order( 86, 21, 'wc_pending_key', 'pending' ),
	87 => new WC_Order( 87, 21, 'wc_onhold_key', 'on-hold' ),
);
function wc_get_order( $id ) { return $GLOBALS['orders'][$id] ?? false; }
require dirname( __DIR__ ) . '/includes/commerce/order-received-redirect.php';
function ensure( $condition, $message ) { if ( ! $condition ) throw new RuntimeException( $message ); }
$target = 'https://shop.test/account?section=orders&order_id=82';
ensure( ehk_order_received_account_target( 82, 'wc_good_secure_key', 21 ) === $target, 'Processing paid order goes to its QQ Account detail' );
ensure( ehk_order_received_account_target( 83, 'wc_completed_key', 21 ) === 'https://shop.test/account?section=orders&order_id=83', 'Completed order redirects' );
ensure( ehk_order_received_account_target( 82, 'wc_bad_key', 21 ) === '', 'Forged Woo order key is rejected' );
ensure( ehk_order_received_account_target( 82, '', 21 ) === '', 'Missing Woo key is rejected' );
ensure( ehk_order_received_account_target( 82, 'wc_good_secure_key', 22 ) === '', 'Foreign customer cannot redirect to order' );
ensure( ehk_order_received_account_target( 84, 'wc_other_customer_key', 21 ) === '', 'Another customer order remains private' );
ensure( ehk_order_received_account_target( 82, 'wc_good_secure_key', 0 ) === '', 'Guests are not redirected to private QQ Account' );
ensure( ehk_order_received_account_target( 85, 'wc_failed_key', 21 ) === '', 'Failed payment stays at WooCommerce' );
ensure( ehk_order_received_account_target( 86, 'wc_pending_key', 21 ) === '', 'Pending payment never promises shipping' );
ensure( ehk_order_received_account_target( 87, 'wc_onhold_key', 21 ) === '', 'Unpaid on-hold order never promises dispatch' );
ensure( ehk_order_received_account_target( 999, 'fake', 21 ) === '', 'Unknown order rejected' );
$flash = ehk_order_success_transient_key( 21, 82 );
set_transient( $flash, '1', 900 );
ensure( ehk_order_account_first_confirmation( wc_get_order(82), 22 ) === '', 'Foreign account must not see flash' );
ensure( get_transient( $flash ) === '1', 'Foreign access does not consume flash' );
$html = ehk_order_account_first_confirmation( wc_get_order(82), 21 );
ensure( strpos( $html, '<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received' ) === 0, 'Native Woo success classes rendered' );
ensure( strpos( $html, 'سفارش شما دریافت شد و بزودی برای شما ارسال خواهد گردید.' ) !== false, 'Exact Persian receipt text' );
ensure( strpos( $html, 'data-ehk-success-order-id="82"' ) !== false, 'Flash scoped to correct order' );
ensure( get_transient( $flash ) === false, 'Flash consumed on first display' );
ensure( get_user_meta( 21, ehk_order_success_seen_meta_key(82), true ) === '1', 'First view persistently marked' );
set_transient( $flash, '1', 900 );
ensure( ehk_order_account_first_confirmation( wc_get_order(82), 21 ) === '', 'Second visit never shows banner, even if flash is rescheduled' );
ensure( ehk_order_account_first_confirmation( wc_get_order(85), 21 ) === '', 'Failed order never shows success banner' );
ensure( ehk_order_account_first_confirmation( wc_get_order(84), 21 ) === '', 'Foreign order never displays flash' );
echo "Woo receipt: verified account redirect, successful statuses, owner-only order, one-time banner and replay prevention passed.\n";

