# Changelog

### 1.0.6 (2026-06-05)

#### Bug Fixes

- plugin: lower minimum PHP version requirement to 8.1 (8ecb7a8)

#### Build System

- composer: update lock file (52f217d)

#### Continuous Integration

- github: update plugin publish workflow to use main branch (2215fa8)
- github: update pnpm version to 11 in push workflow (b4adde7)

### v1.0.5 (2026-06-02)

#### Maintenance

- composer: update project package name (4db1f48)

### v1.0.4 (2026-06-02)

#### Maintenance

- blocks: remove build directory from source control (285f094)

### v1.0.3 (2026-06-02)

#### Features

- rollback version and allow workflow to correctly bump (c58a8fe)
- update: register plugin update hooks (5d253c7)
- Overhaul CI system to use foonver and jcore-update. (BREAKING CHANGE) (9dc5146)
- dynamic-archive: add multiselect filter type (c6108b5)

#### Bug Fixes

- helpers: improve taxonomy filtering and query awareness (259e6da)
- pagination: escape generated URL attributes (d08926f)
- pagination: remove extra closing parenthesis in build_pagination_url (506b69f)
- helpers: ensure paged parameter is an integer and escape pagination URL (1e44faf)
- remove unused term post type usage functionality, since it is being requeried separately. (4648057)
- also revert version.json... (98742b8)
- update: add missing quotes to ABSPATH constant definition (522f0b5)
- plugin: remove redundant Timber requirement notice (13f9e08)
- minor improvements (c68e381)
- ci: Create release after tag has been created (d0f05e3)
- blocks: migrate CSS to SCSS for dynamic-archive and latest-posts blocks (fe43723)
- dynamic-archive: add keyboard support and disabled state handling to filters (41b5a48)
- archive: add keyboard accessibility support for multiselect toggle (a4aad06)
- i18n: use format string for remove button aria-label (f584f2b)
- block: improve multiselect interaction handling (fab7264)

#### Refactor

- cache: rename functions for conciseness in term post type usage (5744a0f)

#### Continuous Integration

- github: add distribution repository configuration to release workflow (a6397a0)
- github: add contents write permission to publish job (d7399b6)
- github: update reusable-plugin-publish action version (114940a)
- github: pin reusable publish workflow to commit hash (cff1a86)
- github: update release workflow to use conventional-changelog-action and fix zip command (4919666)
- github: automate release process with zip packaging and GitHub releases (366c772)

#### Maintenance

- bump plugin version to 1.0.1 (2fdee2e)
- helpers: re-add term usage by post type cache and helper functions (7c12c0c)
- plugin: update header metadata with compatibility requirements (7c58e0b)
- rename zipexclude to .zipexclude (cb686ed)
- git: add common files to .zipexclude (147ce54)
- rename zip_exclude.txt to .zipexclude (62f1fe0)
- i18n: add missing Select... translation string (37d5c8f)

### v0.24.2 (2026-06-02)

#### Bug Fixes

- pagination: escape generated URL attributes (bd539c1)
- backport and reapply XSS fix + release version 0.24.2 (928f62b)

### v0.25.3 (2026-06-02)

#### Bug Fixes

- backport and reapply XSS fix + release version 0.25.3 (0e14a86)
- pagination: escape URL output in build_pagination_url (03c8bff)

### v1.0.2 (2026-06-02)

#### Bug Fixes

- helpers: improve taxonomy filtering and query awareness (259e6da)
- pagination: remove extra closing parenthesis in build_pagination_url (506b69f)
- helpers: ensure paged parameter is an integer and escape pagination URL (1e44faf)
- remove unused term post type usage functionality, since it is being requeried separately. (4648057)

#### Refactor

- cache: rename functions for conciseness in term post type usage (5744a0f)

#### Maintenance

- bump plugin version to 1.0.1 (2fdee2e)
- helpers: re-add term usage by post type cache and helper functions (7c12c0c)
- plugin: update header metadata with compatibility requirements (7c58e0b)

