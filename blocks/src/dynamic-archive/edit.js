/**
 * Hooks
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import { useBlockProps } from '@wordpress/block-editor';
import { settings, layout, funnel } from '@wordpress/icons';
import _debug from 'debug';
const debug = _debug('dynamic-archive:Edit');

/**
 * Components
 */
import ServerSideRender from '@wordpress/server-side-render';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	TextControl,
	RangeControl,
	CheckboxControl,
	Flex,
	FlexItem,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
	Spinner,
	FlexBlock,
	Disabled,
} from '@wordpress/components';
import useQueryContextFromTemplate from '@/shared/useQueryContextFromTemplate';
/**
 * Styles
 */
import './editor.css';
import ToggleWrapper from '@/shared/components/ToggleWrapper';
import usePostTypes from '@/shared/usePostTypes';
import useSiteSetting from '@/shared/useSiteSetting';
import useTaxonomies from '@/shared/useTaxonomies';
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
		hideChildren,
		postType,
		perPage,
		columns,
		masonryGrid,
		showPagination,
		showAllLanguages,
		infiniteScroll,
		sticky,
		filterTypes,
		filterTypesChild,
		forcedCategories,
		taxonomies,
		hierarchicalFilter,
		inherit,
	} = attributes;
	const { isSingular } = useQueryContextFromTemplate(context.templateSlug);

	if (isSingular === true) {
		setAttributes({ inherit: false });
	}

	const instanceId = useInstanceId(Edit);
	setAttributes({ instanceId: instanceId.toString() });

	// BEGIN: Post Types
	const { postTypes, loading: postTypeLoading } = usePostTypes();

	const isPostTypeHierarchical = postTypes
		.filter((p) => p.slug === postType)
		.some((p) => {
			return p.hierarchical;
		});

	const handlePostTypeChange = (value) => {
		setAttributes({
			postType: value,
			taxonomies: [],
			filterTypes: {},
			filterTypesChild: {},
			hierarchicalFilter: {},
			forcedCategories: {},
		});
	};
	// END: Post Types

	// BEGIN: Per Page
	const _sitePerPage = useSiteSetting('posts_per_page', 5);
	useEffect(() => {
		if (attributes.perPage) {
			return;
		}
		setAttributes({ perPage: _sitePerPage });
	}, [_sitePerPage]);
	// END: Per Page

	// BEGIN: Order
	const { order, orderBy } = attributes;
	const orderOptions = [
		{ label: __('Ascending', 'jcore-dynamic-archive'), value: 'ASC' },
		{ label: __('Descending', 'jcore-dynamic-archive'), value: 'DESC' },
	];
	const orderByOptions = [
		{ label: __('Date', 'jcore-dynamic-archive'), value: 'date' },
		{ label: __('Title', 'jcore-dynamic-archive'), value: 'post_title' },
		{ label: __('Modified', 'jcore-dynamic-archive'), value: 'modified' },
		{ label: __('Author', 'jcore-dynamic-archive'), value: 'author' },
		{ label: __('ID', 'jcore-dynamic-archive'), value: 'ID' },
		{
			label: __('Menu order', 'jcore-dynamic-archive'),
			value: 'menu_order',
		},
	];
	// END: Order

	// BEGIN: Taxonomies
	const [taxonomyOptions, setTaxonomyOptions] = useState([]);

	const { taxonomies: _taxonomies, loading: taxonomiesLoading } = useTaxonomies(postType);

	useEffect(() => {
		if (_taxonomies) {
			const filteredTaxonomies = _taxonomies.map((taxonomy) => ({
				label: taxonomy.name,
				value: taxonomy.slug,
				id: taxonomy.slug,
				hierarchical: taxonomy.hierarchical,
			}));
			setTaxonomyOptions(filteredTaxonomies ?? []);
			const newStoredTaxonomies = taxonomies.filter((taxonomy) =>
				filteredTaxonomies.map((t) => t.value).includes(taxonomy)
			);
			setAttributes({ taxonomies: newStoredTaxonomies });
		} else {
			setTaxonomyOptions([]);
		}
	}, [_taxonomies]);

	const filterTypesOptions = [
		{ label: __('Checkbox', 'jcore-dynamic-archive'), value: 'checkbox' },
		{ label: __('Radio', 'jcore-dynamic-archive'), value: 'radio' },
		{ label: __('Dropdown', 'jcore-dynamic-archive'), value: 'dropdown' },
	];

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={__('Settings', 'jcore-dynamic-archive')}
					icon={postTypeLoading ? <Spinner size={5} /> : settings}
				>
					{!isSingular && (
						<ToggleControl
							label={__('Inherit settings from query', 'jcore-dynamic-archive')}
							checked={inherit}
							onChange={(checked) => setAttributes({ inherit: checked })}
						/>
					)}
					<VStack spacing={4} className={'jcore__dynamic-archive-post-type'}>
						<HStack spacing={2}>
							{!inherit && (
								<SelectControl
									label={__('Post Type', 'jcore-dynamic-archive')}
									value={postType}
									options={postTypes.map((postType) => ({
										label: postType.name,
										value: postType.slug,
									}))}
									onChange={handlePostTypeChange}
									__nextHasNoMarginBottom
									__next40pxDefaultSize
								/>
							)}
							{postTypeLoading && <Spinner />}
						</HStack>
						{!inherit && isPostTypeHierarchical && (
							<ToggleControl
								label={__('Hide children', 'jcore-dynamic-archive')}
								checked={hideChildren}
								onChange={(checked) => setAttributes({ hideChildren: checked })}
							/>
						)}
						<ToggleControl
							label={__('Show all languages', 'jcore-dynamic-archive')}
							checked={showAllLanguages}
							onChange={(checked) => setAttributes({ showAllLanguages: checked })}
						/>
					</VStack>
					<ToggleWrapper
						label={__('Show pagination', 'jcore-dynamic-archive')}
						checked={showPagination}
						setAttributes={setAttributes}
						attributeName="showPagination"
					>
						<ToggleControl
							label={__('Infinite scroll', 'jcore-dynamic-archive')}
							checked={infiniteScroll}
							onChange={(checked) => setAttributes({ infiniteScroll: checked })}
							__nextHasNoMarginBottom
						/>
					</ToggleWrapper>
					{!inherit && (
						<>
							<SelectControl
								label={__('Order', 'jcore-dynamic-archive')}
								value={order}
								options={orderOptions}
								onChange={(value) => setAttributes({ order: value })}
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
							<SelectControl
								label={__('Order by', 'jcore-dynamic-archive')}
								value={orderBy}
								options={orderByOptions}
								onChange={(value) => setAttributes({ orderBy: value })}
								__nextHasNoMarginBottom
								__next40pxDefaultSize
							/>
							{postType === 'post' && (
								<SelectControl
									label={__('Sticky post behavior', 'jcore-dynamic-archive')}
									options={[
										{
											label: __('Include', 'jcore-dynamic-archive'),
											value: 'include',
										},
										{
											label: __('Exclude', 'jcore-dynamic-archive'),
											value: 'exclude',
										},
										{
											label: __('Only', 'jcore-dynamic-archive'),
											value: 'only',
										},
									]}
									onChange={(value) => setAttributes({ sticky: value })}
									value={sticky}
									__nextHasNoMarginBottom
									__next40pxDefaultSize
								/>
							)}
						</>
					)}
				</PanelBody>
				<PanelBody title={__('Layout', 'jcore-dynamic-archive')} icon={layout}>
					<ToggleControl
						label={__('Masonry Grid', 'jcore-dynamic-archive')}
						checked={masonryGrid}
						onChange={(checked) => setAttributes({ masonryGrid: checked })}
						__nextHasNoMarginBottom
					/>
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
						max={4}
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
					<TextControl
						label={__('Posts per Page', 'jcore-dynamic-archive')}
						value={perPage || _sitePerPage}
						onChange={(value) => {
							if (isNaN(parseInt(value))) {
								return;
							}
							setAttributes({ perPage: parseInt(value) });
						}}
						type="number"
						min={1}
						max={100}
						__nextHasNoMarginBottom
						__next40pxDefaultSize
					/>
				</PanelBody>
				{!inherit && (
					<PanelBody
						title={__('Filters', 'jcore-dynamic-archive')}
						icon={taxonomiesLoading ? <Spinner size={5} /> : funnel}
					>
						{!taxonomiesLoading && (
							<>
								{taxonomyOptions.length > 0 && <p>Filters to show</p>}
								{taxonomyOptions.length === 0 && (
									<p>No filters available for selected post type</p>
								)}
								<VStack>
									{taxonomyOptions.map((taxonomy) => (
										<FlexItem
											key={taxonomy.id}
											className={'jcore-taxonomy-item'}
										>
											<CheckboxControl
												label={__(taxonomy.label, 'jcore-dynamic-archive')}
												checked={taxonomies.includes(taxonomy.value)}
												onChange={(_checked) =>
													setAttributes({
														taxonomies: taxonomies.includes(
															taxonomy.value
														)
															? taxonomies.filter(
																	(t) => t !== taxonomy.value
																)
															: [...taxonomies, taxonomy.value],
													})
												}
												__nextHasNoMarginBottom
											/>
											{taxonomies.includes(taxonomy.value) && (
												<>
													{taxonomy.hierarchical && (
														<ToggleControl
															label={__(
																'Hierarchical filter',
																'jcore-dynamic-archive'
															)}
															help={__(
																'If enabled, child categories will be hidden until parent category is selected',
																'jcore-dynamic-archive'
															)}
															checked={
																hierarchicalFilter[
																	taxonomy.value
																] ?? false
															}
															onChange={(value) =>
																setAttributes({
																	hierarchicalFilter: {
																		...hierarchicalFilter,
																		[taxonomy.value]: value,
																	},
																})
															}
															__nextHasNoMarginBottom
														/>
													)}
													<SelectControl
														label={
															hierarchicalFilter[taxonomy.value]
																? __(
																		'Filter type (Parent categories)',
																		'jcore-dynamic-archive'
																	)
																: __(
																		'Filter type',
																		'jcore-dynamic-archive'
																	)
														}
														value={filterTypes[taxonomy.value]}
														options={filterTypesOptions}
														onChange={(value) => {
															setAttributes({
																filterTypes: {
																	...filterTypes,
																	[taxonomy.value]: value,
																},
															});
														}}
														__nextHasNoMarginBottom
														__next40pxDefaultSize
													/>
													{hierarchicalFilter[taxonomy.value] && (
														<SelectControl
															label={__(
																'Filter type (Child categories)',
																'jcore-dynamic-archive'
															)}
															value={filterTypesChild[taxonomy.value]}
															options={filterTypesOptions}
															onChange={(value) => {
																setAttributes({
																	filterTypesChild: {
																		...filterTypesChild,
																		[taxonomy.value]: value,
																	},
																});
															}}
															__nextHasNoMarginBottom
															__next40pxDefaultSize
														/>
													)}
													<TaxonomyPicker
														taxonomySlug={taxonomy.value}
														onChange={(value) =>
															setAttributes({
																forcedCategories: {
																	...forcedCategories,
																	[taxonomy.value]: value,
																},
															})
														}
														value={forcedCategories[taxonomy.value]}
													></TaxonomyPicker>
												</>
											)}
										</FlexItem>
									))}
								</VStack>
							</>
						)}
					</PanelBody>
				)}
			</InspectorControls>
			<div {...useBlockProps()}>
				<Disabled isDisabled={true}>
					<ServerSideRender
						block="jcore/dynamic-archive"
						attributes={attributes}
						httpMethod={'POST'}
					/>
				</Disabled>
			</div>
		</>
	);
}
