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
	$blocks = array( 'dynamic-archive' );
	foreach ( $blocks as $block ) {
		$success[] = register_block_type( __DIR__ . '/build/' . $block );
	}
	if ( in_array( false, $success, true ) && wp_get_environment_type() !== 'production' ) {
		wp_admin_notice( 'Dynamic Archive blocks could not be registered.' );
	}
}
add_action( 'init', 'init_dynamic_archive_block' );

// Register the htmx script.
add_action(
	'init',
	static function () {
		wp_register_script(
			'dynamic-archive-htmx-alpine-morph-plugin',
			DYNAMIC_ARCHIVE_URL . 'dist/htmx.alpine-morph.min.js',
			array( 'dynamic-archive-htmx' ),
			'2.0.2',
			array(
				'in_footer' => false,
			)
		);
		wp_register_script(
			'dynamic-archive-alpine-morph-plugin',
			DYNAMIC_ARCHIVE_URL . 'dist/alpine.morph.min.js',
			array(),
			'3.14.1',
			array(
				'in_footer' => false,
				'strategy'  => 'defer',
			)
		);
		wp_register_script(
			'dynamic-archive-htmx',
			DYNAMIC_ARCHIVE_URL . 'dist/htmx.min.js',
			array(),
			'2.0.2',
			array(
				'in_footer' => false,
			)
		);
		wp_register_script(
			'dynamic-archive-alpine',
			DYNAMIC_ARCHIVE_URL . 'dist/alpine.min.js',
			array( 'dynamic-archive-alpine-morph-plugin' ),
			'3.14.1',
			array(
				'in_footer' => false,
				'strategy'  => 'defer',
			)
		);
	}
);

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
