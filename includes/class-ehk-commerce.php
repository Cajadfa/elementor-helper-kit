<?php
/** Optional commerce integrations, independent of Elementor and the active theme. */
defined( 'ABSPATH' ) || exit;

class EHK_Commerce {
	public static function init() {
		add_filter( 'woocommerce_price_format', array( __CLASS__, 'price_format' ), 50 );
		add_filter( 'body_class', array( __CLASS__, 'body_classes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'currency_assets' ), 150 );
		// Allow plugin/theme updates in either order without running the old hooks twice.
		add_action( 'after_setup_theme', array( __CLASS__, 'retire_prince_hooks' ), 30 );

		if ( EHK_Settings::qq_account_orders_enabled() ) {
			require_once EHK_DIR . 'includes/commerce/order-locale.php';
			require_once EHK_DIR . 'includes/commerce/account-orders.php';
			require_once EHK_DIR . 'includes/commerce/account-orders-detail.php';
			require_once EHK_DIR . 'includes/commerce/order-received-redirect.php';
		}
	}

	public static function price_format( $format ) {
		return EHK_Settings::iranian_currency_enabled() ? '%2$s&nbsp;%1$s' : $format;
	}

	public static function body_classes( $classes ) {
		if ( EHK_Settings::iranian_currency_enabled() ) {
			$classes[] = 'ehk-iranian-currency';
		}
		return $classes;
	}

	public static function currency_assets() {
		if ( EHK_Settings::iranian_currency_enabled() ) {
			wp_enqueue_style( 'ehk-iranian-currency', EHK_URL . 'assets/css/iranian-currency.css', array(), EHK_VERSION );
		}
	}

	public static function retire_prince_hooks() {
		remove_filter( 'woocommerce_price_format', 'prince_toman_price_format', 50 );
		remove_filter( 'qqa_account_sections', 'prince_qqa_register_orders_section', 20 );
		remove_action( 'wp_enqueue_scripts', 'prince_qqa_orders_assets', 140 );
		remove_action( 'template_redirect', 'prince_redirect_paid_order_to_account', 15 );
	}
}
