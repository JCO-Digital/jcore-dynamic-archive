/**
 * Hooks
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import usePostTypes from '@/shared/usePostTypes';
import { applyFilters } from '@wordpress/hooks';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import getQueryContextFromTemplate from '@/shared/useQueryContextFromTemplate';

/**
 * Components
 */
import ServerSideRender from '@wordpress/server-side-render';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	Spinner,
	CheckboxControl,
	ToggleControl,
	__experimentalText as Text,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
	__experimentalSpacer as Spacer,
	RangeControl,
	QueryControls,
	SelectControl,
	Disabled,
} from '@wordpress/components';
import { settings, layout, funnel } from '@wordpress/icons';

/**
 * Dependencies
 */
import _debug from 'debug';
const debug = _debug('latest-posts:Edit');

/**
 * Styles
 */
import './editor.css';
import TaxonomyPicker from '@/shared/components/TaxonomyPicker';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes, context }) {
	const {
		postTypes,
		related,
		columns,
		postsPerPage,
		order,
		orderBy,
		selectedTaxonomies,
		sticky,
		expanded,
		inherit,
	} = attributes;

	const { isSingular } = getQueryContextFromTemplate(context?.templateSlug);

	if (isSingular === true && inherit) {
		setAttributes({ inherit: false });
	}

	const { postTypes: _postTypes, loading: postTypeLoading } = usePostTypes();

	const postTypesToShow = applyFilters(
		'jcore.latestPosts.showPostTypes',
		_postTypes
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
			setTaxonomyLoading(false);
			return applyFilters(
				'jcore.latestPosts.taxonomies',
				allTaxonomies,
				postTypes
			);
		},
		[postTypes]
	);

	const maxSelected = applyFilters('jcore.latestPosts.maxSelected', -1);

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
					title={__('Settings', 'jcore-dynamic-archive')}
					icon={postTypeLoading ? <Spinner size={5} /> : settings}
					initialOpen={expanded}
				>
					{!isSingular && !related && (
						<ToggleControl
							label={__(
								'Inherit query from template',
								'jcore-dynamic-archive'
							)}
							checked={inherit}
							onChange={(checked) =>
								setAttributes({ inherit: checked })
							}
							__nextHasNoMarginBottom
						/>
					)}
					{!inherit && !related && (
						<Spacer marginBottom={6}>
							<VStack>
								{postTypesToShow.map((postType) => (
									<CheckboxControl
										disabled={
											!postTypes.includes(
												postType.slug
											) &&
											maxSelected > 0 &&
											postTypes.length >= maxSelected
										}
										key={postType.slug}
										label={postType.name}
										checked={postTypes.includes(
											postType.slug
										)}
										onChange={() =>
											handlePostTypesChange(postType.slug)
										}
										__nextHasNoMarginBottom
									/>
								))}
								{maxSelected > 0 &&
									postTypes.length >= maxSelected && (
										<Text>
											{sprintf(
												__(
													'You have selected the maximum amount of post types. (%s)',
													'jcore'
												),
												maxSelected
											)}
										</Text>
									)}
							</VStack>
						</Spacer>
					)}
					{!inherit && (
						<Spacer marginBottom={6}>
							<VStack>
								{taxonomiesLoading && (
									<HStack alignment={'left'}>
										<Spinner />
										<Text>
											{__(
												'Loading taxonomies...',
												'jcore-dynamic-archive'
											)}
										</Text>
									</HStack>
								)}
								{!taxonomiesLoading && (
									<QueryControls
										numberOfItems={postsPerPage}
										onNumberOfItemsChange={(value) =>
											setAttributes({ postsPerPage: value })
										}
										maxItems={applyFilters(
											'jcore.latestPosts.maxItems',
											25
										)}
										minItems={1}
										order={order}
										orderBy={orderBy}
										onOrderChange={(value) =>
											setAttributes({ order: value })
										}
										onOrderByChange={(value) =>
											setAttributes({ orderBy: value })
										}
										onCategoryChange={(value) =>
											setAttributes({ category: value.id })
										}
									/>
								)}
								{postTypes && postTypes.includes('post') && (
									<SelectControl
										label={__(
											'Sticky post behavior',
											'jcore-dynamic-archive'
										)}
										options={[
											{
												label: __(
													'Include',
													'jcore-dynamic-archive'
												),
												value: 'include',
											},
											{
												label: __(
													'Exclude',
													'jcore-dynamic-archive'
												),
												value: 'exclude',
											},
											{
												label: __(
													'Only',
													'jcore-dynamic-archive'
												),
												value: 'only',
											},
										]}
										onChange={(value) =>
											setAttributes({ sticky: value })
										}
										value={sticky}
										__nextHasNoMarginBottom
										__next40pxDefaultSize
									/>
								)}
							</VStack>
						</Spacer>
					)}
				</PanelBody>
				<PanelBody
					title={__('Layout', 'jcore')}
					icon={layout}
					initialOpen={expanded}
				>
					<RangeControl
						label={__('Columns', 'jcore-dynamic-archive')}
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
				{!related && !inherit && (
					<PanelBody
						title={__('Filters', 'jcore-dynamic-archive')}
						icon={funnel}
						initialOpen={expanded}
					>
						<Spacer marginBottom={6}>
							<VStack>
								{!taxonomiesLoading &&
									taxonomies.map((taxonomy) => (
										<TaxonomyPicker
											taxonomySlug={taxonomy.slug}
											onChange={(value) =>
												setAttributes({
													selectedTaxonomies: {
														...selectedTaxonomies,
														[taxonomy.slug]: value,
													},
												})
											}
											value={
												selectedTaxonomies[
													taxonomy.slug
												] ?? []
											}
										/>
									))}
							</VStack>
						</Spacer>
					</PanelBody>
				)}
			</InspectorControls>
			<div {...useBlockProps()}>
				<Disabled isDisabled={true}>
					<ServerSideRender
						block="jcore/latest-posts"
						attributes={attributes}
						httpMethod={'POST'}
					/>
				</Disabled>
			</div>
		</>
	);
}
