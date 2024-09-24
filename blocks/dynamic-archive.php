<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init_dynamic_archive_block(): void {
	$blocks = array( 'dynamic-archive' );
	foreach ( $blocks as $block ) {
		$success[] = register_block_type( __DIR__ . '/build/' . $block );
	}
	if ( in_array( false, $success, true ) && wp_get_environment_type() !== 'production' ) {
		wp_admin_notice( 'Dynamic Archive blocks could not be registered.' );
	}
}
add_action( 'init', 'init_dynamic_archive_block' );