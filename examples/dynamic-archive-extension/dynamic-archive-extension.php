<?php
/**
 * Plugin Name: ACME Dynamic Archive Extension (Example)
 * Description: Example extension plugin for JCORE Dynamic Archive extensibility APIs.
 * Version: 0.1.0
 * Author: ACME
 * Text Domain: jcore-dynamic-archive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'jcore_dynamic_archive_attributes',
	static function ( array $attributes ): array {
		$attributes['acme_showFeaturedOnly'] = array(
			'type'    => 'boolean',
			'default' => false,
		);

		return $attributes;
	},
	10,
	1
);

add_filter(
	'jcore_dynamic_archive_args',
	static function ( array $args, array $attributes ): array {
		if ( empty( $attributes['acme_showFeaturedOnly'] ) ) {
			return $args;
		}

		if ( ! isset( $args['meta_query'] ) || ! is_array( $args['meta_query'] ) ) {
			$args['meta_query'] = array();
		}

		$args['meta_query'][] = array(
			'key'     => '_thumbnail_id',
			'compare' => 'EXISTS',
		);

		return $args;
	},
	10,
	2
);

/**
 * Enqueues editor extension script for dynamic archive controls.
 *
 * @return void
 */
function acme_dynamic_archive_enqueue_editor_extension_script(): void {
	$block_type = WP_Block_Type_Registry::get_instance()->get_registered( 'jcore/dynamic-archive' );
	if ( ! $block_type ) {
		return;
	}

	$script_path = __DIR__ . '/dynamic-archive-extension.js';
	if ( ! is_file( $script_path ) ) {
		return;
	}

	$dependencies = array(
		'wp-components',
		'wp-element',
		'wp-i18n',
		'wp-plugins',
	);

	$editor_handle = $block_type->editor_script_handles[0] ?? '';
	if ( is_string( $editor_handle ) && '' !== $editor_handle ) {
		$dependencies[] = $editor_handle;
	}

	wp_register_script(
		'acme-dynamic-archive-extension-editor',
		plugins_url( 'dynamic-archive-extension.js', __FILE__ ),
		array_unique( $dependencies ),
		(string) filemtime( $script_path ),
		true
	);

	wp_enqueue_script( 'acme-dynamic-archive-extension-editor' );
}
add_action( 'enqueue_block_editor_assets', 'acme_dynamic_archive_enqueue_editor_extension_script' );
