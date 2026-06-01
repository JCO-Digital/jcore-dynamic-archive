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

use Jcore\DynamicArchive;

if ( is_file( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}
define( 'JCORE_DYNAMIC_ARCHIVE_PLUGIN_FILE', __FILE__ );

require_once __DIR__ . '/consts.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/term-post-type-usage.php';
require_once __DIR__ . '/includes/update.php';
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
	function () {
		if ( ! class_exists( 'Timber\Timber' ) ) {
			wp_admin_notice(
				'You are doing something wrong... Timber is required for Dynamic Archive to work.',
				array(
					'type' => 'error',
				)
			);
		}
	}
);

// Registers the Timber location for the dynamic archive block.
Jcore\DynamicArchive\Blocks\dynamic_archive_setup_timber();
