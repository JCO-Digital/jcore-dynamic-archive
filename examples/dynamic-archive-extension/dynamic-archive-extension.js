(function (wp, dynamicArchiveGlobal) {
	if (
		!wp ||
		!wp.plugins ||
		!wp.components ||
		!wp.element ||
		!dynamicArchiveGlobal ||
		!dynamicArchiveGlobal.extensions
	) {
		return;
	}

	var registerPlugin = wp.plugins.registerPlugin;
	var ToggleControl = wp.components.ToggleControl;
	var __ = wp.i18n.__;
	var el = wp.element.createElement;

	var DynamicArchiveInspectorFiltersFill =
		dynamicArchiveGlobal.extensions.DynamicArchiveInspectorFiltersFill;

	if (!DynamicArchiveInspectorFiltersFill) {
		return;
	}

	var ExtensionFill = function () {
		return el(DynamicArchiveInspectorFiltersFill, null, function (fillContext) {
			if (!fillContext || typeof fillContext.setAttributes !== 'function') {
				return null;
			}

			var attributes = fillContext.attributes || {};

			return el(ToggleControl, {
				label: __('Featured posts only', 'jcore-dynamic-archive'),
				checked: !!attributes.acme_showFeaturedOnly,
				onChange: function (value) {
					fillContext.setAttributes({
						acme_showFeaturedOnly: !!value,
					});
				},
			});
		});
	};

	registerPlugin('acme-dynamic-archive-extension', {
		render: ExtensionFill,
	});
})(window.wp, window.jcoreDynamicArchive);