### v1.0.1 (2026-06-02)

#### Bug Fixes

- pagination: escape generated URL attributes (d08926f)

## v1.0.0 (2026-06-02)

#### Features

- rollback version and allow workflow to correctly bump (c58a8fe)
- update: register plugin update hooks (5d253c7)
- Overhaul CI system to use foonver and jcore-update. (BREAKING CHANGE) (9dc5146)

#### Bug Fixes

- also revert version.json... (98742b8)
- update: add missing quotes to ABSPATH constant definition (522f0b5)
- plugin: remove redundant Timber requirement notice (13f9e08)
- minor improvements (c68e381)

#### Continuous Integration

- github: add contents write permission to publish job (d7399b6)
- github: update reusable-plugin-publish action version (114940a)
- github: pin reusable publish workflow to commit hash (cff1a86)

#### Maintenance

- rename zipexclude to .zipexclude (cb686ed)
- git: add common files to .zipexclude (147ce54)
- rename zip_exclude.txt to .zipexclude (62f1fe0)

### v0.25.2 (2026-06-01)

#### Bug Fixes

- ci: Create release after tag has been created (d0f05e3)

### v0.25.1 (2026-06-01)

#### Bug Fixes

- blocks: migrate CSS to SCSS for dynamic-archive and latest-posts blocks (fe43723)

## v0.25.0 (2026-05-21)

#### Features

- dynamic-archive: add multiselect filter type (c6108b5)

#### Bug Fixes

- dynamic-archive: add keyboard support and disabled state handling to filters (41b5a48)
- archive: add keyboard accessibility support for multiselect toggle (a4aad06)
- i18n: use format string for remove button aria-label (f584f2b)
- block: improve multiselect interaction handling (fab7264)

#### Continuous Integration

- github: update release workflow to use conventional-changelog-action and fix zip command (4919666)
- github: automate release process with zip packaging and GitHub releases (366c772)

#### Maintenance

- i18n: add missing Select... translation string (37d5c8f)

### v0.24.1 (2026-05-06)

#### Bug Fixes

- helpers: use regex to parse sort arguments for improved reliability (e624a26)

## v0.24.0 (2026-05-06)

#### Features

- i18n: update sort label and translation files (c815d04)
- blocks: add sort direction support for titles and taxonomies (7995e4b)
- dynamic-archive: add sort functionality to block (ecb4570)

#### Bug Fixes

- block: move filter and sort option initialization after post retrieval (1cf79bc)
- archive: update sort dropdown interactivity and context (39a76d7)

#### Refactor

- dynamic-archive: consolidate styles and improve security (4e43a82)

### v0.23.1 (2026-04-22)

#### Bug Fixes

- update URL handling in helper functions to prevent XSS (f4619eb)

## v0.23.0 (2026-04-02)

#### Features

- add backfill option to latest posts block for improved post retrieval (4b2a8ba)
- add heading support to latest posts block and improve code formatting (1ad8e32)

#### Maintenance

- build artifacts (1bac736)

## v0.22.0 (2026-03-30)

#### Features

- enhance dynamic archive block with improved taxonomy filtering. (fb717f2)
- implement caching for term usage by post type in dynamic archive (defe6b9)
- add sorting for taxonomy terms by name in dynamic archive block (210f548)

## v0.21.0 (2026-03-27)

#### Features

- correctly use a label for the search input to improve a11y. (39a4c84)

#### Bug Fixes

- ensure posts are only fetched if published in dynamic-archive block (bb10d72)
- actually allow overriding pagination by changing first path. (fd2a07a)

#### Maintenance

- build files (6630be2)
- update block styles and dependencies for latest posts and dynamic archive (29621b1)

## v0.20.0 (2026-02-23)

#### Features

- improved inherit option to actually query by current category (f5680b0)

#### Bug Fixes

- Ensure taxonomies filter is applied correctly in latest posts block rendering (da7b92b)

#### Documentation

