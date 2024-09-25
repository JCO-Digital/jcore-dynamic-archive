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
use function Jcore\DynamicArchive\Helpers\build_taxonomies_filter;
use function Jcore\DynamicArchive\Helpers\handle_dynamic_args;
use function Jcore\DynamicArchive\Helpers\is_post_type;

$context          = Timber::context();
$context['block'] = $block;

$current_url = URLHelper::get_current_url();
$parsed_url  = wp_parse_url( $current_url, PHP_URL_PATH );
if ( $parsed_url === false ) {
	$parsed_url = $current_url;
}
$context['current_path'] = $parsed_url;

if ( ! is_post_type( $attributes['postType'] ?? '' ) ) {
	$attributes['postType'] = 'post';
}

$context['block_wrapper_attributes'] = new FunctionWrapper( 'get_block_wrapper_attributes' );
$context['attributes']               = $attributes;
$context['taxonomies_filter']        = build_taxonomies_filter( $attributes );

$args = array(
	'post_type'      => $attributes['postType'],
	'post__not_in'   => array( get_the_ID() ),
	'posts_per_page' => $attributes['perPage'] ?? get_site_option( 'posts_per_page', 10 ),
);

$args         = handle_dynamic_args( $args, $attributes );
$args         = apply_filters( 'jcore_dynamic_archive_args', $args, $attributes );
$timber_posts = Timber::get_posts(
	$args
);

$context['posts'] = $timber_posts;

Timber::render( 'dynamic-archive/archive.twig', $context );
