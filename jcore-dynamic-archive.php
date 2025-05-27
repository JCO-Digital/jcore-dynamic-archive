<?php
/**
 * Plugin Name: JCORE Dynamic Archive
 * Plugin URI: https://github.com/jco-digital/jcore-dynamic-archive
 * Description: JCORE Dynamic Archive module.
 * Version: 0.14.0
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

require_once __DIR__ . '/consts.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/blocks/dynamic-archive.php';

DynamicArchive\Bootstrap::init();

add_action(
	'init',
	static function () {
		load_plugin_textdomain( 'jcore-dynamic-archive', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);
