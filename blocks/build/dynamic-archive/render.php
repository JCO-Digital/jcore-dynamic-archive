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
use function Jcore\DynamicArchive\Helpers\build_pagination_url;
use function Jcore\DynamicArchive\Helpers\build_param_name;
use function Jcore\DynamicArchive\Helpers\build_taxonomies_filter;
use function Jcore\DynamicArchive\Helpers\get_nested_value;
use function Jcore\DynamicArchive\Helpers\get_parameter;
use function Jcore\DynamicArchive\Helpers\handle_dynamic_args;
use function Jcore\DynamicArchive\Helpers\is_post_type;

$context          = Timber::context();
$context['block'] = $block;

if ( is_admin() ) {
	print( '<div class="jcore-dynamic-archive-block-editor"></div>' );
}

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

$args = handle_dynamic_args( $args, $attributes );
/**
 * Filters the dynamic archive args for the dynamic archive block.
 *
 * @param array $args The dynamic archive args.
 * @param array $attributes The attributes of the dynamic archive block.
 *
 * @hooked jcore_dynamic_archive_args
 */
$args         = apply_filters( 'jcore_dynamic_archive_args', $args, $attributes );
$timber_posts = Timber::get_posts(
	$args
);

$current_page            = absint( get_parameter( build_param_name( 'paged', $attributes['instanceId'] ?? '' ), 1 ) );
$context['current_page'] = $current_page;

$total_pages = absint( ceil( $timber_posts->found_posts / $block_per_page ) );
// If pagination is enabled and not "load more", we need to subtract 1 from the posts per page, as we do not need to check for more posts.
if ( ( $attributes['showPagination'] ?? false ) && ! ( $attributes['infiniteScroll'] ?? false ) ) {
	$pagination = array();
	for ( $i = 1; $i <= $total_pages; $i++ ) {
		$pagination[] = array(
			'number'  => $i,
			'title'   => $i,
			'current' => $i === $current_page,
			'href'    => build_pagination_url( $attributes, $i ),
		);
	}
	$dots = array(
		'title' => '...',
		'type'  => 'dots',
	);
	if ( count( $pagination ) > 6 ) {
		// We cut so we get the first page, the current +- 2 and the last.
		$old_pagination = $pagination;
		if ( $current_page > 3 ) {
			// We are now in the middle/end of the pagination.
			// First we add the first page.
			$pagination = array( ...array_slice( $old_pagination, 0, 1 ) );
			if ( $current_page < $total_pages - 2 ) {
				// We are not at the end of the pagination.
				// We add the previously added first page, the current pages (+/- 2) and the last page.
				$pagination = array(
					...$pagination,
					$dots,
					...array_slice( $old_pagination, $current_page - 2, 3 ),
					$dots,
					...array_slice( $old_pagination, -1, 1 ),
				);
			} else {
				// We are at the end of the pagination.
				$pagination = array(
					...$pagination,
					$dots,
					...array_slice( $old_pagination, $total_pages - ( $current_page === ( $total_pages - 2 ) ? 4 : 3 ), 4 ),
				);
			}
		} else {
			$pagination = array(
				...array_slice( $old_pagination, 0, $current_page > 2 ? 4 : 3 ),
				$dots,
				...array_slice( $old_pagination, -1, 1 ),
			);
		}
	}
	$context['pagination']         = $pagination;
	$context['first_page_link']    = build_pagination_url( $attributes, 1 );
	$context['last_page_link']     = build_pagination_url( $attributes, $total_pages );
	$context['previous_page_link'] = build_pagination_url( $attributes, ( $current_page > 1 ) ? $current_page - 1 : 1 );
	$context['next_page_link']     = build_pagination_url( $attributes, ( $current_page < $total_pages ) ? $current_page + 1 : $total_pages );
	$context['total_pages']        = $total_pages;
}
if ( ( $attributes['showPagination'] ?? false ) && ( $attributes['infiniteScroll'] ?? false ) ) {
	$context['has_more']       = $current_page < $total_pages;
	$context['next_page_link'] = build_pagination_url( $attributes, ( $current_page < $total_pages ) ? $current_page + 1 : $total_pages );
}

$context['posts'] = $final_posts ?? $timber_posts;

$taxonomy_key = build_param_name( 'taxonomy', $attributes['instanceId'] ?? '' );

$interactivity_context = array(
	'currentPage'      => $current_page ?? 1,
	'isInfiniteScroll' => $attributes['infiniteScroll'] ?? false,
	'filters'          => array(
		$taxonomy_key => get_parameter( $taxonomy_key, array() ),
	),
	'terms'            => $context['taxonomies_filter'],
	'blockId'          => $attributes['instanceId'],
);
/**
 * Filters the interactivity context for the dynamic archive block.
 *
 * @param array $interactivity_context The interactivity context.
 * @param array $attributes The attributes of the dynamic archive block.
 *
 * @hooked jcore_dynamic_archive_interactivity_context
 */
$interactivity_context = apply_filters( 'jcore_dynamic_archive_interactivity_context', $interactivity_context, $attributes );

// Possibly set the Global state.
wp_interactivity_state(
	'jcore/dynamic-archive',
	apply_filters(
		'jcore_dynamic_archive_interactivity_state',
		array()
	)
);

$context['interactivity_context_attribute'] = wp_interactivity_data_wp_context( $interactivity_context, 'jcore/dynamic-archive' );

$rendered = Timber::compile( 'dynamic-archive/archive.twig', $context );

echo wp_interactivity_process_directives( $rendered ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
