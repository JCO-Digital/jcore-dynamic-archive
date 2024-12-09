/**
 * Hooks
 */
import { useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import usePostTypes from "@/shared/usePostTypes";
import { applyFilters } from "@wordpress/hooks";
import { useState } from "@wordpress/element";
import { useSelect } from "@wordpress/data";
import { store as coreDataStore } from "@wordpress/core-data";

/**
 * Components
 */
import ServerSideRender from "@wordpress/server-side-render";
import { InspectorControls } from "@wordpress/block-editor";
import {
	PanelBody,
	Spinner,
	CheckboxControl,
	__experimentalText as Text,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
	__experimentalSpacer as Spacer,
	RangeControl,
	QueryControls,
} from "@wordpress/components";
import { settings, layout } from "@wordpress/icons";

/**
 * Dependencies
 */
import _debug from "debug";
const debug = _debug("latest-posts:Edit");

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
	const { postTypes, automatic, columns, postsPerPage, order, orderBy } =
		attributes;

	const { postTypes: _postTypes, loading: postTypeLoading } = usePostTypes();

	const postTypesToShow = applyFilters(
		"jcore.latestPosts.showPostTypes",
		_postTypes,
	);

	const [taxonomiesLoading, setTaxonomyLoading] = useState(false);

	const taxonomies = useSelect(
		(select) => {
			if (!postTypes || postTypes.length === 0) {
				return [];
			}
			setTaxonomyLoading(true);
			const allTaxonomies = [];
			for (const postType of postTypes) {
				const taxonomies = select(coreDataStore).getTaxonomies({
					per_page: -1,
					type: postType,
				});
				if (!taxonomies) {
					continue;
				}
				allTaxonomies.push(...taxonomies);
			}
			if (!allTaxonomies) {
				setTaxonomyLoading(false);
				return [];
			}
			const taxonomyTerms = [];
			for (const taxonomy of allTaxonomies) {
				const terms = select(coreDataStore).getEntityRecords(
					"taxonomy",
					taxonomy.slug,
					{ per_page: -1, hide_empty: true },
				);
				if (!terms || terms.length === 0) {
					continue;
				}
				taxonomyTerms.push(...terms);
			}
			setTaxonomyLoading(false);
			return taxonomyTerms;
		},
		[postTypes],
	);

	const maxSelected = applyFilters("jcore.latestPosts.maxSelected", -1);

	const handlePostTypesChange = (postType) => {
		const postTypeIndex = postTypes.indexOf(postType);
		if (postTypeIndex === -1) {
			setAttributes({ postTypes: [...postTypes, postType] });
		} else {
			setAttributes({
				postTypes: postTypes.toSpliced(postTypeIndex, 1),
			});
		}
	};

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={__("Settings", "jcore-dynamic-archive")}
					icon={postTypeLoading ? <Spinner size={5} /> : settings}
				>
					<Spacer marginBottom={6}>
						<VStack>
							{postTypesToShow.map((postType) => (
								<CheckboxControl
									disabled={
										!postTypes.includes(postType.slug) &&
										maxSelected > 0 &&
										postTypes.length >= maxSelected
									}
									key={postType.slug}
									label={postType.name}
									checked={postTypes.includes(postType.slug)}
									onChange={() => handlePostTypesChange(postType.slug)}
									__nextHasNoMarginBottom
								/>
							))}
							{postTypes.length >= maxSelected && (
								<Text>
									{sprintf(
										__(
											"You have selected the maximum amount of post types. (%s)",
											"jcore",
										),
										maxSelected,
									)}
								</Text>
							)}
						</VStack>
					</Spacer>
					<Spacer marginBottom={6}>
						<VStack>
							{taxonomiesLoading && (
								<HStack>
									<Spinner />
									<Text>{__("Loading taxonomies...", "dynamic-archive")}</Text>
								</HStack>
							)}
							{!taxonomiesLoading && (
								<QueryControls
									numberOfItems={postsPerPage}
									onNumberOfItemsChange={(value) =>
										setAttributes({ postsPerPage: value })
									}
									maxItems={applyFilters("jcore.latestPosts.maxItems", 25)}
									minItems={1}
									order={order}
									orderBy={orderBy}
									onOrderChange={(value) => setAttributes({ order: value })}
									onOrderByChange={(value) => setAttributes({ orderBy: value })}
									categorySuggestions={taxonomies.map((taxonomy) => ({
										id: taxonomy.term_id,
										name: taxonomy.name,
										parent: taxonomy.parent,
									}))}
									onCategoryChange={(value) =>
										setAttributes({ category: value.id })
									}
								/>
							)}
						</VStack>
					</Spacer>
				</PanelBody>
				<PanelBody title={__("Layout", "jcore")} icon={layout}>
					<RangeControl
						label={__("Columns", "dynamic-archive")}
						value={columns || 3}
						onChange={(value) => {
							if (isNaN(parseInt(value))) {
								return;
							}
							setAttributes({ columns: parseInt(value) });
						}}
						min={1}
						max={5}
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>
			</InspectorControls>
			<div {...useBlockProps()}>
				<ServerSideRender
					block="jcore/latest-posts"
					attributes={attributes}
					httpMethod={"POST"}
				/>
			</div>
		</>
	);
}
