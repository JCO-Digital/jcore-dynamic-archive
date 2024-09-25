/**
 * Hooks
 */
import { __ } from "@wordpress/i18n";
import { useSelect } from "@wordpress/data";
import { useEffect, useState } from "@wordpress/element";
import { useInstanceId } from "@wordpress/compose";
import { useBlockProps } from "@wordpress/block-editor";

/**
 * Components
 */
import ServerSideRender from "@wordpress/server-side-render";
import { InspectorControls } from "@wordpress/block-editor";
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	TextControl,
	RangeControl,
	CheckboxControl,
} from "@wordpress/components";

/**
 * Styles
 */
import "./editor.css";

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const {
		postType,
		perPage,
		columns,
		masonryGrid,
		showPagination,
		infiniteScroll,
	} = attributes;
	const instanceId = useInstanceId(Edit);
	setAttributes({ instanceId: instanceId.toString() });

	// BEGIN: Post Types
	const { _postTypes } = useSelect(
		(select) => ({ _postTypes: select("core").getPostTypes() }),
		[],
	);
	const [postTypes, setPostTypes] = useState([]);

	const forbiddenPostTypes = ["attachment"];

	useEffect(() => {
		if (_postTypes) {
			setPostTypes(
				_postTypes
					.filter(
						(postType) =>
							postType.viewable && !forbiddenPostTypes.includes(postType.slug),
					)
					.map((postType) => ({
						label: postType.name,
						value: postType.slug,
					})),
			);
		}
	}, [_postTypes]);
	// END: Post Types

	// BEGIN: Per Page
	const { _site } = useSelect(
		(select) => ({ _site: select("core").getSite() }),
		[],
	);
	const [_perPage, setPerPage] = useState(5);

	useEffect(() => {
		if (attributes.perPage) {
			return;
		}
		if (_site?.posts_per_page) {
			console.log(_site);
			setPerPage(_site.posts_per_page);
		}
	}, [_site]);
	// END: Per Page

	// BEGIN: Order
	const { order, orderBy } = attributes;
	const orderOptions = [
		{ label: __("Ascending", "jcore-dynamic-archive"), value: "ASC" },
		{ label: __("Descending", "jcore-dynamic-archive"), value: "DESC" },
	];
	const orderByOptions = [
		{ label: __("Date", "jcore-dynamic-archive"), value: "date" },
		{ label: __("Title", "jcore-dynamic-archive"), value: "title" },
		{ label: __("Modified", "jcore-dynamic-archive"), value: "modified" },
		{ label: __("Author", "jcore-dynamic-archive"), value: "author" },
		{ label: __("ID", "jcore-dynamic-archive"), value: "ID" },
		{
			label: __("Menu order", "jcore-dynamic-archive"),
			value: "menu_order",
		},
	];
	// END: Order

	// BEGIN: Taxonomies
	const { taxonomies } = attributes;
	const [taxonomyOptions, setTaxonomyOptions] = useState([]);
	const { _taxonomies } = useSelect(
		(select) => ({ _taxonomies: select("core").getTaxonomies() }),
		[],
	);
	useEffect(() => {
		if (_taxonomies) {
			const filteredTaxonomies = _taxonomies
				.filter((taxonomy) => taxonomy.types?.includes(postType))
				.map((taxonomy) => ({
					label: taxonomy.name,
					value: taxonomy.slug,
				}));
			setTaxonomyOptions(filteredTaxonomies);
			const newStoredTaxonomies = taxonomies.filter((taxonomy) =>
				filteredTaxonomies.map((t) => t.value).includes(taxonomy),
			);
			setAttributes({ taxonomies: newStoredTaxonomies });
		}
	}, [_taxonomies, postType]);

	return (
		<>
			<InspectorControls>
				<PanelBody title={__("Settings", "jcore-dynamic-archive")}>
					<SelectControl
						label={__("Post Type", "jcore-dynamic-archive")}
						value={postType}
						options={postTypes}
						onChange={(value) => setAttributes({ postType: value })}
					/>
					{taxonomyOptions.length > 0 && <p>Taxonomies</p>}
					{taxonomyOptions.map((taxonomy) => (
						<CheckboxControl
							label={__(taxonomy.label, "jcore-dynamic-archive")}
							checked={taxonomies.includes(taxonomy.value)}
							onChange={(_checked) =>
								setAttributes({
									taxonomies: taxonomies.includes(taxonomy.value)
										? taxonomies.filter((t) => t !== taxonomy.value)
										: [...taxonomies, taxonomy.value],
								})
							}
						/>
					))}
					<ToggleControl
						label={__("Show pagination", "jcore-dynamic-archive")}
						checked={showPagination}
						onChange={(checked) => setAttributes({ showPagination: checked })}
					/>
					{showPagination && (
						<ToggleControl
							label={__("Infinite scroll", "jcore-dynamic-archive")}
							checked={infiniteScroll}
							onChange={(checked) => setAttributes({ infiniteScroll: checked })}
						/>
					)}
					<SelectControl
						label={__("Order", "jcore-dynamic-archive")}
						value={order}
						options={orderOptions}
						onChange={(value) => setAttributes({ order: value })}
					/>
					<SelectControl
						label={__("Order by", "jcore-dynamic-archive")}
						value={orderBy}
						options={orderByOptions}
						onChange={(value) => setAttributes({ orderBy: value })}
					/>
				</PanelBody>
				<PanelBody title={__("Layout", "jcore-dynamic-archive")}>
					<ToggleControl
						label={__("Masonry Grid", "jcore-dynamic-archive")}
						checked={masonryGrid}
						onChange={(checked) => setAttributes({ masonryGrid: checked })}
					/>
					<RangeControl
						label={__("Columns", "jcore-dynamic-archive")}
						value={columns || 3}
						onChange={(value) => {
							if (isNaN(parseInt(value))) {
								return;
							}
							setAttributes({ columns: parseInt(value) });
						}}
						min={1}
						max={4}
					/>
					<TextControl
						label={__("Posts per Page", "jcore-dynamic-archive")}
						value={perPage || _perPage}
						onChange={(value) => {
							if (isNaN(parseInt(value))) {
								return;
							}
							setAttributes({ perPage: parseInt(value) });
						}}
						type="number"
						min={1}
						max={100}
					/>
				</PanelBody>
			</InspectorControls>
			<div {...useBlockProps()}>
				<ServerSideRender
					block="jcore/dynamic-archive"
					attributes={attributes}
				/>
			</div>
		</>
	);
}
