# Attribute Lifecycle

This document describes how extension attributes travel from registration to frontend rendering.

## 1) Registration (PHP)

1. Core block metadata is loaded from `blocks/build/dynamic-archive/block.json`.
2. `jcore_dynamic_archive_block_metadata` can mutate metadata.
3. `jcore_dynamic_archive_attributes` can add attributes.
4. Attributes are validated:
   - namespaced as `vendorName_featureName`,
   - must include `type` and `default`,
   - cannot override core attributes unless `jcore_dynamic_archive_allow_attribute_overrides` is enabled.
5. Block registers with merged attributes.

## 2) Editor state (JS)

1. Block `edit` receives all attributes from the block registry schema.
2. Core controls and extension controls (Slot/Fill or JS hooks) call `setAttributes`.
3. Attribute values are persisted in block comment JSON (`save: () => null` dynamic block model).
4. `ServerSideRender` preview receives current attributes and calls `render.php`.

## 3) Server query pipeline (PHP)

1. `render.php` receives `$attributes`.
2. Core query args are assembled.
3. `jcore_dynamic_archive_args` allows extensions to mutate query arguments.
4. Posts are queried with final args.

## 4) Render context (PHP -> Twig)

1. Core context data is assembled (posts, pagination, interactivity context, attributes).
2. `jcore_dynamic_archive_render_context` can append extension-specific context.
3. Twig template compiles and directives are processed by Interactivity API.

## 5) Frontend interactivity (optional)

If extension behavior depends on URL/query params or interactivity state:

- use `jcore_dynamic_archive_interactivity_context` for block-local context, and/or
- use `jcore_dynamic_archive_interactivity_state` for store-level state.

This keeps editor controls, server rendering, and runtime behavior consistent.
