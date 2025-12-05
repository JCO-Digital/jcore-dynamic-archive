import {
	getContext,
	getElement,
	getServerContext,
	splitTask,
	store,
	withScope,
} from '@wordpress/interactivity';
import qs from 'qs';
import { cloneDeep } from 'es-toolkit/object';
import { debounce } from 'es-toolkit/function';
import _debug from 'debug';
const debug = _debug('dynamic-archive:frontend');

/** @typedef {string} TaxonomyName */
/** @typedef {string} FilterName */
/** @typedef {string[]|number[]} TaxonomyValues */
/** @typedef {Record<TaxonomyName, TaxonomyValues>} TaxonomyState */
/** @typedef {Record<FilterName, TaxonomyState>} FilterState */

const buildParamName = (instanceId, name) => {
	return `${state.prefix}${name}`;
};

const isValidLink = (ref) =>
	ref &&
	ref instanceof window.HTMLAnchorElement &&
	ref.href &&
	(!ref.target || ref.target === '_self') &&
	ref.origin === window.location.origin;

const isValidEvent = (event) =>
	event.button === 0 && // Left clicks only.
	!event.metaKey && // Open in new tab (Mac).
	!event.ctrlKey && // Open in new tab (Windows).
	!event.altKey && // Download.
	!event.shiftKey &&
	!event.defaultPrevented;

/**
 * Parses required attributes from a filter element.
 * @param {Event} event
 * @param {HTMLElement} ref
 * @param {HTMLAttributes} attributes
 * @returns {[*,*]}
 */
const parseAttributes = (event, ref, attributes) => {
	if (getNestedValue(attributes, ['data-filter-type'], undefined) === 'dropdown') {
		const taxonomyName = getNestedValue(attributes, ['data-taxonomy'], undefined);
		const value = getNestedValue(event, ['target', 'value'], undefined);
		return [
			getNestedValue(attributes, ['data-filter-type'], undefined),
			taxonomyName,
			value,
			getNestedValue(attributes, ['data-is-child'], false),
		];
	}
	const taxonomyName = getNestedValue(attributes, ['data-taxonomy'], undefined);
	const value = getNestedValue(attributes, ['data-term'], undefined);
	return [
		getNestedValue(attributes, ['data-filter-type'], undefined),
		taxonomyName,
		value,
		getNestedValue(attributes, ['data-is-child'], false),
	];
};

/**
 * Builds a filter url for the dynamic archive block.
 *
 * @param {string|Number} blockId - The block id.
 * @param {boolean} isInfiniteScroll - Whether the block is infinite scroll.
 * @param {string|number} currentPage - The current page number.
 * @param {string} type - The type of filter to apply.
 * @param {FilterState} filterState - The filter state to update.
 * @param {string} taxonomyName - The taxonomy name to filter by.
 * @param {any} value - The value to filter by.
 * @returns {string} - The url to navigate to.
 */
