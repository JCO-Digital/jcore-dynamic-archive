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
 * @param array       $args The arguments to handle.
 * @param array       $attributes The attributes of the dynamic archive block.
 * @param string|null $skip_taxonomy The taxonomy to skip (for faceted filters).
 *
 * @return array
 */
function handle_dynamic_args( array $args, array $attributes, ?string $skip_taxonomy = null ): array {
	$instance_id = $attributes['instanceId'] ?? '';
	if ( ( $attributes['showPagination'] ?? false ) && ! ( $attributes['infiniteScroll'] ?? false ) ) {
		$args['paged'] = get_parameter( build_param_name( 'archive-paged', $instance_id, $attributes ), 1 );
	} elseif ( ( $attributes['showPagination'] ?? false ) && ( $attributes['infiniteScroll'] ?? false ) ) {
		$paged                   = get_parameter( build_param_name( 'archive-paged', $instance_id, $attributes ), 1 );
		$args['posts_per_page'] *= $paged;
	}

	$sort = get_parameter( build_param_name( 'sort', $instance_id, $attributes ) );
	if ( $sort ) {
		if ( str_starts_with( $sort, 'tax:' ) ) {
			$tax_sort = str_replace( 'tax:', '', $sort );
			if ( str_contains( $tax_sort, '-' ) ) {
				$parts                                       = explode( '-', $tax_sort );
				$args['jcore_dynamic_archive_sort_taxonomy'] = $parts[0];
				$args['jcore_dynamic_archive_sort_order']    = strtoupper( $parts[1] );
			} else {
				$args['jcore_dynamic_archive_sort_taxonomy'] = $tax_sort;
				$args['jcore_dynamic_archive_sort_order']    = 'ASC';
			}
			// Set a default orderby so WP_Query generates an ORDER BY clause we can prepend to.
			$args['orderby'] = 'post_date';
			$args['order']   = $args['jcore_dynamic_archive_sort_order'];
		} elseif ( str_contains( $sort, '-' ) ) {
			$parts           = explode( '-', $sort );
			$args['orderby'] = $parts[0];
			$args['order']   = strtoupper( $parts[1] );
		} else {
			$args['orderby'] = $sort;
			$args['order']   = 'ASC';
		}

		// Ensure orderby values are compatible with WP_Query.
		if ( 'date' === $args['orderby'] ) {
			$args['orderby'] = 'post_date';
		} elseif ( 'title' === $args['orderby'] ) {
			$args['orderby'] = 'post_title';
		}
	} else {
		$order = get_parameter( build_param_name( 'order', $instance_id, $attributes ), $attributes['order'] ?? 'desc' );
		$order = match ( strtolower( $order ) ) {
			'asc'   => 'asc',
			default => 'desc',
		};

		$args['order']   = strtoupper( $order );
		$args['orderby'] = get_parameter( build_param_name( 'orderby', $instance_id, $attributes ), $attributes['orderBy'] ?? 'date' );

		// Ensure orderby values are compatible with WP_Query.
		if ( 'date' === $args['orderby'] ) {
			$args['orderby'] = 'post_date';
		} elseif ( 'title' === $args['orderby'] ) {
			$args['orderby'] = 'post_title';
		}
	}

	// Load all posts regardless of language.
	if ( $attributes['showAllLanguages'] ) {
		$args['lang'] = '';
	}

	if ( $attributes['search'] ) {
		$search = get_parameter( build_param_name( 'search', $instance_id, $attributes ), false );
		if ( $search ) {
			$args['s']          = sanitize_text_field( $search );
			$args['relevanssi'] = true;
		}
	}

	$args = handle_taxonomies_filter( $args, $attributes, $skip_taxonomy );

	return handle_taxonomy_sorting( $args, $attributes );
}

/**
 * Handles generating the taxonomies filter for the dynamic archive block.
 *
 * @param array       $args The arguments to handle.
 * @param array       $attributes The attributes of the dynamic archive block.
 * @param string|null $skip_taxonomy The taxonomy to skip (for faceted filters).
 *
 * @return array
 */
