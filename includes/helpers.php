<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Checks if the given post type is a valid post type.
 *
 * @param string $post_type The post type to check.
 *
 * @return bool
 */
function is_post_type( string $post_type ): bool {
	$post_types = get_post_types();
	return in_array( $post_type, $post_types, true );
}
