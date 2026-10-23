<?php
/**
 * The server-side rendering of the post picker block.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 *
 * @package JCORE\DynamicArchive
 */

use Timber\FunctionWrapper;
use function Jcore\DynamicArchive\Helpers\get_nested_value;

$context               = Timber::context();
$context['block']      = $block;
$context['attributes'] = $attributes;
$context['heading']    = wp_kses( (string) get_nested_value( $attributes, array( 'heading' ), '' ), array() );
$args                  = null;

if ( 'term' === get_nested_value( $attributes, array( 'mode' ), 'posts' ) ) {
	$taxonomy = get_taxonomy( (string) get_nested_value( $attributes, array( 'taxonomy' ), '' ) );
	$term     = $taxonomy ? get_term( (int) get_nested_value( $attributes, array( 'termId' ), 0 ), $taxonomy->name ) : null;

	if ( $taxonomy && $taxonomy->publicly_queryable && $term instanceof WP_Term ) {
		$args = array(
			'post_type'           => array_values( array_filter( $taxonomy->object_type, 'is_post_type_viewable' ) ),
			'posts_per_page'      => max( 1, (int) get_nested_value( $attributes, array( 'postsPerPage' ), 6 ) ),
			'order'               => 'asc' === get_nested_value( $attributes, array( 'order' ), 'desc' ) ? 'ASC' : 'DESC',
			'orderby'             => 'title' === get_nested_value( $attributes, array( 'orderBy' ), 'date' ) ? 'title' : 'date',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy'         => $taxonomy->name,
					'field'            => 'term_id',
					'terms'            => array( $term->term_id ),
					'include_children' => true,
				),
			),
		);
		// Don't list the post the block is shown on.
		if ( is_singular() && get_the_ID() ) {
			$args['post__not_in'] = array( get_the_ID() ); // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		}
	}
} else {
	$post_ids = array_values( array_filter( array_map( 'absint', (array) get_nested_value( $attributes, array( 'postIds' ), array() ) ) ) );

	if ( ! empty( $post_ids ) ) {
		$args = array(
			'post_type'           => array_values( array_filter( get_post_types(), 'is_post_type_viewable' ) ),
			'post__in'            => $post_ids,
			'orderby'             => 'post__in',
			'posts_per_page'      => count( $post_ids ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
	}
}

$found_posts = array();
if ( null !== $args ) {
	/**
	 * Filters the query args of the post picker block.
	 *
	 * @param array $args The query args.
	 * @param array $attributes The attributes of the post picker block.
	 *
	 * @hooked jcore_post_picker_args
	 */
	$args        = apply_filters( 'jcore_post_picker_args', $args, $attributes );
	$found_posts = Timber::get_posts( $args );
	if ( ! is_iterable( $found_posts ) ) {
		$found_posts = array();
	} elseif ( ! is_array( $found_posts ) ) {
		$found_posts = iterator_to_array( $found_posts );
	}
}

$context['posts']                    = $found_posts;
$context['block_wrapper_attributes'] = new FunctionWrapper( 'get_block_wrapper_attributes' );

Timber::render( 'post-picker/post-picker.twig', $context );
