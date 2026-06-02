<?php
/**
 * Plugin Name: JCORE Dynamic Archive
 * Plugin URI: https://github.com/jco-digital/jcore-dynamic-archive
 * Description: JCORE Dynamic Archive module.
 * Version: 0.25.2
 * Author: JCO Digital
 * Author URI: https://jco.fi
 * Text Domain: jcore-dynamic-archive
 * Domain Path: /languages
 *
 * @package Jcore\DynamicArchive
 */

if ( is_file( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}
defined( 'JCORE_DYNAMIC_ARCHIVE_PLUGIN_FILE' ) || define( 'JCORE_DYNAMIC_ARCHIVE_PLUGIN_FILE', __FILE__ );

require_once __DIR__ . '/consts.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/term-post-type-usage.php';
if ( class_exists( \Jcore\Update\Hooks\PluginUpdateHooks::class ) ) {
	require_once __DIR__ . '/includes/update.php';
}
require_once __DIR__ . '/blocks/dynamic-archive.php';

add_action(
	'init',
	static function () {
		load_plugin_textdomain(
			'jcore-dynamic-archive',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages',
		);
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( 'Timber\\Timber' ) || ! is_admin() ) {
			return;
		}

		add_action(
			'admin_notices',
			static function () {
				if ( function_exists( 'wp_admin_notice' ) ) {
					wp_admin_notice(
						esc_html__( 'Timber is required for JCORE Dynamic Archive to work.', 'jcore-dynamic-archive' ),
						array( 'type' => 'error' )
					);
					return;
				}
			}
		);
	}
);

// Registers the Timber location for the dynamic archive block.
Jcore\DynamicArchive\Blocks\dynamic_archive_setup_timber();