const buildFilterUrl = ({
	blockId,
	isInfiniteScroll,
	currentPage,
	type,
	filterState,
	taxonomyName = '',
	value,
	skipSetup = false,
	paramName = 'taxonomy',
}) => {
	const taxonomyKey = buildParamName(blockId, paramName);

	if (!skipSetup) setupFilter(filterState, taxonomyKey, taxonomyName);

	switch (type) {
		case 'checkbox':
			handleToggle(filterState, taxonomyKey, taxonomyName, value);
			break;
		case 'radio':
			handleRadio(filterState, taxonomyKey, taxonomyName, value);
			break;
		case 'dropdown':
			handleRadio(filterState, taxonomyKey, taxonomyName, value);
			break;
		case 'text':
			handleText(filterState, taxonomyKey, value);
		default:
			break;
	}

	const url = new URL(window.location.href);
	const parsedUrl = qs.parse(url.search.replaceAll('?', ''));
	let urlState = {
		...parsedUrl,
		...filterState,
	};
	const parsedPage = parseInt(currentPage);
	if (isInfiniteScroll && !isNaN(parsedPage) && parsedPage > 1) {
		urlState[buildParamName(blockId, 'archive-paged')] = currentPage;
	} else {
		delete urlState[buildParamName(blockId, 'archive-paged')];
	}
	url.search = qs.stringify(urlState, {
		encode: false,
	});
	return url.toString();
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

/**
 * Setups the filter for the given taxonomy key and name
 *
 * @param {object} filters - The filters object
 * @param {string} taxonomyKey - The taxonomy key
 * @param {string} taxonomyName - The taxonomy name
 */
const setupFilter = (filters, taxonomyKey, taxonomyName) => {
	if (!filters[taxonomyKey]) {
		filters[taxonomyKey] = {};
	}
	if (!filters[taxonomyKey][taxonomyName]) {
		filters[taxonomyKey][taxonomyName] = [];
	}
};

/**
 * Handles toggle buttons (multiple can be selected at a time)
 * @param {object} filters - The filters object
 * @param {string} taxonomyKey - The taxonomy key
 * @param {string} taxonomyName - The taxonomy name
 * @param {any} value - The value to filter by.
 *
 * @returns {void}
 */
const handleToggle = (filters, taxonomyKey, taxonomyName, value) => {
	if (filters[taxonomyKey][taxonomyName].includes(value)) {
		filters[taxonomyKey][taxonomyName] = filters[taxonomyKey][taxonomyName].filter(
			(item) => item !== value
		);
	} else {
		filters[taxonomyKey][taxonomyName] = [...(filters[taxonomyKey][taxonomyName] || []), value];
	}
};

/**
 * Handles radio buttons (only one can be selected at a time)
 * @param {object} filters - The filters object
 * @param {string} taxonomyKey - The taxonomy key
 * @param {string} taxonomyName - The taxonomy name
 * @param {any} value - The value to filter by.
 *
 * @returns {void}
 */
const handleRadio = (filters, taxonomyKey, taxonomyName, value) => {
	// If value is empty, clear the filter.
	if (!value) {
		filters[taxonomyKey][taxonomyName] = [];
		return;
	}
	const currentChildren = state.children[taxonomyName] ?? [];
	const isChild = currentChildren.includes(parseInt(value));
	// If the value is already in the filters, remove it
	if (filters[taxonomyKey][taxonomyName].includes(value)) {
		// If the value is a child, then only remove the child (since we don't want to remove the parent)
		if (isChild) {
			filters[taxonomyKey][taxonomyName] = filters[taxonomyKey][taxonomyName].filter(
				(term) => term !== value
			);
			return;
		}
		filters[taxonomyKey][taxonomyName] = [];
	} else if (isChild) {
		// If the value is a child term, then add it (don't overwrite the parent)
		filters[taxonomyKey][taxonomyName] = [...(filters[taxonomyKey][taxonomyName] || []), value];
	} else {
		// If the value is a parent term, then overwrite the filters.
		filters[taxonomyKey][taxonomyName] = [value];
	}
};

const handleText = (filters, taxonomyKey, value) => {
	if (!value) {
		filters[taxonomyKey] = [];
		return;
	}
	filters[taxonomyKey] = value;
};

const { state } = store('jcore/dynamic-archive', {
	state: {
		get children() {
			const context = getContext();
			if (!context.terms) {
				return {};
			}
			return Object.entries(context.terms).reduce((acc, [taxonomyName, taxonomy]) => {
				if (!taxonomy.hierarchical) {
					return acc;
				}
				const children = taxonomy.terms
					.filter((term) => term.isChild)
					.map((term) => term.id);
				if (children.length > 0) {
					acc[taxonomyName] = children;
				}
				return acc;
			}, {});
		},
		get filterTypes() {
			const context = getContext();
			if (!context.terms) {
				return {};
			}
			return Object.entries(context.terms).reduce((acc, [taxonomyName, taxonomy]) => {
				let types = {
					type: taxonomy.filterType,
				};
				if (taxonomy.hierarchical) {
					types = {
						...types,
						childType: taxonomy.filterTypeChild,
					};
				}
				acc[taxonomyName] = types;
				return acc;
			}, {});
		},
	},
	actions: {
		*filterChange(event) {
			const element = getElement();
			const { attributes } = element;
			const [type, taxonomyName, value] = parseAttributes(event, element.ref, attributes);
			// Bail early if we don't have a taxonomy name.
			if (!taxonomyName) {
				return;
			}
			const context = getContext();
			const { filters, blockId, isInfiniteScroll, currentPage } = context;
			// TODO: Figure out if we need this, or we can use the exported context in the functions.
			const newUrl = buildFilterUrl({
				blockId,
				type,
				filterState: filters,
				taxonomyName,
				value,
				isInfiniteScroll,
				currentPage,
			});
			context.isLoading = true;
			const { actions } = yield import('@wordpress/interactivity-router');
			yield actions.navigate(newUrl);
			context.isLoading = false;
		},
		*searchInputChange(event) {
			const debouncedSearch = debounce(
				withScope(async (event) => {
					const value = event.target.value;
					const context = getContext();

					const { filters, blockId, isInfiniteScroll, currentPage } = context;
					const newUrl = buildFilterUrl({
						blockId,
						type: 'text',
						filterState: filters,
						value,
						isInfiniteScroll,
						currentPage,
						skipSetup: true,
						paramName: 'search',
					});
					context.searchTerm = value;
					context.isLoading = true;
					const { actions } = await import('@wordpress/interactivity-router');
					await actions.navigate(newUrl);
					context.isLoading = false;
				}),
				500
			); // 200ms debounce
			debouncedSearch(event);
		},
		*prefetchFilter(event) {
			const element = getElement();
			/** @type {HTMLLabelElement} */
			const ref = element.ref;
			if (ref.tagName !== 'LABEL') {
				return;
			}
			const labelTarget = ref.htmlFor;
			const target = document.getElementById(labelTarget);
			if (!target) {
				return;
			}
			const taxonomyName = target.getAttribute('data-taxonomy');
			const value = target.getAttribute('data-term');
			const type = target.getAttribute('data-filter-type');
			if (!taxonomyName || !value || !type) {
				return;
			}
			const context = getContext();
			const { blockId, filters, isInfiniteScroll, currentPage } = context;
			const fakeFilter = cloneDeep(filters);
			const newUrl = buildFilterUrl({
				blockId,
				type,
				filterState: fakeFilter,
				taxonomyName,
				value,
				isInfiniteScroll,
				currentPage,
			});
			context.isPrefetching = true;
			const { actions } = yield import('@wordpress/interactivity-router');
			yield actions.prefetch(newUrl);
			context.isPrefetching = false;
		},
		*pageChange(event) {
			const element = getElement();
			const context = getContext();
			/** @type {HTMLAnchorElement} */
			const ref = element.ref;
			const parentEl = ref.closest('[data-wp-interactive="jcore/dynamic-archive"]');
			if (!isValidLink(ref) || !isValidEvent(event)) {
				return;
			}
			event.preventDefault();
			yield splitTask();
			context.isLoading = true;
			const { actions } = yield import('@wordpress/interactivity-router');
			yield actions.navigate(ref.href);
			context.isLoading = false;
			if (!context.isInfiniteScroll && parentEl) {
				parentEl.scrollIntoView({
					behavior: 'smooth',
				});
			}
		},
		*prefetchPage(event) {
			const element = getElement();
			const context = getContext();
			/** @type {HTMLAnchorElement} */
			const ref = element.ref;
			if (!isValidLink(ref) || !isValidEvent(event)) {
				return;
			}
			context.isPrefetching = true;
			const { actions } = yield import('@wordpress/interactivity-router');
			yield actions.prefetch(ref.href);
			context.isPrefetching = false;
		},
		*labelKeyDown(event) {
			const element = getElement();
			const context = getContext();
			/** @type {HTMLLabelElement} */
			const ref = element.ref;
			if (ref.tagName !== 'LABEL') {
				return;
			}
			const target = ref.htmlFor;
			const input = document.getElementById(target);
			if (!input) {
				return;
			}
			if (event.keyCode === 13 || event.keyCode === 32) {
				input.click();
				event.preventDefault();
				event.stopPropagation();
			}
		},
	},
	callbacks: {
		updateServerContext() {
			// Handles updating the current page number from the server.
			const context = getContext();
			const serverContext = getServerContext();
			if (serverContext.currentPage) {
				if (!isNaN(parseInt(serverContext.currentPage))) {
					context.currentPage = parseInt(serverContext.currentPage);
				}
			}
			if (serverContext.terms) {
				context.terms = serverContext.terms;
			}
		},
	},
});
