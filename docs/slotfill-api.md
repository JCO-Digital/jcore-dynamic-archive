# SlotFill API

The dynamic archive block exposes Inspector extension slots through `window.jcoreDynamicArchive.extensions`.

## Available exports

- `DynamicArchiveInspectorGeneralFill`
- `DynamicArchiveInspectorLayoutFill`
- `DynamicArchiveInspectorFiltersFill`
- `DYNAMIC_ARCHIVE_INSPECTOR_SLOTS`

Slot names:

- `jcore/dynamic-archive/inspector-general`
- `jcore/dynamic-archive/inspector-layout`
- `jcore/dynamic-archive/inspector-filters`

## Fill context contract

Slot fills receive the extension context through `fillProps`.

Shape:

- `attributes` (object): current block attributes.
- `setAttributes` (function): block updater from `edit`.
- `postType` (string): selected post type.
- `taxonomies` (array): selected taxonomy filters.
- `isSingular` (boolean): whether template context is singular.
- `inherit` (boolean): whether query inheritance is enabled.
- `isPostTypeHierarchical` (boolean): current post type hierarchy support.
- `postTypes` (array): loaded post type objects.
- `taxonomyOptions` (array): resolved taxonomy options for selected post type.
- `loading` (object):
  - `postTypes` (boolean)
  - `taxonomies` (boolean)
- `capabilities` (object):
  - `canToggleInherit` (boolean)
  - `canConfigurePostType` (boolean)
  - `canConfigureFilters` (boolean)
  - `isHierarchicalPostType` (boolean)

## Example (SlotFill)

```js
const { registerPlugin } = wp.plugins;
const { ToggleControl } = wp.components;
const { createElement: el } = wp.element;

const {
	DynamicArchiveInspectorFiltersFill,
} = window.jcoreDynamicArchive.extensions;

registerPlugin('acme-dynamic-archive-fill', {
	render: () =>
		el(DynamicArchiveInspectorFiltersFill, null, ({ attributes, setAttributes }) =>
			el(ToggleControl, {
				label: 'Featured posts only',
				checked: !!attributes.acme_showFeaturedOnly,
				onChange: (value) => setAttributes({ acme_showFeaturedOnly: value }),
			})
		),
});
```

## JS hook fallback API

If Slot/Fill is not desired, add controls with filters:

- `jcore.dynamicArchive.editor.generalControls`
- `jcore.dynamicArchive.editor.layoutControls`
- `jcore.dynamicArchive.editor.filterControls`

Each filter receives `(controls, extensionContext)` and should return:

- a React element, or
- an array of React elements.
