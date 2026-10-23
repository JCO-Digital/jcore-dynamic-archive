=== JCORE Dynamic Archive ===
Contributors: jcodigital
Tags: archive, posts, filters, block, interactivity
Requires at least: 6.7
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.10
License: GPL-2.0-only
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A filterable post archive block and a latest posts block for the block editor.

== Description ==

JCORE Dynamic Archive adds two blocks for listing posts:

* **Dynamic Archive** – a post archive with optional filters, sorting, search and pagination. The list updates without a full page reload, and every filtered or paged state has its own address that can be shared.
* **Latest posts (JCORE)** – the newest posts of the chosen post types, optionally limited to some terms. Its **Related Posts (JCORE)** variation shows posts that share terms with the current post, and can fill the remaining places with other recent posts.

= Features =

* **Inherit the query** – in templates, both blocks can follow the current archive: term, author, post type, date, search or blog page.
* **Taxonomy filters** – shown as checkboxes, radio buttons, a dropdown or a multiselect. Child terms can stay hidden until their parent is chosen.
* **Forced terms** – limit an archive and its filters to selected terms.
* **Search and sorting** – free-text search, using Relevanssi when it is active, and sorting by date, title or a taxonomy.
* **Pagination or load more** – numbered pages, or a load more link that adds the next page below the list.
* **Sticky posts** – include, exclude or show only sticky posts.
* **Polylang** – show posts in the current language or in all languages.
* **Automatic updates** – through the J&Co Digital update service.

= Theme integration =

The plugin does not ship a post teaser: the blocks render each post with the theme's `partials/tease.twig`, or with `dynamic-archive/partials/tease.twig` and `latest-posts/partials/tease.twig` when a block needs its own. The template receives `post` (a Timber post) and `nr` (its position in the list).

Any other template of the plugin can be overridden by placing a file with the same relative path in the theme's Timber views directory.

= External services =

The plugin checks for updates against `https://update.jcore.fi`, operated by J&Co Digital Oy. It sends the plugin slug and the installed version, and WordPress adds the site address to the request's user agent. No visitor or content data is involved.

= Source code =

The blocks are built with `@wordpress/scripts`. Their readable source is published at https://github.com/JCO-Digital/jcore-dynamic-archive.

== Installation ==

1. Make sure Timber 2 is installed. The plugin does not bundle it; the theme or site provides it.
2. Install the plugin with `composer require jcodigital/jcore-dynamic-archive`, or upload the release zip on the Plugins screen.
3. Activate the plugin.
4. Add a **Dynamic Archive** or **Latest posts (JCORE)** block in the editor.

== Frequently Asked Questions ==

= Why does the block show no posts, or show them without styling? =

The blocks render posts with the theme's teaser template. Add `partials/tease.twig` to the theme's Timber views directory.

= Can I change the queries? =

Yes. `jcore_dynamic_archive_args` filters the archive's `WP_Query` arguments and `jcore_latest_posts_args` those of the latest posts block. The README on GitHub lists every PHP and JavaScript filter.

= Can I link to a filtered archive? =

Yes. The archive keeps its filters, search, sorting and page in the query string, for example `?dynamic-archive-3-taxonomy[category][]=12`. Terms are referred to by ID, or by slug with the `jcore_dynamic_archive_taxonomy_field_type` filter.

= What happens if Timber is missing? =

The plugin shows an admin notice. The blocks render through Timber, so install it before adding them to any page.

== Changelog ==

= 1.0.10 (2026-10-23) =

* Build: composer - update jcore-update dependency to v1.7.1

= v1.0.9 (2026-09-23) =

* Fix: security - harden URL parameter handling and escape block attributes
* Documentation: readme - update documentation and remove unused configs
* Maintenance: config - add foonver configuration file and update stable tag

= v1.0.8 (2026-09-09) =

* Fix: helpers - guard against non-array taxonomy filter query parameter

= v1.0.7 (2026-06-05) =

* Build: composer - update jcore-update dependency to v1.2.0

= v1.0.6 (2026-06-05) =

* Fix: plugin - lower minimum PHP version requirement to 8.1
* Build: composer - update lock file
* CI: github - update plugin publish workflow to use main branch
* CI: github - update pnpm version to 11 in push workflow

= v1.0.5 (2026-06-02) =

* Maintenance: composer - update project package name

= v1.0.4 (2026-06-02) =

* Maintenance: blocks - remove build directory from source control

= v1.0.3 (2026-06-02) =

* CI: github - add distribution repository configuration to release workflow

= v1.0.2 (2026-06-02) =

* Fix: helpers - improve taxonomy filtering and query awareness
* Fix: pagination - remove extra closing parenthesis in build_pagination_url
* Fix: helpers - ensure paged parameter is an integer and escape pagination URL
* Fix: remove unused term post type usage functionality, since it is being requeried separately.
* Refactor: cache - rename functions for conciseness in term post type usage
* Maintenance: bump plugin version to 1.0.1
* Maintenance: helpers - re-add term usage by post type cache and helper functions
* Maintenance: plugin - update header metadata with compatibility requirements

