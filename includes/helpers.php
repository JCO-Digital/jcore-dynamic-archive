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
		$paged                   = get_parameter( build_param_name( 'paged', $instance_id ), 1 );
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
		$active_filters   = array_map( 'absint', $active_filters );
		$has_active_child = array_filter(
			$active_filters,
			static function ( $term_id ) use ( $taxonomy ) {
				$term = get_term( $term_id, $taxonomy );
				return $term->parent > 0;
			}
		);
		$tax_query        = array(
			'taxonomy'         => $taxonomy,
			'field'            => 'id',
			'terms'            => $active_filters,
			'include_children' => count( $has_active_child ) === 0,
		);
		if ( count( $active_filters ) > 1 ) {
			$tax_query['operator'] = 'AND';
		}
		$args['tax_query'][] = $tax_query;
	}
	if ( empty( $args['tax_query'] ) ) {
		$forced_terms = get_nested_value( $attributes, array( 'forcedCategories' ), array() );
		if ( ! is_array( $forced_terms ) ) {
			$forced_terms = array();
		}
		if ( ! empty( $forced_terms ) ) {
			foreach ( $forced_terms as $taxonomy => $term ) {
				$term = array_map( 'absint', $term );
				if ( empty( $term ) ) {
					continue;
				}
				$args['tax_query'][] = array(
					'taxonomy'         => $taxonomy,
					'field'            => 'id',
					'terms'            => $term,
					'operator'         => 'IN',
					'include_children' => true,
				);
			}
			$args['tax_query']['relation'] = 'OR';
		}
	}
	/**
	 * Filters the tax query for the dynamic archive block. (Includes the active filters)
	 *
	 * @param array $args The tax query args.
	 * @param array $attributes The attributes of the dynamic archive block.
	 * @param array $all_filters All currently active filters (Ids).
	 *
	 * @hooked jcore_dynamic_archive_tax_query
	 */
	return apply_filters( 'jcore_dynamic_archive_tax_query', $args, $attributes, $all_filters );
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
		$tax_object   = get_taxonomy( $taxonomy );
		$forced_terms = get_nested_value( $attributes, array( 'forcedCategories', $taxonomy ), array() );
		if ( ! is_array( $forced_terms ) ) {
			$forced_terms = array();
		}
		$forced_terms = array_map( 'absint', $forced_terms );
		$has_forced   = count( $forced_terms ) > 0;
		if ( ! $tax_object ) {
			continue;
		}
		$active_filters = get_nested_value( $all_filters, array( $taxonomy ), array() );
		if ( ! is_array( $active_filters ) ) {
			$active_filters = array( $active_filters );
		}
		$active_filters          = array_map( 'absint', $active_filters );
		$terms                   = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			)
		);
		$taxonomies[ $taxonomy ] = array(
			'name'            => $taxonomy,
			'label'           => apply_filters( 'jcore_dynamic_archive_taxonomy_label', $taxonomy, $attributes ),
			'filterType'      => get_nested_value( $attributes, array( 'filterTypes', $taxonomy ), 'checkbox' ),
			'filterTypeChild' => get_nested_value( $attributes, array( 'filterTypesChild', $taxonomy ), 'checkbox' ),
			'hierarchical'    => $tax_object->hierarchical ? get_nested_value( $attributes, array( 'hierarchicalFilter', $taxonomy ), false ) : false,
			'forcedTerms'     => $forced_terms,
			'terms'           => array(),
		);
		foreach ( $terms as $term ) {
			$filter_type = $taxonomies[ $taxonomy ]['filterType'] ?? 'checkbox';
			if ( ( $taxonomies[ $taxonomy ]['hierarchical'] ?? false ) && $term->parent > 0 ) {
				$filter_type = $taxonomies[ $taxonomy ]['filterTypeChild'] ?? 'checkbox';
			}
			// Handles only showing forced categories.
			if ( $has_forced && ! in_array( $term->term_id, $forced_terms, true ) ) {
				continue;
			}
			$taxonomies[ $taxonomy ]['terms'][] = array(
				'id'           => $term->term_id,
				'type'         => $term->taxonomy,
				'slug'         => $term->slug,
				'name'         => $term->name,
				'isChild'      => $term->parent > 0,
				'parent'       => $term->parent,
				'parentActive' => in_array( $term->parent, $active_filters, true ),
				'filterType'   => $filter_type,
				'active'       => in_array( $term->term_id, $active_filters, true ),
			);
		}
	}
	/**
	 * Filters the taxonomies filter for the dynamic archive block.
	 *
	 * @param array $taxonomies The taxonomies filter.
	 * @param array $attributes The attributes of the dynamic archive block.
	 * @param array $all_filters All currently active filters.
	 *
	 * @hooked jcore_dynamic_archive_taxonomies_filter
	 */
	return apply_filters( 'jcore_dynamic_archive_taxonomies_filter', $taxonomies, $attributes, $all_filters );
}

/**
 * Handles building pagination url.
 *
 * @param array $attributes The attributes of the dynamic archive block.
 * @param int   $page The page number.
 * @return string
 */
function build_pagination_url( array $attributes, int $page ): string {
	$url        = URLHelper::get_current_url();
	$param_name = rawurlencode( build_param_name( 'paged', $attributes['instanceId'] ?? '' ) );
	$url        = remove_query_arg( $param_name, $url );
	return rawurldecode( add_query_arg( $param_name, absint( $page ), $url ) );
}
