<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace Jcore\DynamicArchive\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

const TERM_POST_TYPE_USAGE_CACHE_GROUP = 'jcore_dynamic_archive_term_pt';
const TERM_POST_TYPE_USAGE_VERSION_OPTION = 'jcore_dynamic_archive_term_pt_usage_version';

/**
 * Stores a value in the cache.
 *
 * @param string $key The cache key.
 * @param mixed $value The value to store.
 * @param int $ttl The cache TTL.
 *
 * @return void
 */
function store_value( string $key, mixed $value, int $ttl ): void {
	if ( ! wp_using_ext_object_cache() ) {
		set_transient( $key, $value, $ttl );
	} else {
		wp_cache_set( $key, $value, TERM_POST_TYPE_USAGE_CACHE_GROUP, $ttl );
	}
}

/**
 * Gets a value from the cache.
 *
 * @param string $key The cache key.
 *
 * @return mixed
 */
function get_value( string $key ): mixed {
	if ( ! wp_using_ext_object_cache() ) {
		return get_transient( $key );
	} else {
		return wp_cache_get( $key, TERM_POST_TYPE_USAGE_CACHE_GROUP );
	}
}

/**
 * Gets the cache key version for term usage by post type.
 *
 * @return int
 */
function get_term_post_type_usage_cache_version(): int {
	$version = absint( get_option( TERM_POST_TYPE_USAGE_VERSION_OPTION, 1 ) );
	if ( $version < 1 ) {
		$version = 1;
		update_option( TERM_POST_TYPE_USAGE_VERSION_OPTION, $version, false );
	}

	return $version;
}

/**
 * Builds a cache key for term usage by post type.
 *
 * @param string $taxonomy The taxonomy name.
 * @param string $post_type The post type name.
 *
 * @return string
 */
function get_term_post_type_usage_cache_key( string $taxonomy, string $post_type ): string {
	$version = get_term_post_type_usage_cache_version();
	return 'jcore_tpu_' . md5( $version . '|' . $taxonomy . '|' . $post_type );
}

/**
 * Gets the cache ttl for term usage by post type.
 *
 * @param string $taxonomy The taxonomy name.
 * @param string $post_type The post type name.
 *
 * @return int
 */
function get_term_post_type_usage_cache_ttl( string $taxonomy, string $post_type ): int {
	$ttl = (int) apply_filters(
		'jcore_dynamic_archive_term_post_type_usage_cache_ttl',
		DAY_IN_SECONDS,
		$taxonomy,
		$post_type
	);

	return $ttl > 0 ? $ttl : DAY_IN_SECONDS;
}

/**
 * Returns term IDs used by a specific post type within a taxonomy.
 *
 * @param string $taxonomy The taxonomy name.
 * @param string $post_type The post type name.
 *
 * @return int[]
 */
function get_term_ids_in_use_for_post_type( string $taxonomy, string $post_type ): array {
	global $wpdb;

	if ( ! taxonomy_exists( $taxonomy ) || ! post_type_exists( $post_type ) ) {
		return array();
	}

	$cache_key = get_term_post_type_usage_cache_key( $taxonomy, $post_type );
	$cached    = get_value( $cache_key );
	if ( is_array( $cached ) ) {
		return array_values( array_map( 'absint', $cached ) );
	}

	$post_statuses = array_keys( get_post_stati( array( 'public' => true ) ) );
	$post_statuses = apply_filters(
		'jcore_dynamic_archive_term_post_type_usage_post_statuses',
		$post_statuses,
		$taxonomy,
		$post_type
	);
	if ( ! is_array( $post_statuses ) || empty( $post_statuses ) ) {
		$post_statuses = array( 'publish' );
	}
	$post_statuses = array_values( array_filter( array_map( 'sanitize_key', $post_statuses ) ) );
	if ( empty( $post_statuses ) ) {
		$post_statuses = array( 'publish' );
	}

	$placeholders = implode( ', ', array_fill( 0, count( $post_statuses ), '%s' ) );
	$query        = "
		SELECT DISTINCT tt.term_id
		FROM {$wpdb->term_relationships} tr
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id
		WHERE tt.taxonomy = %s
		AND p.post_type = %s
		AND p.post_status IN ($placeholders)
	";
	$args         = array_merge( array( $taxonomy, $post_type ), $post_statuses );
	$prepared     = $wpdb->prepare( $query, $args );
	$term_ids     = $prepared ? $wpdb->get_col( $prepared ) : array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	$term_ids     = array_values( array_unique( array_map( 'absint', $term_ids ) ) );

	$ttl = get_term_post_type_usage_cache_ttl( $taxonomy, $post_type );
	store_value( $cache_key, $term_ids, $ttl );

	return $term_ids;
}