= v1.0.1 (2026-06-02) =

* Fix: pagination - escape generated URL attributes

= v1.0.0 (2026-06-02) =

* Feature: rollback version and allow workflow to correctly bump
* Feature: update - register plugin update hooks
* Feature: Overhaul CI system to use foonver and jcore-update. (BREAKING CHANGE)
* Fix: also revert version.json...
* Fix: update - add missing quotes to ABSPATH constant definition
* Fix: plugin - remove redundant Timber requirement notice
* Fix: minor improvements
* CI: github - add contents write permission to publish job
* CI: github - update reusable-plugin-publish action version
* CI: github - pin reusable publish workflow to commit hash
* Maintenance: rename zipexclude to .zipexclude
* Maintenance: git - add common files to .zipexclude
* Maintenance: rename zip_exclude.txt to .zipexclude

= v0.25.3 (2026-06-02) =

* Fix: backport and reapply XSS fix + release version 0.25.3
* Fix: pagination - escape URL output in build_pagination_url

= v0.25.2 (2026-06-01) =

* Fix: ci - Create release after tag has been created

= v0.25.1 (2026-06-01) =

* Fix: blocks - migrate CSS to SCSS for dynamic-archive and latest-posts blocks

= v0.25.0 (2026-05-21) =

* Feature: dynamic-archive - add multiselect filter type
* Fix: dynamic-archive - add keyboard support and disabled state handling to filters
* Fix: archive - add keyboard accessibility support for multiselect toggle
* Fix: i18n - use format string for remove button aria-label
* Fix: block - improve multiselect interaction handling
* CI: github - update release workflow to use conventional-changelog-action and fix zip command
* CI: github - automate release process with zip packaging and GitHub releases
* Maintenance: i18n - add missing Select... translation string

= v0.24.2 (2026-06-02) =

* Fix: pagination - escape generated URL attributes
* Fix: backport and reapply XSS fix + release version 0.24.2

= v0.24.1 (2026-05-06) =

* Fix: helpers - use regex to parse sort arguments for improved reliability

= v0.24.0 (2026-05-06) =

* Feature: i18n - update sort label and translation files
* Feature: blocks - add sort direction support for titles and taxonomies
* Feature: dynamic-archive - add sort functionality to block
* Fix: block - move filter and sort option initialization after post retrieval
* Fix: archive - update sort dropdown interactivity and context
* Refactor: dynamic-archive - consolidate styles and improve security

= v0.23.1 (2026-04-22) =

* Fix: update URL handling in helper functions to prevent XSS

= v0.23.0 (2026-04-02) =

* Feature: add backfill option to latest posts block for improved post retrieval
* Feature: add heading support to latest posts block and improve code formatting
* Maintenance: build artifacts

= v0.22.0 (2026-03-30) =

* Feature: enhance dynamic archive block with improved taxonomy filtering.
* Feature: implement caching for term usage by post type in dynamic archive
* Feature: add sorting for taxonomy terms by name in dynamic archive block

= v0.21.0 (2026-03-27) =

* Feature: correctly use a label for the search input to improve a11y.
* Fix: ensure posts are only fetched if published in dynamic-archive block
* Fix: actually allow overriding pagination by changing first path.
* Maintenance: build files
* Maintenance: update block styles and dependencies for latest posts and dynamic archive

= v0.20.0 (2026-02-23) =

* Feature: improved inherit option to actually query by current category
* Fix: Ensure taxonomies filter is applied correctly in latest posts block rendering
* Documentation: Update AGENTS.md to include project-specific notes and clarify package management instructions
* Documentation: Update AGENTS.md to clarify project details and package management

= v0.19.1 (2026-01-09) =

* Fix: Update dynamic-archive block to correctly handle post exclusion on singular pages

= v0.19.0 (2025-12-05) =

* Feature: Support taxonomy filter by slug and customizable param names
* Fix: Fix typo in interactivity state filter name.
* Build: Minify dynamic-archive block build files.

= v0.18.0 (2025-12-01) =

* Feature: Add support for relevanssi.

= v0.17.0 (2025-12-01) =

* Feature: Add "Search..." translation to Finnish and Swedish files.
* Feature: Add free text search option to filters.
* Style: Refactor edit.js to improve readability and reduce nesting.

= v0.16.4 (2025-11-19) =

* Fix: added composer installers.

= v0.16.3 (2025-11-14) =

* Fix: require v3 or v4 of ydin

= v0.16.2 (2025-11-03) =

* Fix: latest-posts - add similar filter as with dynamic-archive for the post query args

= v0.16.1 (2025-10-31) =

* Fix: Update tax query relation to use a filter for improved flexibility

= v0.16.0 (2025-09-12) =

* Feature: Add "Show all" option for radio filters and update translations. ✨ 🌐

= v0.15.1 (2025-06-11) =

