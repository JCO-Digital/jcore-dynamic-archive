import { getContext, getElement, store } from "@wordpress/interactivity";
import qs from "qs";

/** @typedef {string} TaxonomyName */
/** @typedef {string} FilterName */
/** @typedef {string[]|number[]} TaxonomyValues */
/** @typedef {Record<TaxonomyName, TaxonomyValues>} TaxonomyState */
/** @typedef {Record<FilterName, TaxonomyState>} FilterState */

const buildParamName = (instanceId, name) => {
	return `dynamic-archive-${instanceId}-${name}`;
};

/**
 * Parses required attributes from a filter element.
 * @param {HTMLAttributes} attributes
 * @returns {[*,*]}
 */
const parseAttributes = (attributes) => {
	const taxonomyName = getNestedValue(attributes, ["data-taxonomy"], undefined);
	const value = getNestedValue(attributes, ["data-term"], undefined);
	return [taxonomyName, value];
};

/**
 * Builds a filter for the dynamic archive block.
 *
 * @param {string|Number}instanceId
 * @param {FilterState} state
 *
 * @returns {string}
 */
const buildUrl = (instanceId, state) => {
	return `${window.location.href}?${qs.stringify(state, {
		arrayFormat: "brackets",
		encode: false,
	})}`;
};

/**
 * Gets a nested value from an object.
 *
 * @param {Record<any, any>} obj
 * @param {string[]} keys
 * @param {any} defaultValue
 * @returns {unknown}
 */
const getNestedValue = (obj, keys, defaultValue = undefined) => {
	const key = keys.shift();
	if (!keys.length) {
		return obj[key];
	}
	if (!obj[key]) {
		return defaultValue;
	}
	return getNestedValue(obj[key], keys, defaultValue);
};

const { state } = store("jcore/dynamic-archive", {
	state: {},
	actions: {
		*toggleFilter(event) {
			const ref = getElement();
			const { attributes } = ref;
			const [taxonomyName, value] = parseAttributes(attributes);
			// Bail early if we don't have a taxonomy name or value.
			if (!taxonomyName || !value) {
				return;
			}
			const { filters, blockId } = getContext();
			const taxonomyKey = buildParamName(blockId, "taxonomy");
			if (!filters[taxonomyKey]) {
				filters[taxonomyKey] = {};
			}
			if (!filters[taxonomyKey][taxonomyName]) {
				filters[taxonomyKey][taxonomyName] = [];
			}
			filters[taxonomyKey][taxonomyName] = [
				...(filters[taxonomyKey][taxonomyName] || []),
				value,
			];
			const newUrl = buildUrl(blockId, filters);
			console.log(newUrl);
			const { actions } = yield import("@wordpress/interactivity-router");
			console.log(actions);
			yield actions.navigate(newUrl);
		},
	},
});
