<?php

if ( ! defined( 'ABSPATH' ) || ! class_exists( 'WC_Payment_Gateway' ) ) {
	exit;
}

/** Gateway registration and server-side authorization are both enforced. */
class EHK_Admin_Test_Payment_Method extends WC_Payment_Gateway {
	public function __construct() {
		$this->id                 = EHK_Admin_Test_Gateway_Bootstrap::ID;
		$this->method_title       = 'درگاه آزمایشی مدیر';
		$this->method_description = 'شبیه‌سازی پرداخت ووکامرس فقط برای مدیر سایت؛ بدون تراکنش واقعی.';
		$this->title              = 'درگاه تست (فقط مدیر)';
		$this->description        = 'پرداخت واقعی انجام نمی‌شود. پس از ثبت سفارش می‌توانید نتیجه موفق یا ناموفق را انتخاب کنید.';
		$this->has_fields         = true;
		$this->supports           = array( 'products' );
		$this->enabled            = 'yes';
	}

	public function is_available() {
		return EHK_Admin_Test_Gateway_Bootstrap::allowed() && parent::is_available();
	}

	public function payment_fields() {
		echo '<p class="ehk-test-gateway-warning">' . esc_html( $this->description ) . '</p>';
		echo '<p class="ehk-test-gateway-warning"><strong>هشدار:</strong> سفارش آزمایشی واقعی ثبت می‌شود؛ پرداخت موفق می‌تواند موجودی و ایمیل‌های سفارش را تغییر دهد.</p>';
	}

	private function posted_result() {
		if ( ! isset( $_POST['ehk_test_outcome'] ) || ! is_scalar( $_POST['ehk_test_outcome'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce checks checkout nonce.
			return '';
		}
		return sanitize_key( wp_unslash( $_POST['ehk_test_outcome'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	public function validate_fields() {
		if ( ! EHK_Admin_Test_Gateway_Bootstrap::allowed() ) {
			wc_add_notice( 'این درگاه فقط برای مدیر سایت مجاز است.', 'error' );
			return false;
		}
		if ( ! in_array( $this->posted_result(), array( 'success', 'failure' ), true ) ) {
			wc_add_notice( 'لطفاً نتیجه پرداخت آزمایشی را از پنجره شبیه‌سازی انتخاب کنید.', 'error' );
			return false;
		}
		return true;
	}

	public function process_payment( $order_id ) {
		if ( ! EHK_Admin_Test_Gateway_Bootstrap::allowed() ) {
			wc_add_notice( 'دسترسی به درگاه آزمایشی مجاز نیست.', 'error' );
			return array( 'result' => 'failure' );
		}

		$outcome = $this->posted_result();
		if ( ! in_array( $outcome, array( 'success', 'failure' ), true ) ) {
			wc_add_notice( 'نتیجه پرداخت آزمایشی معتبر نیست.', 'error' );
			return array( 'result' => 'failure' );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_payment_method() !== $this->id ) {
			wc_add_notice( 'سفارش آزمایشی معتبر پیدا نشد.', 'error' );
			return array( 'result' => 'failure' );
		}

		$order->update_meta_data( '_ehk_test_payment', 'yes' );
		$order->update_meta_data( '_ehk_test_outcome', $outcome );
		$order->add_order_note( sprintf( 'EHK gateway: admin #%d simulated %s. No money collected.', get_current_user_id(), $outcome ) );
		$order->save();

		if ( 'failure' === $outcome ) {
			$order->update_status( 'failed', 'EHK: simulated payment failure. No payment collected.' );
			wc_add_notice( 'پرداخت آزمایشی ناموفق شبیه‌سازی شد. هیچ مبلغی دریافت نشده است.', 'error' );
			return array( 'result' => 'failure' );
		}

		// Normal paid flow, including WooCommerce inventory and order notification hooks.
		$order->payment_complete( 'ehk-test-' . $order->get_id() );
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}
		return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
	}
}
