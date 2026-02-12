# Dynamic Archive Example Extension

This example plugin shows how to extend `jcore/dynamic-archive` without changing core plugin code.

It demonstrates:

1. adding an attribute (`acme_showFeaturedOnly`),
2. rendering a ToggleControl in the Dynamic Archive **Filters** slot,
3. mutating query args in PHP.

## Install (local development)

1. Copy the `dynamic-archive-extension` directory to your WordPress plugins directory.
2. Activate **ACME Dynamic Archive Extension (Example)** in wp-admin.
3. Edit a page/template with a Dynamic Archive block.
4. Open block Inspector -> **Filters** panel.
5. Toggle **Featured posts only** and verify results on frontend.

## Notes

- This is a reference implementation for extension authors.
- It relies on the experimental extensibility API from this plugin.
