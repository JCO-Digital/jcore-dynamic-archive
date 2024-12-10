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

$args = array(
	'post_type'      => get_nested_value( $attributes, array( 'postTypes' ), array() ),
	'posts_per_page' => get_nested_value( $attributes, array( 'postsPerPage' ), 6 ),
	'order'          => get_nested_value( $attributes, array( 'order' ), 'desc' ),
	'orderby'        => get_nested_value( $attributes, array( 'orderBy' ), 'date' ),
);

$taxonomies_filter = get_nested_value( $attributes, array( 'selectedTaxonomies' ), array() );
if ( ! empty( $taxonomies_filter ) ) {
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
}

$context['posts']                    = Timber::get_posts( $args );
$context['block_wrapper_attributes'] = new FunctionWrapper( 'get_block_wrapper_attributes' );

Timber::render( 'latest-posts/latest-posts.twig', $context );
