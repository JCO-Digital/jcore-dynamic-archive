<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace Jcore\DynamicArchive\Helpers;

use Timber\URLHelper;

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

/**
 * Handles setting up the dynamic archive arguments.
 *
 * @param array $args The arguments to handle.
 * @param array $attributes The attributes of the dynamic archive block.
 *
 * @return array
 */
function handle_dynamic_args( array $args, array $attributes ): array {
	$instance_id = $attributes['instanceId'] ?? '';
	if ( ( $attributes['showPagination'] ?? false ) && ! ( $attributes['infiniteScroll'] ?? false ) ) {
		$args['paged'] = get_parameter( build_param_name( 'paged', $instance_id ), 1 );
	} elseif ( ( $attributes['showPagination'] ?? false ) && ( $attributes['infiniteScroll'] ?? false ) ) {
		$paged                  = get_parameter( build_param_name( 'paged', $instance_id ), 1 );
		$args['posts_per_page'] *= $paged;
	}
	$order = get_parameter( build_param_name( 'order', $instance_id ), $attributes['order'] ?? 'desc' );
	$order = match ( strtolower( $order ) ) {
		'asc'  => 'asc',
		default => 'desc',
	};
	$args['order']   = strtoupper( $order );
	$args['orderby'] = get_parameter( build_param_name( 'orderby', $instance_id ), $attributes['sortBy'] ?? 'date' );

	return handle_taxonomies_filter( $args, $attributes );
}

/**
 * Handles generating the taxonomies filter for the dynamic archive block.
 *
 * @param array $args The arguments to handle.
 * @param array $attributes The attributes of the dynamic archive block.
 *
 * @return array
 */
function handle_taxonomies_filter( array $args, array $attributes ): array {
	$instance_id = $attributes['instanceId'] ?? '';
	$all_filters = get_parameter( build_param_name( 'taxonomy', $instance_id ), array() );
	foreach ( $attributes['taxonomies'] ?? array() as $taxonomy ) {
		$active_filters = get_nested_value( $all_filters, array( $taxonomy ), array() );
		if ( empty( $active_filters ) ) {
			continue;
		}
		if ( ! is_array( $active_filters ) ) {
			$active_filters = array( $active_filters );
		}
		$active_filters      = array_map( 'absint', $active_filters );
		$args['tax_query'][] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'id',
			'terms'    => $active_filters,
		);
	}
	return $args;
}

/**
 * Builds a parameter name for the dynamic archive block, based on the instance id and the parameter name.
 *
 * @param string     $name The name of the parameter.
 * @param int|string $instance_id The instance id of the dynamic archive block.
 *
 * @return string
 */
function build_param_name( string $name, int|string $instance_id ): string {
	return 'dynamic-archive-' . $instance_id . '-' . $name;
}

/**
 * Handles getting a parameter from the URL, and sanitizes it.
 *
 * @param string     $name The name of the parameter.
 * @param mixed|null $default_value The default value to return if the parameter is not set.
 *
 * @return mixed
 */
function get_parameter( string $name, mixed $default_value = null ): mixed {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET[ $name ] ) ) {
		return $default_value;
	}
	$value = wp_unslash( $_GET[ $name ] );

	if ( $default_value === null ) {
		return sanitize_text_field( $value );
	}
	return sanitize_value_deep( $value, true );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}

/**
 * Recursively sanitize a value or an array of values.
 *
 * @param mixed $value The value to be sanitized.
 * @param bool  $preserve_keys Whether to preserve the keys of the array or not.
 *
 * @return mixed The sanitized value.
 */
function sanitize_value_deep( mixed $value, bool $preserve_keys = false ): mixed {
	if ( is_array( $value ) ) {
		if ( $preserve_keys ) {
			return array_map(
				static function ( $arr ) {
					return sanitize_value_deep( $arr, true );
				},
				$value
			);
		}
		return array_values( array_map( '\Jcore\DynamicArchive\Helpers\sanitize_value_deep', $value ) );
	}

	return match ( gettype( $value ) ) {
		'integer' => absint( $value ),
		'double'  => (float) $value,
		'boolean' => (bool) $value,
		default => sanitize_text_field( $value ),
	};
}

/**
 * Safely gets the nested value from an array.
 *
 * @param array      $arr The array in question.
 * @param array      $keys The keys to be used to get the value.
 * @param mixed|null $default_value The default value to return in case the value does not exist.
 *
 * @return mixed
 */
function get_nested_value( array $arr, array $keys, mixed $default_value = null ): mixed {
	$key = array_shift( $keys );

	if ( ! isset( $arr[ $key ] ) ) {
		return $default_value;
	}

	if ( empty( $keys ) ) {
		return $arr[ $key ];
	}

	return get_nested_value( $arr[ $key ], $keys, $default_value );
}

/**
 * Handles setting a nested value in an array. Modifies the array directly.
 *
 * @param array $arr The array in question.
 * @param array $keys The keys to be used to set the value.
 * @param mixed $value The value to be set.
 *
 * @return void
 */
function set_nested_value( array &$arr, array $keys, mixed $value ): void {
	$key = array_shift( $keys );

	if ( empty( $keys ) ) {
		$arr[ $key ] = $value;
		return;
	}

	if ( ! isset( $arr[ $key ] ) ) {
		$arr[ $key ] = array();
	}

	set_nested_value( $arr[ $key ], $keys, $value );
}


/**
 * Handles constructing the taxonomies filter for the dynamic archive block.
 *
 * @param array $attributes The attributes of the dynamic archive block.
 *
 * @return array
 */
function build_taxonomies_filter( array $attributes ): array {
	$taxonomies  = array();
	$instance_id = $attributes['instanceId'] ?? '';
	$all_filters = get_parameter( build_param_name( 'taxonomy', $instance_id ), array() );
	foreach ( $attributes['taxonomies'] ?? array() as $taxonomy ) {
		$active_filters = get_nested_value( $all_filters, array( $taxonomy ), array() );
		if ( ! is_array( $active_filters ) ) {
			$active_filters = array( $active_filters );
		}
		$active_filters = array_map( 'absint', $active_filters );
		$terms          = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			)
		);
        $taxonomies[ $taxonomy ] = array(
            'name' => $taxonomy,
            'label' => apply_filters( 'jcore_dynamic_archive_taxonomy_label', $taxonomy, $attributes ),
            'filterType' => get_nested_value( $attributes, array( 'filterTypes', $taxonomy ), 'checkbox' ),
            'terms' => array(),
        );
		foreach ( $terms as $term ) {
			$taxonomies[ $taxonomy ]['terms'][] = array(
				'id'         => $term->term_id,
				'type'       => $term->taxonomy,
				'name'       => $term->name,
				'filterType' => get_nested_value( $attributes, array( 'filterTypes', $term->taxonomy ), 'checkbox' ),
				'active'     => in_array( $term->term_id, $active_filters, true ),
			);
		}
	}
	return apply_filters( 'jcore_dynamic_archive_taxonomies_filter', $taxonomies, $attributes );
}

/**
 * Handles building pagination url.
 *
 * @param array $attributes The attributes of the dynamic archive block.
 * @param int $page The page number.
 * @return string
 */
function build_pagination_url(array $attributes, int $page ): string {
    $url = URLHelper::get_current_url();
    $param_name = build_param_name( 'paged', $attributes['instanceId'] ?? '' );
    $url = remove_query_arg( $param_name, $url );
    return add_query_arg( $param_name, absint($page), $url );
}
