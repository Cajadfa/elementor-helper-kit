<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EHK_Admin_Tools {
	const CLEAR_CACHE_ACTION = 'ehk_clear_elementor_cache';
	const CLEAR_CACHE_NONCE  = 'ehk_clear_elementor_cache';

	public static function init() {
		add_action( 'after_setup_theme', array( __CLASS__, 'maybe_hide_frontend_admin_bar' ), 100 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_elementor_cache_button' ), 90 );
		add_action( 'admin_post_' . self::CLEAR_CACHE_ACTION, array( __CLASS__, 'handle_clear_elementor_cache' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_cache_notice' ) );
	}

	/**
	 * Match the common theme snippet: show_admin_bar( false );
	 * This only affects the frontend admin bar; wp-admin keeps its toolbar.
	 */
	public static function maybe_hide_frontend_admin_bar() {
		if ( is_admin() || ! EHK_Settings::hide_frontend_admin_bar_enabled() ) {
			return;
		}

		show_admin_bar( false );
	}

	/**
	 * Add a one-click Elementor cache clear action to the wp-admin toolbar.
	 * The button intentionally appears only inside wp-admin.
	 */
	public static function add_elementor_cache_button( $wp_admin_bar ) {
		if ( ! is_admin() || ! EHK_Settings::elementor_cache_toolbar_enabled() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) || ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		$url = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::CLEAR_CACHE_ACTION ),
			self::CLEAR_CACHE_NONCE
		);

		$wp_admin_bar->add_node(
			array(
				'id'    => 'ehk-elementor-cache',
				'title' => '<span class="ab-icon dashicons dashicons-update" aria-hidden="true"></span><span class="ab-label">Elementor Cache</span>',
				'href'  => $url,
				'meta'  => array(
					'title' => __( 'Clear Elementor Files & Data', 'elementor-helper-kit' ),
				),
			)
		);
	}

	/**
	 * Run the same core Elementor files/data cache clear used by Elementor Tools.
	 */
	public static function handle_clear_elementor_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__( 'You are not allowed to perform this action.', 'elementor-helper-kit' ),
				'',
				array( 'response' => 403 )
			);
		}

		check_admin_referer( self::CLEAR_CACHE_NONCE );

		$status = 'unavailable';

		if ( did_action( 'elementor/loaded' ) && class_exists( '\\Elementor\\Plugin' ) ) {
			$elementor = \Elementor\Plugin::instance();

			if ( isset( $elementor->files_manager ) && method_exists( $elementor->files_manager, 'clear_cache' ) ) {
				$elementor->files_manager->clear_cache();
				$status = 'cleared';
			}
		}

		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = admin_url();
		}

		$redirect = add_query_arg( 'ehk_elementor_cache', $status, $redirect );
		wp_safe_redirect( $redirect );
		exit;
	}

	public static function render_cache_notice() {
		if ( empty( $_GET['ehk_elementor_cache'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$status = sanitize_key( wp_unslash( $_GET['ehk_elementor_cache'] ) );

		if ( 'cleared' === $status ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'Elementor cache/files & data cleared successfully.', 'elementor-helper-kit' )
			);
			return;
		}

		if ( 'unavailable' === $status ) {
			printf(
				'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
				esc_html__( 'Elementor cache could not be cleared because Elementor is unavailable or its cache manager could not be found.', 'elementor-helper-kit' )
			);
		}
	}
}