function handle_taxonomies_filter( array $args, array $attributes, ?string $skip_taxonomy = null ): array {
	$instance_id = $attributes['instanceId'] ?? '';
	$all_filters = get_parameter( build_param_name( 'taxonomy', $instance_id, $attributes ), array() );

	[$taxonomy_filters] = extract_taxonomy_filter_attributes( $attributes );

	foreach ( $taxonomy_filters ?? array() as $taxonomy ) {
		if ( $taxonomy === $skip_taxonomy ) {
			continue;
		}
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

		if ( $attributes['showAllLanguages'] && function_exists( 'pll_get_term_translations' ) ) {
			$new_filters = array();
			foreach ( $active_filters as $term_id ) {
				$translations = pll_get_term_translations( $term_id );
				if ( is_array( $translations ) ) {
					foreach ( $translations as $translation ) {
						$new_filters[] = $translation;
					}
				}
			}
			$active_filters = array_unique( array_merge( $active_filters, $new_filters ) );
		}

		$tax_query = array(
			'taxonomy'         => $taxonomy,
			'field'            => 'id',
			'terms'            => $active_filters,
			'include_children' => count( $has_active_child ) === 0,
		);

		if ( count( $active_filters ) > 1 ) {
			$tax_query['operator'] = 'IN';
		}

		$args['tax_query'][] = $tax_query;
	}
	if ( empty( $args['tax_query'] ) ) {
		$forced_terms = get_nested_value( $attributes, array( 'forcedCategories' ), array() );
		if ( ! is_array( $forced_terms ) || $attributes['inherit'] ) {
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
			$args['tax_query']['relation'] = apply_filters( 'jcore_dynamic_archive_tax_query_relation', 'OR', $args, $attributes, $all_filters );
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
 * Handles sorting by taxonomy.
 *
 * @param array $args The arguments to handle.
 * @param array $attributes The attributes of the dynamic archive block.
 *
 * @return array
 */
function handle_taxonomy_sorting( array $args, array $attributes ): array {
	if ( ! isset( $args['jcore_dynamic_archive_sort_taxonomy'] ) ) {
		return $args;
	}

	/**
	 * Filters the arguments for taxonomy sorting.
	 *
	 * @param array $args The arguments to handle.
	 * @param array $attributes The attributes of the dynamic archive block.
	 */
	return apply_filters( 'jcore_dynamic_archive_handle_taxonomy_sorting', $args, $attributes );
}

add_filter(
	'posts_clauses',
	function ( $clauses, $query ) {
		global $wpdb;
		$taxonomy = $query->get( 'jcore_dynamic_archive_sort_taxonomy' );
		$order    = strtoupper( $query->get( 'jcore_dynamic_archive_sort_order' ) ?: 'ASC' );
		if ( $taxonomy ) {
			$clauses['join'] .= " LEFT JOIN {$wpdb->term_relationships} AS jcore_tr ON {$wpdb->posts}.ID = jcore_tr.object_id";
			$clauses['join'] .= " LEFT JOIN {$wpdb->term_taxonomy} AS jcore_tt ON jcore_tr.term_taxonomy_id = jcore_tt.term_taxonomy_id AND jcore_tt.taxonomy = " . $wpdb->prepare( '%s', $taxonomy );
			$clauses['join'] .= " LEFT JOIN {$wpdb->terms} AS jcore_t ON jcore_tt.term_id = jcore_t.term_id";

			if ( empty( $clauses['groupby'] ) ) {
				$clauses['groupby'] = "{$wpdb->posts}.ID";
			} elseif ( ! str_contains( $clauses['groupby'], "{$wpdb->posts}.ID" ) ) {
				$clauses['groupby'] .= ", {$wpdb->posts}.ID";
			}

			$aggregate   = 'ASC' === $order ? 'MIN' : 'MAX';
			$tax_orderby = "$aggregate(jcore_t.name) $order";
			if ( ! empty( $clauses['orderby'] ) ) {
				$clauses['orderby'] = "$tax_orderby, {$clauses['orderby']}";
			} else {
				$clauses['orderby'] = $tax_orderby;
			}
		}
		return $clauses;
	},
	10,
	2
);

/**
 * Builds base WP_Query args for the dynamic archive block (before handle_dynamic_args).
 *
 * Mirrors {@see blocks/src/dynamic-archive/render.php}: defaults, singular exclusion, inherit merge,
 * hierarchical post_parent, sticky handling.
 *
 * @param array $attributes Block attributes (postType normalized; may be updated when inherit is true).
 *
 * @return array{0: array, 1: array} [ $attributes, $args ]
 */
function build_dynamic_archive_block_base_args( array $attributes ): array {
	if ( ! is_post_type( $attributes['postType'] ?? '' ) ) {
		$attributes['postType'] = 'post';
	}

	$block_per_page = $attributes['perPage'] ?? get_site_option( 'posts_per_page', 10 );
	$args           = array(
		'post_type'      => $attributes['postType'],
		'posts_per_page' => $block_per_page,
		'post_status'    => 'publish',
	);

	if ( is_singular() ) {
		$args['post__not_in'] = array( get_the_ID() );
	}

	if ( ! empty( $attributes['inherit'] ) ) {
		$inherited = get_inherited_query_args();

		if ( ! empty( $inherited['post_type'] ) ) {
			$args['post_type']      = $inherited['post_type'];
			$attributes['postType'] = is_array( $inherited['post_type'] ) ? $inherited['post_type'][0] : $inherited['post_type'];
		}

		if ( ! empty( $inherited['tax_query'] ) ) {
			$args['tax_query'] = array_merge( $args['tax_query'] ?? array(), $inherited['tax_query'] );
		}

		if ( ! empty( $inherited['author'] ) ) {
			$args['author'] = $inherited['author'];
		}

		if ( ! ( $attributes['search'] ?? false ) && ! empty( $inherited['s'] ) ) {
			$args['s'] = $inherited['s'];
		}

		foreach ( array( 'year', 'monthnum', 'day' ) as $date_key ) {
			if ( ! empty( $inherited[ $date_key ] ) ) {
				$args[ $date_key ] = $inherited[ $date_key ];
			}
		}

		$selected_post_type = get_post_type_object( $attributes['postType'] );
		if ( ! $selected_post_type ) {
			$selected_post_type = get_post_type_object( 'post' );
		}
		$attributes['postType'] = $selected_post_type->name;
	} else {
		$selected_post_type = get_post_type_object( $attributes['postType'] );
		if ( ! $selected_post_type ) {
			$selected_post_type = get_post_type_object( 'post' );
		}
	}

	if ( $selected_post_type->hierarchical && ( $attributes['hideChildren'] ?? false ) === true ) {
		$args['post_parent'] = 0;
	}

	if ( isset( $attributes['sticky'] ) || ( ! empty( $attributes['inherit'] ) && apply_filters( 'jcore_dynamic_archive_inherit_sticky', false ) ) ) {
		$args_to_add = match ( $attributes['sticky'] ?? 'include' ) {
			'exclude' => array(
				'post__not_in' => get_option( 'sticky_posts' ),
			),
			'only' => array(
				'post__in'            => get_option( 'sticky_posts' ),
				'ignore_sticky_posts' => true,
			),
			default => array(),
		};
		$args = array_merge( $args, $args_to_add );
	}

	return array( $attributes, $args );
}

/**
 * Strips pagination and limits for enumerating all matching post IDs (faceted term discovery).
 *
 * @param array $args WP_Query arguments.
 *
 * @return array
 */
function normalize_dynamic_archive_query_args_for_facet( array $args ): array {
	unset( $args['paged'] );
	$args['posts_per_page']         = -1;
	$args['fields']                 = 'ids';
	$args['no_found_rows']          = true;
	$args['update_post_meta_cache'] = false;
	$args['update_post_term_cache'] = false;
	return $args;
}

/**
 * Returns term IDs that appear on at least one post matching the current query, excluding URL filters for $taxonomy.
 *
 * @param string $taxonomy   Taxonomy slug.
 * @param array  $attributes Block attributes (same merge as main query).
 *
 * @return int[] Distinct term IDs.
 */
function get_applicable_term_ids_for_taxonomy( string $taxonomy, array $attributes ): array {
	[ $merged_attributes, $base_args ] = build_dynamic_archive_block_base_args( $attributes );
	$facet_args                        = handle_dynamic_args( $base_args, $merged_attributes, $taxonomy );
	$facet_args                        = apply_filters( 'jcore_dynamic_archive_args', $facet_args, $merged_attributes );
	$facet_args                        = normalize_dynamic_archive_query_args_for_facet( $facet_args );
	/**
	 * Filters WP_Query args used to discover applicable terms for a taxonomy (faceted filters).
	 *
	 * @param array  $facet_args        Query args (post IDs enumeration).
	 * @param string $taxonomy          Taxonomy being faceted.
	 * @param array  $merged_attributes Block attributes after inherit merge.
	 */
	$facet_args = apply_filters( 'jcore_dynamic_archive_facet_term_query_args', $facet_args, $taxonomy, $merged_attributes );

	$query = new \WP_Query( $facet_args );
	$ids   = $query->posts;

	if ( empty( $ids ) ) {
		return array();
	}

	global $wpdb;
	$ids_list = implode( ',', array_map( 'intval', $ids ) );
	$taxonomy_sql = $wpdb->prepare( '%s', $taxonomy );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$term_ids = $wpdb->get_col(
		"SELECT DISTINCT tt.term_id
         FROM {$wpdb->term_relationships} tr
         JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
         WHERE tr.object_id IN ($ids_list) AND tt.taxonomy = $taxonomy_sql"
	);

	return array_map( 'intval', $term_ids );
}

/**
 * Builds the final WP_Query arguments for the dynamic archive block.
 *
 * @param array $attributes The attributes of the dynamic archive block.
 *
 * @return array The final WP_Query arguments.
 */
function build_dynamic_archive_query_args( array $attributes ): array {
	[ $merged_attributes, $base_args ] = build_dynamic_archive_block_base_args( $attributes );
	$args                              = handle_dynamic_args( $base_args, $merged_attributes );
	return apply_filters( 'jcore_dynamic_archive_args', $args, $merged_attributes );
}

/**
 * Builds a parameter name for the dynamic archive block, based on the instance id and the parameter name.
 *
 * @param string     $name The name of the parameter.
 * @param int|string $instance_id The instance id of the dynamic archive block.
 * @param array      $attributes The block attributes (for fetching the prefix).
 *
 * @return string
 */
function build_param_name( string $name, int|string $instance_id, array $attributes = array() ): string {
	$prefix = 'dynamic-archive-' . $instance_id;

	/**
	 * Filters the parameter name prefix for the dynamic archive block.
	 *
	 * @param string $prefix The parameter name prefix.
	 * @param int|string $instance_id The instance id of the dynamic archive block.
	 * @param array $attributes The block attributes.
	 */
	$prefix = apply_filters( 'jcore_dynamic_archive_param_prefix', $prefix, $instance_id, $attributes );

	return $prefix . ( empty( $name ) ? '' : '-' . $name );
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
 * Extracts taxonomy filter attributes from the given attributes array, applying overrides if 'inherit' is set.
 *
 * @param array $attributes The attributes of the dynamic archive block.
 *
 * @return array An array containing taxonomy_filters, filter_types, filter_types_child, hierarchical_filter.
 */
function extract_taxonomy_filter_attributes( array $attributes ): array {
	$taxonomy_filters    = get_nested_value( $attributes, array( 'taxonomies' ), array() );
	$filter_types        = get_nested_value( $attributes, array( 'filterTypes' ), array() );
	$filter_types_child  = get_nested_value( $attributes, array( 'filterTypesChild' ), array() );
	$hierarchical_filter = get_nested_value( $attributes, array( 'hierarchicalFilter' ), array() );

	if ( $attributes['inherit'] ) {
		// Override the taxonomy filters with developer defined filters.
		/**
		 * Filters the taxonomy filters for the dynamic archive block.
		 *
		 * @param array $attributes The attributes of the dynamic archive block.
		 *
		 * @hooked jcore_dynamic_archive_taxonomies_inherit
		 *
		 * The return value should be in this form:
		 *
		 * array(
		 *  'taxonomies' => array(
		 *   'taxonomy_name'
		 *  ),
		 *  'filterTypes' => array(
		 *   'taxonomy_name' => 'filter_type',
		 *  ),
		 *  'filterTypesChild' => array(
		 *   'taxonomy_name' => 'filter_type',
		 *  ),
		 *  'hierarchicalFilter' => array(
		 *   'taxonomy_name' => true|false
		 *  ),
		 * );
		 */
		$overrides = apply_filters( 'jcore_dynamic_archive_taxonomies_inherit', array(), $attributes );
		if ( ! is_array( $overrides ) ) {
			$overrides = array();
		}
		$taxonomy_filters    = get_nested_value( $overrides, array( 'taxonomies' ), array() );
		$filter_types        = get_nested_value( $overrides, array( 'filterTypes' ), array() );
		$filter_types_child  = get_nested_value( $overrides, array( 'filterTypesChild' ), array() );
		$hierarchical_filter = get_nested_value( $overrides, array( 'hierarchicalFilter' ), array() );
	}

	return array( $taxonomy_filters, $filter_types, $filter_types_child, $hierarchical_filter );
}

/**
 * Handles constructing the taxonomies filter for the dynamic archive block.
 *
 * @param array $attributes The attributes of the dynamic archive block.
 * @param array $base_args The base query arguments.
 *
 * @return array
 */
function build_taxonomies_filter( array $attributes, array $base_args = array() ): array {
	// Extract the taxonomy filters, filter types, filter types child, and hierarchical filter from the attributes.
	[$taxonomy_filters, $filter_types, $filter_types_child, $hierarchical_filter] = extract_taxonomy_filter_attributes( $attributes );

	$taxonomies  = array();
	$instance_id = $attributes['instanceId'] ?? '';
	$all_filters = get_parameter( build_param_name( 'taxonomy', $instance_id, $attributes ), array() );

	foreach ( $taxonomy_filters as $taxonomy ) {
		// Get taxonomy object.
		$tax_object   = get_taxonomy( $taxonomy );
		$forced_terms = get_nested_value( $attributes, array( 'forcedCategories', $taxonomy ), array() );
		if ( ! is_array( $forced_terms ) || $attributes['inherit'] ) {
			$forced_terms = array();
		}
		$forced_terms = array_map( 'absint', $forced_terms );
		$has_forced   = count( $forced_terms ) > 0;
		if ( ! $tax_object ) {
			continue;
		}

		$applicable_term_ids = get_applicable_term_ids_for_taxonomy( $taxonomy, $attributes );

		$active_filters = get_nested_value( $all_filters, array( $taxonomy ), array() );
		if ( ! is_array( $active_filters ) ) {
			$active_filters = array( $active_filters );
		}
		$active_filters = array_map( 'absint', $active_filters );
		$terms          = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => ! $attributes['showAllLanguages'], // Show empty if all languages are shown.
			)
		);
		$taxonomies[ $taxonomy ] = array(
			'name'            => $tax_object->name,
			'label'           => $tax_object->label,
			'filterType'      => get_nested_value( $filter_types, array( $taxonomy ), 'checkbox' ),
			'filterTypeChild' => get_nested_value( $filter_types_child, array( $taxonomy ), 'checkbox' ),
			'hierarchical'    => $tax_object->hierarchical ? get_nested_value( $hierarchical_filter, array( $taxonomy ), false ) : false,
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
				'disabled'     => ! in_array( (int) $term->term_id, $applicable_term_ids, true ),
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
 * Returns the post type for the taxonomies filter, based on the attributes.
 *
 * @param array $attributes The block attributes.
 *
 * @return string The post type.
 */
function get_taxonomies_filter_post_type( array $attributes ): string {
	if ( ! empty( $attributes['inherit'] ) ) {
		$inherited = get_inherited_query_args();
		if ( ! empty( $inherited['post_type'] ) ) {
			return is_array( $inherited['post_type'] ) ? $inherited['post_type'][0] : $inherited['post_type'];
		}
	}

	return $attributes['postType'] ?? 'post';
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
	$param_name = rawurlencode( build_param_name( 'archive-paged', $attributes['instanceId'] ?? '', $attributes ) );
	$url        = remove_query_arg( $param_name, $url );
	return rawurldecode( add_query_arg( $param_name, absint( $page ), $url ) );
}

/**
 * Returns the filter field type for the taxonomy (slug or id).
 *
 * @param array $attributes The block attributes.
 * @return string 'slug' or 'id'.
 */
function get_taxonomy_param_field_type( array $attributes ): string {
	return apply_filters( 'jcore_dynamic_archive_taxonomy_field_type', 'id', $attributes );
}

/**
 * Returns WP_Query-compatible arguments for the current main query (e.g. on a term or author archive).
 *
 * @return array
 */
function get_inherited_query_args(): array {
	$args = array();

	if ( is_category() || is_tag() || is_tax() ) {
		$obj                = get_queried_object();
		$args['post_type']  = get_post_type() ?: 'post';
		$args['tax_query']  = array(
			array(
				'taxonomy' => $obj->taxonomy,
				'field'    => 'id',
				'terms'    => $obj->term_id,
			),
		);
		$args['post_type']  = get_taxonomy( $obj->taxonomy )->object_type;
	} elseif ( is_author() ) {
		$args['post_type'] = get_post_type() ?: 'post';
		$args['author']    = get_queried_object_id();
	} elseif ( is_post_type_archive() ) {
		$args['post_type'] = get_queried_object()->name;
	} elseif ( is_home() ) {
		$args['post_type'] = 'post';
	} elseif ( is_search() ) {
		$args['s'] = get_search_query();
	}

	if ( is_date() ) {
		if ( is_year() ) {
			$args['year'] = get_query_var( 'year' );
		}
		if ( is_month() ) {
			$args['monthnum'] = get_query_var( 'monthnum' );
		}
		if ( is_day() ) {
			$args['day'] = get_query_var( 'day' );
		}
	}

	/**
	 * Filters the inherited query args for the dynamic archive block.
	 *
	 * @param array $args The inherited query args.
	 */
	return apply_filters( 'jcore_dynamic_archive_inherited_query_args', $args );
}

/**
 * Handles generating the sort options for the dynamic archive block.
 *
 * @param array $attributes The attributes of the dynamic archive block.
 *
 * @return array
 */
function build_sort_options( array $attributes ): array {
	if ( ! ( $attributes['showSort'] ?? false ) ) {
		return array();
	}
	$instance_id      = $attributes['instanceId'] ?? '';
	$selected_options = $attributes['sortOptions'] ?? array();
	$current_sort     = get_parameter( build_param_name( 'sort', $instance_id, $attributes ) );

	$all_options = array(
		'date-DESC'       => __( 'Date ↓', 'jcore-dynamic-archive' ),
		'date-ASC'        => __( 'Date ↑', 'jcore-dynamic-archive' ),
		'post_title-ASC'  => __( 'Title ↑', 'jcore-dynamic-archive' ),
		'post_title-DESC' => __( 'Title ↓', 'jcore-dynamic-archive' ),
	);

	// Add taxonomies.
	$taxonomies = get_object_taxonomies( $attributes['postType'], 'objects' );
	foreach ( $taxonomies as $taxonomy ) {
		$all_options[ 'tax:' . $taxonomy->name . '-ASC' ]  = $taxonomy->label . ' ↑';
		$all_options[ 'tax:' . $taxonomy->name . '-DESC' ] = $taxonomy->label . ' ↓';
	}

	$sort_options = array();
	foreach ( $selected_options as $option_value ) {
		if ( isset( $all_options[ $option_value ] ) ) {
			$sort_options[] = array(
				'value'    => $option_value,
				'label'    => $all_options[ $option_value ],
				'selected' => $current_sort === $option_value,
			);
		}
	}

	return $sort_options;
}
