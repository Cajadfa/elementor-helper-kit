<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Optional, theme-neutral CSS baseline for WooCommerce pages and shortcodes.
 */
class EHK_WooCommerce_Styles {
	const STYLESHEET = 'assets/css/woocommerce-minimal.css';

	public static function init() {
		add_filter( 'woocommerce_enqueue_styles', array( __CLASS__, 'filter_classic_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ), 99 );

		// Recheck explicitly assigned WooCommerce pages just before head styles print.
		add_action( 'wp_head', array( __CLASS__, 'ensure_configured_page_styles' ), 7 );
	}

	/**
	 * Read WooCommerce's configured page IDs (WooCommerce > Settings > Advanced).
	 * This also covers checkout/order-received and account endpoint URLs.
	 */
	private static function is_configured_woocommerce_page() {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return false;
		}

		$page_id = (int) get_queried_object_id();
		if ( $page_id <= 0 ) {
			return false;
		}

		foreach ( array( 'cart', 'checkout', 'myaccount', 'shop', 'terms' ) as $page ) {
			$configured_id = (int) wc_get_page_id( $page );
			if ( $configured_id > 0 && $configured_id === $page_id ) {
				return true;
			}
		}

		return false;
	}

	public static function ensure_configured_page_styles() {
		if ( ! self::is_available() || ! self::is_configured_woocommerce_page() ) {
			return;
		}

		// On these pages, restore our style if an optimization/theme dequeued it.
		// wp_head priority 7 runs before WordPress prints head styles at priority 8.
		self::enqueue_styles();
	}

	private static function is_available() {
		return EHK_Settings::woocommerce_minimal_styles_enabled()
			&& class_exists( 'WooCommerce' )
			&& is_readable( EHK_DIR . self::STYLESHEET );
	}

	public static function filter_classic_styles( $styles ) {
		return self::is_available() ? array() : $styles;
	}

	public static function enqueue_styles() {
		if ( ! self::is_available() ) {
			return;
		}

		// Product shortcodes/widgets may appear on ordinary WordPress pages.
		wp_enqueue_style(
			'ehk-woocommerce-minimal',
			EHK_URL . self::STYLESHEET,
			array(),
			EHK_VERSION
		);
	}
}
