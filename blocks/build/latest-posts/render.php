<?php
/**
 * The server-side rendering of the latest posts block.
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

if ( $attributes['related'] ) {
	$args = array(
		'post_type'      => get_post_type( get_the_ID() ),
		'posts_per_page' => get_nested_value( $attributes, array( 'postsPerPage' ), 6 ),
		'order'          => get_nested_value( $attributes, array( 'order' ), 'desc' ),
		'orderby'        => get_nested_value( $attributes, array( 'orderBy' ), 'date' ),
		'post__not_in'   => array( get_the_ID() ),
	);
} else {
	$args = array(
		'post_type'      => get_nested_value( $attributes, array( 'postTypes' ), array() ),
		'posts_per_page' => get_nested_value( $attributes, array( 'postsPerPage' ), 6 ),
		'order'          => get_nested_value( $attributes, array( 'order' ), 'desc' ),
		'orderby'        => get_nested_value( $attributes, array( 'orderBy' ), 'date' ),
	);
}


$taxonomies_filter = get_nested_value( $attributes, array( 'selectedTaxonomies' ), array() );
if ( ! empty( $taxonomies_filter ) && ! $attributes['related'] ) {
	$args['tax_query'] = array();
	foreach ( $taxonomies_filter as $slug => $terms ) {
		if ( empty( $terms ) ) {
			continue;
		}
		$args['tax_query'][] = array(
			'taxonomy'         => $slug,
			'field'            => 'id',
			'terms'            => $terms,
			'include_children' => true,
		);
	}
	if ( empty( $args['tax_query'] ) ) {
		unset( $args['tax_query'] );
	}
} elseif ( $attributes['related'] ) {
	$existing_taxonomies = get_object_taxonomies( get_post_type( get_the_ID() ), 'objects' );
	foreach ( $existing_taxonomies as $taxo ) {
		if ( ! $taxo->public || ! $taxo->publicly_queryable ) {
			continue;
		}
		$current_terms = get_the_terms( get_the_ID(), $taxo->name );
		if ( empty( $current_terms ) || is_wp_error( $current_terms ) ) {
			continue;
		}
		if ( ! isset( $args['tax_query'] ) ) {
			$args['tax_query'] = array();
		}
		$args['tax_query'][] = array(
			'taxonomy'         => $taxo->name,
			'field'            => 'id',
			'terms'            => wp_list_pluck( $current_terms, 'term_id' ),
			'include_children' => true,
		);
	}
}

if ( isset( $attributes['sticky'] ) ) {
	$args_to_add = match ( $attributes['sticky'] ) {
		'exclude' => array(
			'post__not_in' => get_option( 'sticky_posts' ),
		),
		'only' => array(
			'post__in'            => get_option( 'sticky_posts' ),
			'ignore_sticky_posts' => true, // This might seem redundant, but all it does is improve performance. See https://wordpress.stackexchange.com/questions/260941/why-ignore-sticky-posts-argument-is-in-sticky-post-query#:~:text=Explanation%20of%20the%20codex%20example%3A for more information.
		),
		default => array(),
	};
	$args = array_merge( $args, $args_to_add );
	if ( $attributes['related'] && isset( $args_to_add['post__not_in'] ) ) {
		$args['post__not_in'] = array_merge( $args['post__not_in'], $args_to_add['post__not_in'] );
	}
}


/**
 * Filters the latest posts args for the latest posts block.
 *
 * @param array $args The latest posts args.
 * @param array $attributes The attributes of the latest posts block.
 *
 * @hooked jcore_latest_posts_args
 */
$args                                = apply_filters( 'jcore_latest_posts_args', $args, $attributes );
$context['posts']                    = Timber::get_posts( $args );
$context['block_wrapper_attributes'] = new FunctionWrapper( 'get_block_wrapper_attributes' );

Timber::render( 'latest-posts/latest-posts.twig', $context );
