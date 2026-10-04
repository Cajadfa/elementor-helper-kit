<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps Elementor's base frontend stylesheet ahead of the theme styles.
 *
 * Elementor's frontend.min.css contains generic rules such as `.elementor img`.
 * When that stylesheet is printed after a theme stylesheet with equal CSS
 * specificity, Elementor can unexpectedly win the cascade. This helper only
 * changes the print order; it does not modify Elementor or theme files.
 */
class EHK_Style_Priority {
	const ELEMENTOR_FRONTEND_HANDLE = 'elementor-frontend';

	public static function init() {
		/*
		 * wp_print_styles runs immediately before WordPress resolves and prints the
		 * stylesheet queue, so this also catches styles enqueued late during
		 * wp_enqueue_scripts.
		 */
		add_action( 'wp_print_styles', array( __CLASS__, 'move_elementor_frontend_before_theme' ), -9999 );
	}

	public static function move_elementor_frontend_before_theme() {
		if ( ! EHK_Settings::elementor_css_first_enabled() || is_admin() ) {
			return;
		}

		global $wp_styles;

		if ( ! ( $wp_styles instanceof WP_Styles ) || empty( $wp_styles->queue ) ) {
			return;
		}

		$elementor_handles = self::get_elementor_frontend_handles( $wp_styles );
		if ( empty( $elementor_handles ) ) {
			return;
		}

		$queue = array_values( $wp_styles->queue );

		// Remove Elementor's frontend stylesheet from its current location first.
		$queue = array_values(
			array_filter(
				$queue,
				function ( $handle ) use ( $elementor_handles ) {
					return ! in_array( $handle, $elementor_handles, true );
				}
			)
		);

		/*
		 * Put Elementor immediately before the first stylesheet that belongs to
		 * the active parent/child theme. This keeps WordPress/plugin stylesheet
		 * ordering intact while guaranteeing that the theme wins equal-specificity
		 * CSS conflicts against Elementor's base frontend CSS.
		 */
		$insert_at = self::find_first_theme_style_index( $wp_styles, $queue );
		if ( null === $insert_at ) {
			// Fallback: if no theme stylesheet is detectable, print Elementor first.
			$insert_at = 0;
		}

		array_splice( $queue, $insert_at, 0, $elementor_handles );
		$wp_styles->queue = $queue;
	}

	private static function get_elementor_frontend_handles( $wp_styles ) {
		$handles = array();

		foreach ( $wp_styles->queue as $handle ) {
			if ( self::ELEMENTOR_FRONTEND_HANDLE === $handle ) {
				$handles[] = $handle;
				continue;
			}

			if ( empty( $wp_styles->registered[ $handle ] ) ) {
				continue;
			}

			$src = (string) $wp_styles->registered[ $handle ]->src;
			if ( self::is_elementor_frontend_src( $src ) ) {
				$handles[] = $handle;
			}
		}

		return array_values( array_unique( $handles ) );
	}

	private static function is_elementor_frontend_src( $src ) {
		if ( '' === $src ) {
			return false;
		}

		$path = wp_parse_url( $src, PHP_URL_PATH );
		$path = is_string( $path ) ? $path : $src;
		$path = str_replace( '\\', '/', $path );

		return (bool) preg_match( '#/elementor/assets/css/frontend(?:\.min)?\.css$#i', $path );
	}

	private static function find_first_theme_style_index( $wp_styles, $queue ) {
		foreach ( $queue as $index => $handle ) {
			if ( empty( $wp_styles->registered[ $handle ] ) ) {
				continue;
			}

			$src = (string) $wp_styles->registered[ $handle ]->src;
			if ( self::is_theme_style_src( $src ) ) {
				return $index;
			}
		}

		return null;
	}

	private static function is_theme_style_src( $src ) {
		if ( '' === $src ) {
			return false;
		}

		$src_path = self::normalize_url_path( $src );
		if ( '' === $src_path ) {
			return false;
		}

		$theme_urls = array_unique(
			array_filter(
				array(
					get_template_directory_uri(),
					get_stylesheet_directory_uri(),
				)
			)
		);

		foreach ( $theme_urls as $theme_url ) {
			$theme_path = self::normalize_url_path( $theme_url );
			if ( '' !== $theme_path && 0 === strpos( $src_path, trailingslashit( $theme_path ) ) ) {
				return true;
			}
		}

		return false;
	}

	private static function normalize_url_path( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! is_string( $path ) ) {
			return '';
		}

		$path = '/' . ltrim( str_replace( '\\', '/', rawurldecode( $path ) ), '/' );
		return untrailingslashit( $path );
	}
}
