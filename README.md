# JCORE Dynamic Archive

A WordPress plugin that adds a filterable, sortable and searchable post archive block, plus a latest/related posts block. Front-end filtering, pagination and "load more" are powered by the [Interactivity API](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/) and its router, so the archive updates without full page reloads while every state stays linkable through the URL.

Templates are rendered with [Timber](https://timber.github.io/docs/v2/), and themes can override any of them.

## Requirements

- WordPress 6.7 or newer
- PHP 8.1 or newer
- [Timber](https://github.com/timber/timber) 2.x (installed by the theme or site, not bundled with this plugin)

An admin notice is shown if Timber is missing.

## Installation

Install via Composer:

```sh
composer require jcodigital/jcore-dynamic-archive
```

`composer/installers` places the package in the plugins directory. Activate it as usual, or load it from a must-use plugin together with your Composer autoloader.

Updates are delivered through [jcore-update](https://github.com/jco-digital/jcore-update) when that package is available.

## Blocks

### Dynamic Archive (`jcore/dynamic-archive`)

A post archive with optional front-end controls.

**Query**

- Post type selection, or **Inherit settings from query** to follow the current archive (term, author, post type, date, search or blog page).
- Order and order by (date, title, modified, author, ID, menu order).
- Sticky posts: include, exclude or show only sticky posts.
- Hide children (for hierarchical post types).
- Show all languages (Polylang).

**Filters**

- Taxonomy filters per taxonomy, displayed as checkbox, radio, dropdown or multiselect.
- Hierarchical filters: child terms stay hidden until their parent is selected, and can use a different filter type than the parents.
- Forced terms: restrict the archive (and its filter) to specific terms.
- Free-text search with a configurable label. If Relevanssi is active, it is used for the search.
- User-selectable sorting by date, title, or any taxonomy of the post type.

**Layout and pagination**

- Columns, masonry grid and posts per page.
- Numbered pagination, or infinite scroll ("load more").

### Latest Posts (`jcore/latest-posts`)

Shows the newest posts of the selected post types, optionally limited to specific terms, with an optional heading, columns, order and sticky post handling. It can also inherit the current archive query.

The **Related Posts (JCORE)** variation shows posts that share terms with the current post. With **backfill** enabled, remaining slots are filled with other recent posts of the same post type.

## Theme integration

### Post teaser (required)

The plugin does not ship a post teaser template. Your theme must provide one, looked up in this order:

| Block           | Templates tried                                                  |
| --------------- | ---------------------------------------------------------------- |
| Dynamic Archive | `dynamic-archive/partials/tease.twig`, then `partials/tease.twig` |
| Latest Posts    | `latest-posts/partials/tease.twig`, then `partials/tease.twig`    |

The template receives `post` (a Timber post) and `nr` (the 1-based loop index).

### Overriding templates

The plugin's `blocks/templates/` directory is registered as a Timber location after the theme's own locations. To override a template, place a file with the same relative path in your theme's Timber views directory, e.g. `dynamic-archive/partials/filters/filter-checkbox.twig`.

Pagination and "load more" can also be overridden via `dynamic-archive/overrides/partials/pagination.twig` and `dynamic-archive/overrides/partials/load-more.twig`.

Overridden templates must escape any block attribute they print (e.g. `{{ attributes.searchLabel|e }}`).

### URL parameters

The archive state lives in the query string, prefixed per block instance as `dynamic-archive-{instanceId}-`:

| Parameter                               | Example                                         |
| --------------------------------------- | ----------------------------------------------- |
| `…-taxonomy[{taxonomy}][]`              | `dynamic-archive-3-taxonomy[category][]=12`     |
| `…-sort`                                | `dynamic-archive-3-sort=title-ASC`, `tax:category-DESC` |
| `…-search`                              | `dynamic-archive-3-search=hello`                |
| `…-archive-paged`                       | `dynamic-archive-3-archive-paged=2`             |

Taxonomy filters use term IDs by default. Switch to slugs with the `jcore_dynamic_archive_taxonomy_field_type` filter, and change the prefix with `jcore_dynamic_archive_param_prefix`.

## Hooks

### PHP filters

| Filter | Purpose |
| --- | --- |
| `jcore_dynamic_archive_args` | Final `WP_Query` args of the archive. |
| `jcore_dynamic_archive_tax_query` | Query args after taxonomy filters are applied. |
| `jcore_dynamic_archive_tax_query_relation` | Relation between forced term groups (default `OR`). |
| `jcore_dynamic_archive_handle_taxonomy_sorting` | Query args when sorting by taxonomy. |
| `jcore_dynamic_archive_inherited_query_args` | Args inherited from the current main query. |
| `jcore_dynamic_archive_inherit_sticky` | Apply sticky settings when inheriting the query (default `false`). |
| `jcore_dynamic_archive_taxonomies_inherit` | Taxonomy filter configuration used when the block inherits the query. |
| `jcore_dynamic_archive_taxonomies_filter` | The filter data (taxonomies and terms) passed to the templates. |
| `jcore_dynamic_archive_taxonomies_filter_query_aware` | Only show terms that match posts in the current result (faceted filters, default `false`). |
| `jcore_dynamic_archive_facet_term_query_args` | Query args used to find applicable terms for faceted filters. |
| `jcore_dynamic_archive_use_post_type_term_usage` | Only show terms used by the selected post type (cached, default `false`). |
| `jcore_dynamic_archive_term_post_type_usage_cache_ttl` | Cache TTL for the term usage lookup (default one day). |
| `jcore_dynamic_archive_term_post_type_usage_post_statuses` | Post statuses counted in the term usage lookup. |
| `jcore_dynamic_archive_taxonomy_field_type` | `id` or `slug` for taxonomy URL parameters. |
| `jcore_dynamic_archive_param_prefix` | URL parameter prefix per block instance. |
| `jcore_dynamic_archive_infinite_scroll_max_pages` | Highest page reachable with "load more" (default `50`). |
| `jcore_dynamic_archive_interactivity_context` | Interactivity API context of the block. |
| `jcore_dynamic_archive_interactivity_state` | Global Interactivity API state. |
| `jcore_latest_posts_args` | `WP_Query` args of the Latest Posts block (also applied to the backfill query). |

Example: enable faceted filters for all archives.

```php
add_filter( 'jcore_dynamic_archive_taxonomies_filter_query_aware', '__return_true' );
```

### JavaScript filters (block editor)

| Filter | Purpose |
| --- | --- |
| `dynamicArchive.forbiddenPostTypes` | Post types hidden from the post type selector (default `['attachment']`). |
| `jcore.latestPosts.showPostTypes` | Post types offered in the Latest Posts block. |
| `jcore.latestPosts.taxonomies` | Taxonomies offered for term selection in the Latest Posts block. |
| `jcore.latestPosts.maxSelected` | Maximum number of selectable post types (default `-1`, unlimited). |
| `jcore.latestPosts.maxItems` | Maximum value for posts per page (default `25`). |

## Development

The block sources live in `blocks/` and are built with `@wordpress/scripts`, using pnpm.

```sh
make install   # composer install + pnpm install
make dev       # watch and rebuild blocks
make build     # production build into blocks/build
make make-pot  # regenerate languages/jcore-dynamic-archive.pot (requires WP-CLI)
```

Don't edit files under `blocks/build/`; they are generated from `blocks/src/`.

Commits follow [Conventional Commits](https://www.conventionalcommits.org/). The changelog and releases are generated from them.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

GPL-2.0-only. See `composer.json`.
