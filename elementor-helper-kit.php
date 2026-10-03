<?php
/**
 * Plugin Name: Elementor Helper Kit
 * Plugin URI: https://github.com/Cajadfa/elementor-helper-kit
 * Update URI: https://github.com/Cajadfa/elementor-helper-kit
 * Description: Elementor development helpers: JSON editing, motion/CSS controls, frontend admin-bar control, a quick Elementor cache action, and GitHub updates.
 * Version: 1.0.0
 * Author: Sajad Pedar
 * Author URI: http://wwwc.qoqnooos.ir
 * License: GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: elementor-helper-kit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EHK_VERSION', '1.0.0' );
define( 'EHK_FILE', __FILE__ );
define( 'EHK_DIR', plugin_dir_path( __FILE__ ) );
define( 'EHK_URL', plugin_dir_url( __FILE__ ) );

require_once EHK_DIR . 'includes/class-ehk-settings.php';
require_once EHK_DIR . 'includes/class-ehk-motion.php';
require_once EHK_DIR . 'includes/class-ehk-style-priority.php';
require_once EHK_DIR . 'includes/class-ehk-json-editor.php';
require_once EHK_DIR . 'includes/class-ehk-admin-tools.php';
require_once EHK_DIR . 'includes/class-ehk-updater.php';

register_activation_hook( __FILE__, array( 'EHK_Settings', 'activate' ) );

add_action( 'plugins_loaded', function () {
	EHK_Settings::init();
	EHK_Motion::init();
	EHK_Style_Priority::init();
	EHK_JSON_Editor::init();
	EHK_Admin_Tools::init();
	EHK_Updater::init();
} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	$settings_url = admin_url( 'options-general.php?page=elementor-helper-kit' );
	array_unshift( $links, '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'elementor-helper-kit' ) . '</a>' );
	return $links;
} );
