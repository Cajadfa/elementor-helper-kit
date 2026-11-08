<?php
/**
 * WooCommerce paid checkout -> authenticated customer's QQ Account order view.
 * Keep Woo's original thankyou for guests, unpaid/failed orders and any
 * unverified order key. All redirects remain local to the site's account page.
 */
defined( 'ABSPATH' ) || exit;

function ehk_order_success_transient_key( $user_id, $order_id ) {
	return 'prince_order_success_' . absint( $user_id ) . '_' . absint( $order_id );
}

function ehk_order_success_seen_meta_key( $order_id ) {
	return '_prince_account_success_seen_' . absint( $order_id );
}

/**
 * Return a safe, account-owned target ONLY for a genuinely accepted order.
 * Woo order keys are mandatory: guessing an order ID never grants a redirect.
 * Use statuses that indicate payment/checkout completed, never pending/failed.
 */
function ehk_order_received_account_target( $order_id, $order_key, $user_id ) {
	if ( ! EHK_Settings::qq_account_orders_enabled() || ! $user_id || ! $order_id || ! is_scalar( $order_key ) || '' === (string) $order_key
		|| ! function_exists( 'wc_get_order' ) ) {
		return '';
	}
	$order = wc_get_order( absint( $order_id ) );
	if ( ! ehk_qqa_order_is_owned( $order, $user_id )
		|| ! $order->has_status( array( 'processing', 'completed' ) )
		|| ! is_string( $order->get_order_key() )
		|| ! hash_equals( $order->get_order_key(), (string) $order_key ) ) {
		return '';
	}
	return add_query_arg( 'order_id', (int) $order->get_id(), ehk_qqa_orders_url() );
}

/**
 * The native Woo order-received endpoint runs before template output. The
 * transient carries only an order ID for the owner, not private order data.
 * Do not redirect when QQ cannot render the account order section.
 */
function ehk_redirect_paid_order_to_account() {
	if ( ! EHK_Settings::qq_account_orders_enabled() || is_admin() || wp_doing_ajax() || ! function_exists( 'is_order_received_page' )
		|| ! is_order_received_page() || ! is_user_logged_in()
		|| ! function_exists( 'qqa_account_sections' ) ) {
		return;
	}
	$order_id = absint( get_query_var( 'order-received' ) );
	$user_id  = get_current_user_id();
	$order_key = isset( $_GET['key'] ) && is_scalar( $_GET['key'] )
		? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
	$target = ehk_order_received_account_target( $order_id, $order_key, $user_id );
	if ( ! $target ) {
		return;
	}
	// Only schedule the first confirmation. A bookmarked thankyou URL will
	// not show the congratulatory message a second time.
	if ( ! get_user_meta( $user_id, ehk_order_success_seen_meta_key( $order_id ), true ) ) {
		set_transient( ehk_order_success_transient_key( $user_id, $order_id ), '1', 15 * MINUTE_IN_SECONDS );
	}
	wp_safe_redirect( $target, 303 );
	exit;
}
add_action( 'template_redirect', 'ehk_redirect_paid_order_to_account', 15 );

/**
 * One-time confirmation shown at the TOP of the selected QQ order. The
 * ownership check happens before this function is called and also here.
 * The message is never emitted for an arbitrary ?order_id= query.
 */
function ehk_order_account_first_confirmation( $order, $user_id ) {
	if ( ! ehk_qqa_order_is_owned( $order, $user_id )
		|| ! $order->has_status( array( 'processing', 'completed' ) ) ) {
		return '';
	}
	$order_id = (int) $order->get_id();
	$key = ehk_order_success_transient_key( $user_id, $order_id );
	if ( '1' !== get_transient( $key )
		|| get_user_meta( $user_id, ehk_order_success_seen_meta_key( $order_id ), true ) ) {
		return '';
	}
	delete_transient( $key );
	update_user_meta( $user_id, ehk_order_success_seen_meta_key( $order_id ), '1' );
	return '<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received ehk-orders-success-once" data-ehk-orders-success-once data-ehk-success-order-id="' . esc_attr( $order_id ) . '" role="status">سفارش شما دریافت شد و بزودی برای شما ارسال خواهد گردید.</p>';
}

