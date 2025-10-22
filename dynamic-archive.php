<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

use function Jcore\Ydin\register_timber_location;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register the Timber templates directory.
register_timber_location( trailingslashit( __DIR__ ) . 'templates/', 1 );

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init_dynamic_archive_block(): void {
	$blocks = array( 'dynamic-archive', 'latest-posts' );
	foreach ( $blocks as $block ) {
		$success[] = register_block_type( __DIR__ . '/build/' . $block );
	}
	if ( in_array( false, $success, true ) && wp_get_environment_type() !== 'production' ) {
		wp_admin_notice( 'Dynamic Archive blocks could not be registered.' );
	}
	foreach ( $success as $block ) {
		if ( ! is_a( $block, WP_Block_Type::class, true ) ) {
			continue;
		}
		if ( is_array( $block->editor_script_handles ) ) {
			foreach ( $block->editor_script_handles as $handle ) {
				wp_set_script_translations( $handle, 'jcore-dynamic-archive', untrailingslashit( DYNAMIC_ARCHIVE_PATH ) . '/languages/' );
			}
		}
	}
}
add_action( 'init', 'init_dynamic_archive_block' );

add_filter(
	'timber/twig/functions',
	static function ( array $functions ): array {
		$functions['build_param_name'] = array(
			'callable' => '\Jcore\DynamicArchive\Helpers\build_param_name',
		);
		$functions['get_parameter']    = array(
			'callable' => '\Jcore\DynamicArchive\Helpers\get_parameter',
		);

		return $functions;
	}
);