- Update AGENTS.md to include project-specific notes and clarify package management instructions (bcd3b79)
- Update AGENTS.md to clarify project details and package management (7fce99a)

### v0.19.1 (2026-01-09)

#### Bug Fixes

- Update dynamic-archive block to correctly handle post exclusion on singular pages (37d2587)

## v0.19.0 (2025-12-05)

#### Features

- Support taxonomy filter by slug and customizable param names (ab85d3e)

#### Bug Fixes

- Fix typo in interactivity state filter name. (fcb70c9)

#### Build System

- Minify dynamic-archive block build files. (39c43d5)

## v0.18.0 (2025-12-01)

#### Features

- Add support for relevanssi. (19d9d4a)

## v0.17.0 (2025-12-01)

#### Features

- Add "Search..." translation to Finnish and Swedish files. (899fcb6)
- Add free text search option to filters. (2c27896)

#### Styles

- Refactor edit.js to improve readability and reduce nesting. (3949435)

### v0.16.4 (2025-11-19)

#### Bug Fixes

- added composer installers. (d87e8b7)

### v0.16.3 (2025-11-14)

#### Bug Fixes

- require v3 or v4 of ydin (7b29371)

### v0.16.2 (2025-11-03)

#### Bug Fixes

- latest-posts: add similar filter as with dynamic-archive for the post query args (554fdb0)

### v0.16.1 (2025-10-31)

#### Bug Fixes

- Update tax query relation to use a filter for improved flexibility (21613c4)

## v0.16.0 (2025-09-12)

#### Features

- Add "Show all" option for radio filters and update translations. ✨ 🌐 (f726025)

### v0.15.1 (2025-06-11)

#### Bug Fixes

- labels now also recieves an is-active class + some accessibility improvements. 🐛 (8b1a6f0)

## v0.15.0 (2025-06-03)

#### Features

- Remove all CSS styles from latest-posts block. 🔥 (e9bccc0)

## v0.14.0 (2025-05-27)

#### Features

- dynamic-archive: add inherit attribute for dynamic archive, allowing for reading the post type from the current archive page. (7d77ada)

#### Bug Fixes

- dynamic-archive: handle inherit if block is inserted into singular (0d3f37e)

### v0.13.1 (2025-05-26)

#### Bug Fixes

- latest-posts: conditionally render filters panel based on related posts state 🐛 (a24ba9f)

## v0.13.0 (2025-05-26)

#### Features

- latest-posts: added related posts variation. ✨ (fb6946a)

#### Maintenance

- deps: remove lodash (f83cfe0)

### v0.12.2 (2025-05-26)

#### Bug Fixes

- blocks: add Disabled wrapper to blocks to disable interaction in the editor. (5731efe)

### v0.12.1 (2025-05-26)

#### Bug Fixes

- workflow: update cache key to use pnpm-lock.yaml (0c60a4e)

## v0.12.0 (2025-05-26)

#### Features

- change to pnpm + add new preCommit hook to deploy (4794a52)

## v0.11.0 (2025-03-28)

#### Features

- Added option to show items of all languages. (4daafee)

### v0.10.2 (2024-12-17)

#### Bug Fixes

- TaxonomyPicker: Fixed an issue that caused a crash if the taxonomy picker is used to search for values. 🐛 (f51c137)

### v0.10.1 (2024-12-10)

#### Bug Fixes

- do not make pot when building. (e7f4fb1)
- Added i18n and fixed some broken domains (c8bfd4c)

#### Maintenance

- make file can create translations. ✨ (e888a68)

## v0.10.0 (2024-12-10)

#### Features

- finalized the first version of the latest posts block ✨ (6c3a01a)
- started working on latest-posts block :sparkles: (05db418)

## v0.9.0 (2024-12-03)

#### Features

- archive: Added ability to hide children for hierarchical post types :sparkles: (0a6df60)

## v0.8.0 (2024-12-02)

#### Features

- pagination now has dots when pages are over 6 (a739dcf)

