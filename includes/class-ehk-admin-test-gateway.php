<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Optional WooCommerce classic-checkout test payment method. No processor. */
class EHK_Admin_Test_Gateway_Bootstrap {
	const ID = 'ehk_admin_test';

	public static function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'register_gateway' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 110 );
		add_action( 'wp_footer', array( __CLASS__, 'render_dialog' ), 5 );
	}

	/** A forged checkout POST from a non-admin must never use this gateway. */
	public static function allowed() {
		return EHK_Settings::woocommerce_test_gateway_enabled()
			&& is_user_logged_in()
			&& current_user_can( 'manage_options' );
	}

	public static function register_gateway( $methods ) {
		if ( ! self::allowed() || ! class_exists( 'WC_Payment_Gateway' ) ) {
			return $methods;
		}

		require_once EHK_DIR . 'includes/class-ehk-admin-test-payment-method.php';
		$methods[] = 'EHK_Admin_Test_Payment_Method';
		return $methods;
	}

	private static function is_classic_checkout() {
		return self::allowed()
			&& function_exists( 'is_checkout' )
			&& is_checkout()
			&& function_exists( 'is_order_received_page' )
			&& ! is_order_received_page();
	}

	public static function enqueue_assets() {
		if ( ! self::is_classic_checkout() ) {
			return;
		}
		wp_enqueue_style( 'ehk-admin-test-gateway', EHK_URL . 'assets/css/admin-test-gateway.css', array(), EHK_VERSION );
		wp_enqueue_script( 'ehk-admin-test-gateway', EHK_URL . 'assets/js/admin-test-gateway.js', array(), EHK_VERSION, true );
	}

	/** Shown only to site administrators, never to customers or visitors. */
	public static function render_dialog() {
		if ( ! self::is_classic_checkout() ) {
			return;
		}
		?>
		<div id="ehk-test-gateway-dialog" class="ehk-test-gateway-dialog" hidden aria-hidden="true">
			<div class="ehk-test-gateway-backdrop" data-ehk-test-close></div>
			<div class="ehk-test-gateway-panel" role="dialog" aria-modal="true" aria-labelledby="ehk-test-gateway-title" aria-describedby="ehk-test-gateway-description" dir="rtl">
				<div class="ehk-test-gateway-kicker">TEST MODE / شبیه‌سازی پرداخت</div>
				<h2 id="ehk-test-gateway-title">نتیجه پرداخت آزمایشی</h2>
				<p id="ehk-test-gateway-description">کدام نتیجه برای سفارش شبیه‌سازی شود؟ هیچ پرداخت واقعی انجام نمی‌شود.</p>
				<div class="ehk-test-gateway-actions">
					<button type="button" data-ehk-test-result="success">پرداخت موفق</button>
					<button type="button" data-ehk-test-result="failure">پرداخت ناموفق</button>
				</div>
				<button type="button" class="ehk-test-gateway-cancel" data-ehk-test-close>انصراف و بازگشت</button>
			</div>
		</div>
		<?php
	}
}
