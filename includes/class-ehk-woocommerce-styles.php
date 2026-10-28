<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Optional, theme-neutral CSS baseline for classic WooCommerce output.
 */
class EHK_WooCommerce_Styles {
	const STYLESHEET = 'assets/css/woocommerce-minimal.css';

	public static function init() {
		add_filter( 'woocommerce_enqueue_styles', array( __CLASS__, 'filter_classic_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ), 99 );
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
