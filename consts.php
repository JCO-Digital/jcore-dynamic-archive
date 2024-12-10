<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'DYNAMIC_ARCHIVE_URL', plugin_dir_url( __FILE__ ) );
define( 'DYNAMIC_ARCHIVE_PATH', plugin_dir_path( __FILE__ ) );
