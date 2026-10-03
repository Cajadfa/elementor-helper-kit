<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Native WordPress updater backed by the public GitHub repository.
 *
 * The latest version is read from the Version header on the main branch.
 * When it is newer than EHK_VERSION, WordPress receives the main-branch ZIP
 * as the update package. No GitHub token is required while the repository is
 * public.
 */
class EHK_Updater {
	const REPO_URL           = 'https://github.com/Cajadfa/elementor-helper-kit';
	const REMOTE_PLUGIN_FILE = 'https://raw.githubusercontent.com/Cajadfa/elementor-helper-kit/main/elementor-helper-kit.php';
	const PACKAGE_URL         = 'https://github.com/Cajadfa/elementor-helper-kit/archive/refs/heads/main.zip';
	const PLUGIN_SLUG         = 'elementor-helper-kit';

	public static function init() {
		add_filter( 'update_plugins_github.com', array( __CLASS__, 'check_update' ), 10, 4 );

		// GitHub branch archives extract as elementor-helper-kit-main. Rename the
		// extracted source so WordPress keeps the existing plugin directory name.
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'normalize_source_directory' ), 9, 4 );
	}

	/**
	 * Supply update information through WordPress' Update URI mechanism.
	 */
	public static function check_update( $update, $plugin_data, $plugin_file, $locales ) {
		if ( plugin_basename( EHK_FILE ) !== $plugin_file ) {
			return $update;
		}

		if ( empty( $plugin_data['UpdateURI'] ) || self::REPO_URL !== untrailingslashit( $plugin_data['UpdateURI'] ) ) {
			return $update;
		}

		$remote_version = self::get_remote_version();
		if ( ! $remote_version || ! version_compare( $remote_version, EHK_VERSION, '>' ) ) {
			return false;
		}

		return array(
			'slug'         => self::PLUGIN_SLUG,
			'version'      => $remote_version,
			'url'          => self::REPO_URL,
			'package'      => self::PACKAGE_URL,
			'requires'     => '6.0',
			'requires_php' => '7.4',
		);
	}

	/**
	 * Read the Version header directly from the plugin file on GitHub main.
	 */
	private static function get_remote_version() {
		$response = wp_remote_get(
			self::REMOTE_PLUGIN_FILE,
			array(
				'timeout'     => 10,
				'redirection' => 5,
				'headers'     => array(
					'Accept'     => 'text/plain',
					'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! is_string( $body ) || '' === $body ) {
			return false;
		}

		if ( ! preg_match( '/^\s*\*\s*Version:\s*([^\r\n]+)$/mi', $body, $matches ) ) {
			return false;
		}

		$version = trim( sanitize_text_field( $matches[1] ) );

		return preg_match( '/^\d+(?:\.\d+){1,3}(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ? $version : false;
	}

	/**
	 * Preserve the canonical plugin folder during updates from a GitHub archive.
	 */
	public static function normalize_source_directory( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( is_wp_error( $source ) ) {
			return $source;
		}

		if ( empty( $hook_extra['plugin'] ) || plugin_basename( EHK_FILE ) !== $hook_extra['plugin'] ) {
			return $source;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			return $source;
		}

		$corrected_source = trailingslashit( $remote_source ) . self::PLUGIN_SLUG . '/';
		if ( trailingslashit( $source ) === $corrected_source ) {
			return $source;
		}

		if ( $wp_filesystem->exists( $corrected_source ) ) {
			$wp_filesystem->delete( $corrected_source, true );
		}

		if ( ! $wp_filesystem->move( $source, $corrected_source, true ) ) {
			return new WP_Error(
				'ehk_update_source_rename_failed',
				__( 'Elementor Helper Kit could not normalize the GitHub update package directory.', 'elementor-helper-kit' )
			);
		}

		return $corrected_source;
	}
}