* Fix: labels now also recieves an is-active class + some accessibility improvements. 🐛

= v0.15.0 (2025-06-03) =

* Feature: Remove all CSS styles from latest-posts block. 🔥

= v0.14.0 (2025-05-27) =

* Feature: dynamic-archive - add inherit attribute for dynamic archive, allowing for reading the post type from the current archive page.
* Fix: dynamic-archive - handle inherit if block is inserted into singular

= v0.13.1 (2025-05-26) =

* Fix: latest-posts - conditionally render filters panel based on related posts state 🐛

= v0.13.0 (2025-05-26) =

* Feature: latest-posts - added related posts variation. ✨
* Maintenance: deps - remove lodash

= v0.12.2 (2025-05-26) =

* Fix: blocks - add Disabled wrapper to blocks to disable interaction in the editor.

= v0.12.1 (2025-05-26) =

* Fix: workflow - update cache key to use pnpm-lock.yaml

= v0.12.0 (2025-05-26) =

* Feature: change to pnpm + add new preCommit hook to deploy

= v0.11.0 (2025-03-28) =

* Feature: Added option to show items of all languages.

= v0.10.2 (2024-12-17) =

* Fix: TaxonomyPicker: Fixed an issue that caused a crash if the taxonomy picker is used to search for values. 🐛

= v0.10.1 (2024-12-10) =

* Fix: do not make pot when building.
* Fix: Added i18n and fixed some broken domains
* Maintenance: make file can create translations. ✨

= v0.10.0 (2024-12-10) =

* Feature: finalized the first version of the latest posts block ✨
* Feature: started working on latest-posts block :sparkles:

= v0.9.0 (2024-12-03) =

* Feature: archive - Added ability to hide children for hierarchical post types :sparkles:

= v0.8.0 (2024-12-02) =

* Feature: pagination now has dots when pages are over 6

= v0.7.2 (2024-11-22) =

* Fix: minor changes and fixes for different setting combinations :bug:

= v0.7.1 (2024-11-21) =

* Fix: When terms have been set, and then unset, Gberg leaves an "empty" array, which triggered filtering. 🐛
* Maintenance: fix merge conflicts
* Maintenance: test ci 💚

= v0.7.0 (2024-11-21) =

* Feature: multiple blocks preserve state on a page. ✨
* Feature: Forced categories now work ✨
* Feature: blocks/archive: Added hierarchical filtering. ✨
* Feature: Finalize rewrite to wp-interactivity API + loads of minor fixes ✨ (BREAKING CHANGE)
* Feature: very many stuff
* Feature: finalized settings view and new settings ✨
* Feature: package updates + installed needed packages
* Feature: Started working on new filter settings ✨
* Fix: disable building for now
* Refactor: frontend - Cleanup external dependencies (htmx + alpine) ♻️
* Refactor: Settings refactored ♻️
* CI: fix push target 💚
* CI: Fix cache + use npm
* CI: Fix CI build issues 💚
* CI: Add build step to correctly build files when a pull request is created. 👷
* Maintenance: build and commit blocks on release ✨
* Maintenance: cleanup imports 🗑

= v0.6.6 (2024-09-30) =

* Fix: use block attributes + add support for alignment

= v0.6.5 (2024-09-25) =

* Fix: gutenberg - fix an issue fetching CPTs :bug:

= v0.6.4 (2024-09-25) =

* Fix: pagination - Fixed issues when pagination is disabled and pagination + filtering not working together 🐛

= v0.6.3 (2024-09-25) =

* Fix: Some defaults added to prevent issues when first adding the block. 🐛

= v0.6.2 (2024-09-25) =

* Fix: lock ydin to 3.7.2 ⬆️

= v0.6.1 (2024-09-25) =

* Fix: masonry grid class name fixed 🐛

= v0.6.0 (2024-09-25) =

* Feature: block - Added load more functionality for the block. ✨

= v0.5.0 (2024-09-25) =

* Feature: Dynamic archive made dynamic courtesy of HTMX ✨
* Feature: Dynamic archive made dynamic courtesy of HTMX ✨ (BREAKING CHANGE)

= v0.4.1 (2024-09-25) =

* Fix: renderblocks class added for now.

= v0.4.0 (2024-09-25) =

* Feature: gutenberg - Added basic version of dynamic archive block ✨
* Feature: gutenberg - multi block functionality and loading added ✨
* Refactor: Move blocks one dir up
* Build: Added built gutenberg files 📦

= v0.3.0 (2024-09-24) =

* Feature: updated jcore-dynamic archive to be a plugin. ✨

= v0.2.1 (2024-09-24) =

* Fix: composer namespace updated to match the used namespace 🐛

= v0.2.0 (2024-09-24) =

* Feature: repo - Repo is now free of template stuff and ready to be deployed 🚀
* Fix: composer.lock updated to work correctly 🐛
* CI: allow secrets to be inherited
* CI: use the correct tag 💚
* Maintenance: Initial commit
