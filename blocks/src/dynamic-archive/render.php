<?php
/**
 * The server-side rendering of the dynamic archive block.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 *
 * @package JCORE\DynamicArchive
 */

use Timber\FunctionWrapper;
use Timber\URLHelper;

$context               = Timber::context();
$context['attributes'] = $attributes;
$context['block']      = $block;

if ( ! is_post_type( $attributes['postType'] ?? '' ) ) {
	$attributes['postType'] = 'post';
}

$context['block_wrapper_attributes'] = new FunctionWrapper( 'get_block_wrapper_attributes' );

$args = array(
	'post_type'      => $attributes['postType'],
	'post__not_in'   => array( get_the_ID() ),
	'posts_per_page' => $attributes['perPage'] ?? get_site_option( 'posts_per_page', 10 ),
);

$context['current_url'] = URLHelper::get_current_url();

$timber_posts = Timber::get_posts(
	$args
);

$context['posts'] = $timber_posts;

Timber::render( 'dynamic-archive/archive.twig', $context );
