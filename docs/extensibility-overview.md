# Dynamic Archive Extensibility Overview

This document defines the extension API for `jcore/dynamic-archive`.

## Status and compatibility

- API namespace: `jcore.dynamicArchive` (JS) and `jcore_dynamic_archive_*` (PHP).
- Current stability: **experimental**.
- Extension API version: `1` (available in `window.jcoreDynamicArchive.extensibilityApiVersion`).
- Goal: keep third-party extensions compatible across minor releases once the API is promoted to stable.

## Supported extension surfaces

### 1) Attribute registry (schema extensions)

Use the `jcore_dynamic_archive_attributes` filter to add new block attributes before block registration.

- Input/output type: `array<string,array>`.
- New attributes must be namespaced as `vendorName_featureName`.
- New attributes must define both `type` and `default`.
- Core attributes are immutable by default.

Related filters:

- `jcore_dynamic_archive_block_metadata`: modifies raw metadata before registration.
- `jcore_dynamic_archive_attributes`: adds/modifies attributes.
- `jcore_dynamic_archive_allow_attribute_overrides`: enables core-attribute overrides when explicitly needed.

### 2) Editor controls (Inspector)

Primary API: Slot/Fill surfaces in the block Inspector.

Slots:

- `jcore/dynamic-archive/inspector-general`
- `jcore/dynamic-archive/inspector-layout`
- `jcore/dynamic-archive/inspector-filters`

A JS fallback API based on `@wordpress/hooks` is also available:

- `jcore.dynamicArchive.editor.extensionContext`
- `jcore.dynamicArchive.editor.generalControls`
- `jcore.dynamicArchive.editor.layoutControls`
- `jcore.dynamicArchive.editor.filterControls`

### 3) Query and render pipeline (PHP)

Dynamic archive output remains server-authoritative. Extensions can mutate:

- query args (`jcore_dynamic_archive_args`),
- taxonomy query behavior (`jcore_dynamic_archive_tax_query`, `jcore_dynamic_archive_tax_query_relation`),
- interactivity context/state (`jcore_dynamic_archive_interactivity_context`, `jcore_dynamic_archive_interactivity_state`),
- final Twig context (`jcore_dynamic_archive_render_context`).

## Guardrails and collision policy

- Avoid unprefixed attribute names.
- Do not overwrite core attributes unless `jcore_dynamic_archive_allow_attribute_overrides` returns `true`.
- Keep extension data serializable using block attribute schema types.
- Treat extension code as optional: core block must work even if extension scripts fail to load.

## Recommended implementation pattern

1. Add attributes with `jcore_dynamic_archive_attributes`.
2. Add Inspector controls through Slot/Fill (or JS hook fallback).
3. Apply server logic in `jcore_dynamic_archive_args` (and related filters).
4. If template data is required, append it via `jcore_dynamic_archive_render_context`.
