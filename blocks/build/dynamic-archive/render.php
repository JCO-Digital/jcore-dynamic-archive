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
use function Jcore\DynamicArchive\Helpers\build_param_name;
use function Jcore\DynamicArchive\Helpers\build_taxonomies_filter;
use function Jcore\DynamicArchive\Helpers\get_parameter;
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

$block_per_page = $attributes['perPage'] ?? get_site_option( 'posts_per_page', 10 );
$args           = array(
	'post_type'      => $attributes['postType'],
	'post__not_in'   => array( get_the_ID() ),
	'posts_per_page' => $block_per_page,
);

$args = handle_dynamic_args( $args, $attributes );
$args = apply_filters( 'jcore_dynamic_archive_args', $args, $attributes );

// If pagination is enabled and not "load more", we need to subtract 1 from the posts per page, as we do not need to check for more posts.
if ( ( $attributes['showPagination'] ?? false ) && ! ( $attributes['infiniteScroll'] ?? false ) ) {
	$args['posts_per_page'] -= 1;
}
$timber_posts = Timber::get_posts(
	$args
);

$current_page            = get_parameter( build_param_name( 'paged', $attributes['instanceId'] ?? '' ), 1 );
$has_more                = count( $timber_posts ) > ( $block_per_page * absint( $current_page ) );
$context['has_more']     = $has_more;
$context['current_page'] = $current_page;
// If pagination is enabled and "load more", we need to slice the posts array to remove the last post, as we do not need to check for more posts.
if ( ! is_null( $timber_posts ) && ( $attributes['showPagination'] ?? false ) && ( $attributes['infiniteScroll'] ?? false ) && $has_more ) {
	$final_posts = array_slice( $timber_posts->to_array(), 0, - 1 );
}
$context['posts'] = $final_posts ?? $timber_posts;

$taxonomy_key = build_param_name( 'taxonomy', $attributes['instanceId'] ?? '' );

$interactivity_context = array(
	'currentPage' => $current_page ?? 1,
	'filters'     => array(
		$taxonomy_key => get_parameter( $taxonomy_key ),
	),
	'blockId'     => $attributes['instanceId'],
);

wp_interactivity_state(
	'jcore/dynamic-archive',
	array()
);

$context['interactivity_context_attribute'] = wp_interactivity_data_wp_context( $interactivity_context, 'jcore/dynamic-archive' );

$rendered = Timber::compile( 'dynamic-archive/archive.twig', $context );

echo wp_interactivity_process_directives( $rendered ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