### v0.7.2 (2024-11-22)

#### Bug Fixes

- minor changes and fixes for different setting combinations :bug: (f8fa13a)

### v0.7.1 (2024-11-21)

#### Bug Fixes

- When terms have been set, and then unset, Gberg leaves an "empty" array, which triggered filtering. 🐛 (aecc57f)

#### Maintenance

- fix merge conflicts (3e91b4e)
- test ci 💚 (207c063)

## v0.7.0 (2024-11-21)

#### Features

- multiple blocks preserve state on a page. ✨ (a881a27)
- Forced categories now work ✨ (fbc4895)
- blocks/archive: Added hierarchical filtering. ✨ (8b00697)
- Finalize rewrite to wp-interactivity API + loads of minor fixes ✨ (BREAKING CHANGE) (2bebd26)
- very many stuff (7562b6e)
- finalized settings view and new settings ✨ (42efadf)
- package updates + installed needed packages (503d082)
- Started working on new filter settings ✨ (d5965aa)

#### Bug Fixes

- disable building for now (5dd227f)

#### Refactor

- frontend: Cleanup external dependencies (htmx + alpine) ♻️ (05b1ab8)
- Settings refactored ♻️ (d82c4d4)

#### Continuous Integration

- fix push target 💚 (8cae9af)
- Fix cache + use npm (c1aaa31)
- Fix CI build issues 💚 (e79f43c)
- Add build step to correctly build files when a pull request is created. 👷 (3901937)

#### Maintenance

- build and commit blocks on release ✨ (d235884)
- cleanup imports 🗑 (85cda72)

### v0.6.6 (2024-09-30)

#### Bug Fixes

- use block attributes + add support for alignment (ff1a723)

### v0.6.5 (2024-09-25)

#### Bug Fixes

- gutenberg: fix an issue fetching CPTs :bug: (4751d23)

### v0.6.4 (2024-09-25)

#### Bug Fixes

- pagination: Fixed issues when pagination is disabled and pagination + filtering not working together 🐛 (ad715c0)

### v0.6.3 (2024-09-25)

#### Bug Fixes

- Some defaults added to prevent issues when first adding the block. 🐛 (454031b)

### v0.6.2 (2024-09-25)

#### Bug Fixes

- lock ydin to 3.7.2 ⬆️ (9f97402)

### v0.6.1 (2024-09-25)

#### Bug Fixes

- masonry grid class name fixed 🐛 (03c70dc)

## v0.6.0 (2024-09-25)

#### Features

- block: Added load more functionality for the block. ✨ (e9a4b67)

## v0.5.0 (2024-09-25)

#### Features

- Dynamic archive made dynamic courtesy of HTMX ✨ (5099c77)
- Dynamic archive made dynamic courtesy of HTMX ✨ (BREAKING CHANGE) (5aeb814)

### v0.4.1 (2024-09-25)

#### Bug Fixes

- renderblocks class added for now. (d0493bc)

## v0.4.0 (2024-09-25)

#### Features

- gutenberg: Added basic version of dynamic archive block ✨ (8e20c9d)
- gutenberg: multi block functionality and loading added ✨ (e8b697f)

#### Refactor

- Move blocks one dir up (457eaec)

#### Build System

- Added built gutenberg files 📦 (7111525)

## v0.3.0 (2024-09-24)

#### Features

- updated jcore-dynamic archive to be a plugin. ✨ (c1f81a5)

### v0.2.1 (2024-09-24)

#### Bug Fixes

- composer namespace updated to match the used namespace 🐛 (fc9e4be)

## v0.2.0 (2024-09-24)

#### Features

- repo: Repo is now free of template stuff and ready to be deployed 🚀 (e1a29e1)

#### Bug Fixes

- composer.lock updated to work correctly 🐛 (7b97e92)

#### Continuous Integration

- allow secrets to be inherited (c2793f9)
- use the correct tag 💚 (aa4b359)

### Misc
- Initial commit (5521752)

