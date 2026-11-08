<?php
/** CLI owner-only Woo orders / item / shipping contract. */
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
class EHK_Settings {
 public static function qq_account_orders_enabled() { return true; }
}

$filters = array();
function add_filter( $name, $callback ) { $GLOBALS['filters'][$name] = $callback; }
function add_action() {}
function is_user_logged_in() { return true; }
function get_current_user_id() { return 21; }
$GLOBALS['transients'] = array();
$GLOBALS['user_meta'] = array();
function get_transient($key) { return $GLOBALS['transients'][$key] ?? false; }
function set_transient($key,$value,$ttl) { $GLOBALS['transients'][$key] = $value; return true; }
function delete_transient($key) { unset($GLOBALS['transients'][$key]); return true; }
function get_user_meta($id,$key,$single=true) { return $GLOBALS['user_meta'][$id][$key] ?? ''; }
function update_user_meta($id,$key,$value) { $GLOBALS['user_meta'][$id][$key] = $value; return true; }

function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function wp_strip_all_tags( $s ) { return strip_tags( (string) $s ); }
function esc_url( $s ) { return esc_attr( $s ); }
function wp_kses_post( $s ) { return $s; }
function home_url( $s ) { return 'https://test.local' . $s; }
function get_option( $key ) { return 71; }
function get_post_status( $id ) { return 'publish'; }
function get_permalink( $id ) { return 'https://test.local/my-customer-panel/'; }
function wp_unslash( $s ) { return $s; }
function absint( $x ) { return abs( (int) $x ); }
function wp_timezone() { return new DateTimeZone( 'Asia/Tehran' ); }
function wp_date( $format, $timestamp, $timezone ) { return (new DateTimeImmutable('@' . $timestamp))->setTimezone($timezone)->format($format); }
function add_query_arg( $a, $b, $c = null ) {
	if ( is_array( $a ) ) { $args = $a; $url = $b; }
	else { $args = array( $a => $b ); $url = $c; }
	return $url . ( strpos( $url, '?' ) !== false ? '&' : '?' ) . http_build_query( $args );
}
function wc_format_datetime( $s ) { return '۱۴۰۵/۰۸/۱۲'; }
function wc_display_item_meta( $item, $settings = array() ) {
	return '<ul class="wc-item-meta"><li>مقدار عطر: 50 میلی‌گرم</li></ul>';
}
class WC_Order_Item_Product {
	public $name;public $meta;
	public function __construct( $name, $meta = '۵۰ میلی‌گرم' ) { $this->name=$name; $this->meta=$meta; }
	public function get_name(){return $this->name;}
	public function get_quantity(){return 2;}
	public function get_meta($key,$single=true){
		if($key==='_pps_fill_mg')return '20';
		if($key==='مقدار عطر')return '20 میلی‌گرم';
		if($key==='شیشه انتخابی')return 'شیشه آتگان — 50 میلی‌گرم';
		return '';
	}
	public function get_product(){return false;}
}
class WC_Order {
	private $id; private $user_id; private $status;
	public function __construct( $id, $uid, $status ) { $this->id=$id; $this->user_id=$uid; $this->status=$status; }
	public function get_customer_id() { return $this->user_id; }
	public function get_id() { return $this->id; }
	public function get_status() { return $this->status; }
	public function has_status($statuses) { return in_array($this->status,(array)$statuses,true); }
	public function get_items( $type = 'line_item' ) { return array( new WC_Order_Item_Product( 'عطر اختصاصی سفارش ' . $this->id ) ); }
	public function get_formatted_line_subtotal( $item ) { return '<span>600,000 تومان</span>'; }
	public function get_formatted_order_total() { return '<strong>600,000 تومان</strong>'; }
	public function get_formatted_shipping_address() { return 'خیابان تحویل سفارش ' . $this->id; }
	public function get_formatted_billing_address() { return 'خیابان صورتحساب سفارش ' . $this->id; }
	public function get_billing_phone() { return '09120000101'; }
	public function get_billing_email() { return 'owner@example.test'; }
	public function get_shipping_method() { return 'پست'; }
	public function get_customer_note() { return 'تحویل صبح'; }
	public function get_date_created() { return new DateTimeImmutable('2026-11-03 10:15:00', new DateTimeZone('Asia/Tehran')); }
	public function get_payment_method_title() { return 'پرداخت آنلاین'; }
}
$own = new WC_Order( 101, 21, 'processing' );
$foreign = new WC_Order( 202, 22, 'completed' );
$older = new WC_Order( 303, 21, 'on-hold' );
function wc_get_order( $id ) {
	global $own, $foreign, $older;
	if ( $id === 101 ) return $own;
	if ( $id === 202 ) return $foreign;
	if ( $id === 303 ) return $older;
	return false;
}
function wc_get_order_status_name( $status ) {
	if ( $status === 'processing' ) return 'در حال انجام';
	if ( $status === 'on-hold' ) return 'در انتظار بررسی';
	return 'تکمیل شده';
}
function wc_get_order_statuses() { return array( 'wc-processing' => 'در حال انجام', 'wc-completed' => 'تکمیل شده' ); }
function wc_get_orders( $args ) {
	global $own, $foreign;
	if ( $args['customer'] !== 21 || $args['return'] !== 'objects' || $args['paginate'] !== true ) {
		throw new RuntimeException( 'Wrong WooCommerce customer query' );
	}
	// A deliberately poisoned result tests defense-in-depth ownership checks.
	return (object) array( 'orders' => array( $own, $foreign ), 'max_num_pages' => 1 );
}
require dirname( __DIR__ ) . '/includes/commerce/order-locale.php';
require dirname( __DIR__ ) . '/includes/commerce/account-orders.php';
require dirname( __DIR__ ) . '/includes/commerce/account-orders-detail.php';
require dirname( __DIR__ ) . '/includes/commerce/order-received-redirect.php';
function ensure( $condition, $message ) { if ( ! $condition ) throw new RuntimeException( $message ); }
$entities = '<span data-id="82">250,000 &#x6F0; &nbsp; 50</span>';
ensure( ehk_order_fa_markup( $entities ) === '<span data-id="82">۲۵۰,۰۰۰ &#x6F0; &nbsp; ۵۰</span>', 'Visible digits localized; HTML attributes and entities preserved' );
$sections = $GLOBALS['filters']['qqa_account_sections']( array( 'profile' => array( 'order' => 10 ) ) );
ensure( isset( $sections['orders'] ) && $sections['orders']['order'] === 15 && $sections['orders']['menu'] === true, 'QQ Account section registration' );
ensure( ehk_qqa_orders_url() === 'https://test.local/my-customer-panel/?section=orders', 'Footer tracking link' );
foreach ( array( 202, 101, 303 ) as $requested ) {
	$_GET['order_id'] = (string) $requested;
	ob_start();
	ehk_qqa_render_orders_section( 'orders', $sections['orders'] );
	$html = ob_get_clean();
	ensure( strpos( $html, 'سفارش #۱۰۱' ) !== false, 'Owned order number shown in list' );
	ensure( strpos( $html, 'عطر اختصاصی سفارش ۱۰۱' ) !== false, 'Purchased product visible in list and details' );
	ensure( strpos( $html, 'مقدار عطر: ۵۰ میلی‌گرم' ) !== false, 'Order-item metadata included' );
	ensure( strpos( $html, '۲ عدد عطر ۲۰ میلی‌گرمی با شیشه آتگان' ) !== false, 'Purchased item row preview shows quantity, purchased fill and exact saved bottle' );
	ensure( strpos( $html, '>× ' ) === false, 'Order list no longer uses x quantity marker' );

	ensure( strpos( $html, 'خیابان تحویل سفارش ۱۰۱' ) !== false, 'Owner shipping data available in details' );
	ensure( strpos( $html, '۶۰۰,۰۰۰ تومان' ) !== false, 'Actual WooCommerce line/total price present' );
	ensure( strpos( $html, 'data-ehk-order-detail-id="101"' ) !== false, 'Owned detail panel available' );
	ensure( strpos( $html, 'ehk-order-row-total' ) !== false && strpos( $html, 'مبلغ کل' ) !== false, 'Order total visible inside order row' );
	ensure( strpos( $html, '۱۲ آبان ۱۴۰۵' ) !== false, 'Jalali date with Persian month visible' );
	ensure( strpos( $html, 'ساعت ۱۰:۱۵' ) !== false, 'Localized order time in detail' );
	ensure( strpos( $html, 'مقدار عطر: ۵۰ میلی‌گرم' ) !== false, 'Item metadata Latin digits converted to Persian' );
	ensure( strpos( $html, '09120000101' ) === false && strpos( $html, '۰۹۱۲۰۰۰۰۱۰۱' ) !== false, 'Visible delivery phone digits Persian' );
	ensure( strpos( $html, 'عطر اختصاصی سفارش ۲۰۲' ) === false, 'Foreign purchased item hidden' );
	ensure( strpos( $html, 'خیابان تحویل سفارش ۲۰۲' ) === false, 'Foreign delivery details hidden' );
	ensure( strpos( $html, 'data-ehk-order-detail-id="202"' ) === false, 'Foreign detail panel never generated' );
	if ( $requested === 303 ) {
		ensure( strpos( $html, 'data-ehk-order-detail-id="303"' ) !== false, 'Owned direct deep link works outside current page' );
		ensure( strpos( $html, 'خیابان تحویل سفارش ۳۰۳' ) !== false, 'Deep-linked owner order exposes its own shipping' );
	}
}
ensure( ehk_qqa_order_is_owned( $own, 21 ) && ! ehk_qqa_order_is_owned( $foreign, 21 ), 'Order ownership prevents ID enumeration' );
$flash = ehk_order_success_transient_key(21,101);
set_transient($flash,'1',900);
$_GET['order_id']='101';
ob_start();
ehk_qqa_render_orders_section('orders',$sections['orders']);
$first=ob_get_clean();
ensure(strpos($first,'class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received')!==false,'One-time Woo success banner appears in authorized QQ order details');
ensure(strpos($first,'سفارش شما دریافت شد و بزودی برای شما ارسال خواهد گردید.')!==false,'Exact receipt confirmation displayed');
ensure(strpos($first,'data-ehk-success-order-id="101"')!==false,'Banner scoped to selected order');
ob_start();
ehk_qqa_render_orders_section('orders',$sections['orders']);
$second=ob_get_clean();
ensure(strpos($second,'ehk-orders-success-once')===false,'Confirmation is only rendered once on the server');
$_GET['order_id']='202';
set_transient(ehk_order_success_transient_key(21,202),'1',900);
ob_start();
ehk_qqa_render_orders_section('orders',$sections['orders']);
$foreignHtml=ob_get_clean();
ensure(strpos($foreignHtml,'ehk-orders-success-once')===false,'No foreign order success banner');
echo "QQ orders: purchase previews, owner-only details, first-time Woo success and deep links passed.\n";