/**
 * Checks whether a term is in use for a specific post type.
 *
 * @param int    $term_id The term ID.
 * @param string $taxonomy The taxonomy name.
 * @param string $post_type The post type name.
 *
 * @return bool
 */
function term_is_in_use_for_post_type( int $term_id, string $taxonomy, string $post_type ): bool {
	return in_array( absint( $term_id ), get_term_ids_in_use_for_post_type( $taxonomy, $post_type ), true );
}

/**
 * Invalidates term usage cache for a taxonomy and post type pair.
 *
 * If taxonomy and post type are omitted, all keys are invalidated by bumping
 * the internal cache version.
 *
 * @param string|null $taxonomy The taxonomy name.
 * @param string|null $post_type The post type name.
 *
 * @return void
 */
function invalidate_term_post_type_usage_cache( ?string $taxonomy = null, ?string $post_type = null ): void {
	if ( null === $taxonomy && null === $post_type ) {
		update_option( TERM_POST_TYPE_USAGE_VERSION_OPTION, get_term_post_type_usage_cache_version() + 1, false );
		return;
	}

	if ( empty( $taxonomy ) || empty( $post_type ) ) {
		return;
	}

	$cache_key = get_term_post_type_usage_cache_key( $taxonomy, $post_type );
	delete_transient( $cache_key );
	wp_cache_delete( $cache_key, TERM_POST_TYPE_USAGE_CACHE_GROUP );
}

/**
 * Invalidates cache for all taxonomies attached to a post type.
 *
 * @param string $post_type The post type name.
 *
 * @return void
 */
function invalidate_term_post_type_usage_cache_for_post_type( string $post_type ): void {
	if ( ! post_type_exists( $post_type ) ) {
		return;
	}

	$taxonomies = get_object_taxonomies( $post_type, 'names' );
	foreach ( $taxonomies as $taxonomy ) {
		invalidate_term_post_type_usage_cache( $taxonomy, $post_type );
	}
}

/**
 * Handles cache invalidation on post save.
 *
 * @param int           $post_id The post ID.
 * @param \WP_Post|null $post The post object.
 * @param bool     $update Whether this is an existing post being updated.
 *
 * @return void
 */
function handle_term_post_type_usage_save_post( int $post_id, \WP_Post $post, bool $update ): void {
	unset( $update );

	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	invalidate_term_post_type_usage_cache_for_post_type( $post->post_type );
}

/**
 * Handles cache invalidation on post deletion.
 *
 * @param int      $post_id The post ID.
 * @param \WP_Post $post The post object.
 *
 * @return void
 */
function handle_term_post_type_usage_deleted_post( int $post_id, ?\WP_Post $post = null ): void {
	$post_type = $post instanceof \WP_Post ? $post->post_type : get_post_type( $post_id );
	if ( ! is_string( $post_type ) || '' === $post_type ) {
		return;
	}

	invalidate_term_post_type_usage_cache_for_post_type( $post_type );
}

/**
 * Handles cache invalidation on term assignment changes.
 *
 * @param int          $object_id The object ID.
 * @param int[]|string $terms The terms.
 * @param int[]        $tt_ids The term taxonomy IDs.
 * @param string       $taxonomy The taxonomy.
 * @param bool         $append Whether to append terms.
 * @param int[]        $old_tt_ids Old term taxonomy IDs.
 *
 * @return void
 */
function handle_term_post_type_usage_set_object_terms( int $object_id, $terms, array $tt_ids, string $taxonomy, bool $append, array $old_tt_ids ): void {
	unset( $terms, $tt_ids, $append, $old_tt_ids );

	$post_type = get_post_type( $object_id );
	if ( ! is_string( $post_type ) || '' === $post_type ) {
		return;
	}

	invalidate_term_post_type_usage_cache( $taxonomy, $post_type );
}

add_action( 'save_post', __NAMESPACE__ . '\\handle_term_post_type_usage_save_post', 10, 3 );
add_action( 'deleted_post', __NAMESPACE__ . '\\handle_term_post_type_usage_deleted_post', 10, 2 );
add_action( 'before_delete_post', __NAMESPACE__ . '\\handle_term_post_type_usage_deleted_post', 10, 2 );
add_action( 'set_object_terms', __NAMESPACE__ . '\\handle_term_post_type_usage_set_object_terms', 10, 6 );
