/**
 * Hooks
 */
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import useTaxonomies from '@/shared/useTaxonomies';
import useTaxonomyRecords from '@/shared/useTaxonomyRecords';

/**
 * Components
 */
import { ServerSideRender } from '@wordpress/server-side-render';
import {
	ComboboxControl,
	Disabled,
	PanelBody,
	Placeholder,
	QueryControls,
	RadioControl,
	RangeControl,
	SelectControl,
	Spinner,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { settings, layout } from '@wordpress/icons';
import PostPicker from './PostPicker';

/**
 * Styles
 */
import './editor.scss';

/**
 * Builds "Parent › Child" labels so terms with the same name can be told apart.
 *
 * @param {Array} terms Term records.
 * @return {Array<{value: number, label: string}>} Combobox options.
 */
function getTermOptions(terms) {
	const byId = new Map(terms.map((term) => [term.id, term]));
	const getLabel = (term, depth = 0) => {
		const parent = byId.get(term.parent);
		return parent && depth < 10
			? `${getLabel(parent, depth + 1)} › ${term.name}`
			: term.name;
	};
	return terms
		.map((term) => ({ value: term.id, label: getLabel(term) }))
		.sort((a, b) => a.label.localeCompare(b.label));
}

function TermSelect({ taxonomy, termId, onChange }) {
	const { taxonomyRecords, loading } = useTaxonomyRecords(taxonomy.slug, {
		per_page: -1,
		_fields: 'id,name,parent',
	});

	return (
		<ComboboxControl
			label={taxonomy.labels?.singular_name ?? taxonomy.name}
			help={
				loading
					? __('Loading…', 'jcore-dynamic-archive')
					: __(
							'Start typing to filter the list.',
							'jcore-dynamic-archive'
						)
			}
			value={termId || null}
			options={getTermOptions(taxonomyRecords ?? [])}
			onChange={(value) => onChange(value ? Number(value) : 0)}
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @hooked jcore.postPicker.taxonomies - Filters the taxonomies offered in term mode.
 * @hooked jcore.postPicker.maxItems - Filters the highest number of posts in term mode.
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const {
		mode,
		postIds,
		taxonomy,
		termId,
		postsPerPage,
		order,
		orderBy,
		columns,
		heading,
	} = attributes;

	const { taxonomies: allTaxonomies, loading: taxonomiesLoading } =
		useTaxonomies();

	const taxonomies = applyFilters(
		'jcore.postPicker.taxonomies',
		(allTaxonomies ?? []).filter(
			(item) =>
				item.visibility?.publicly_queryable &&
				item.types?.some((type) => type !== 'attachment')
		)
	);
	const selectedTaxonomy = taxonomies.find((item) => item.slug === taxonomy);

	const changeMode = (value) => {
		const next = { mode: value };
		if (value === 'term' && !selectedTaxonomy && taxonomies.length > 0) {
			next.taxonomy = (
				taxonomies.find((item) => item.slug === 'category') ??
				taxonomies[0]
			).slug;
			next.termId = 0;
		}
		setAttributes(next);
	};

	const isConfigured =
		mode === 'term' ? Boolean(selectedTaxonomy && termId) : postIds.length > 0;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={__('Content', 'jcore-dynamic-archive')}
					icon={taxonomiesLoading ? <Spinner size={5} /> : settings}
				>
					<VStack spacing={6}>
						<RadioControl
							label={__('What to show', 'jcore-dynamic-archive')}
							selected={mode}
							options={[
								{
									label: __(
										'Posts I pick',
										'jcore-dynamic-archive'
									),
									value: 'posts',
								},
								{
									label: __(
										'Newest posts in a category',
										'jcore-dynamic-archive'
									),
									value: 'term',
								},
							]}
							onChange={changeMode}
						/>
						{mode === 'posts' && (
							<PostPicker
								value={postIds}
								onChange={(value) =>
									setAttributes({ postIds: value })
								}
							/>
						)}
						{mode === 'term' && (
							<>
								<SelectControl
									label={__(
										'Kind of category',
										'jcore-dynamic-archive'
									)}
									value={taxonomy}
									options={[
										...(selectedTaxonomy
											? []
											: [
													{
														label: __(
															'Choose…',
															'jcore-dynamic-archive'
														),
														value: '',
														disabled: true,
													},
												]),
										...taxonomies.map((item) => ({
											label: item.name,
											value: item.slug,
										})),
									]}
									onChange={(value) =>
										setAttributes({
											taxonomy: value,
											termId: 0,
										})
									}
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
								{selectedTaxonomy && (
									<TermSelect
										key={selectedTaxonomy.slug}
										taxonomy={selectedTaxonomy}
										termId={termId}
										onChange={(value) =>
											setAttributes({ termId: value })
										}
									/>
								)}
								<QueryControls
									numberOfItems={postsPerPage}
									onNumberOfItemsChange={(value) =>
										setAttributes({ postsPerPage: value })
									}
									minItems={1}
									maxItems={applyFilters(
										'jcore.postPicker.maxItems',
										25
									)}
									order={order}
									orderBy={orderBy}
									onOrderChange={(value) =>
										setAttributes({ order: value })
									}
									onOrderByChange={(value) =>
										setAttributes({ orderBy: value })
									}
								/>
							</>
						)}
					</VStack>
				</PanelBody>
				<PanelBody
					title={__('Layout', 'jcore-dynamic-archive')}
					icon={layout}
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
			</InspectorControls>
			<div {...useBlockProps()}>
				<RichText
					tagName="h2"
					className="wp-block-jcore-post-picker__heading"
					aria-label={__('Heading', 'jcore-dynamic-archive')}
					value={heading ?? ''}
					allowedFormats={[]}
					withoutInteractiveFormatting
					placeholder={__(
						'Add a heading (optional)',
						'jcore-dynamic-archive'
					)}
					onChange={(value) => setAttributes({ heading: value })}
				/>
				{isConfigured ? (
					<div className="jcore-post-picker__preview">
						<Disabled isDisabled={true}>
							<ServerSideRender
								block="jcore/post-picker"
								attributes={attributes}
								httpMethod={'POST'}
							/>
						</Disabled>
					</div>
				) : (
					// The text goes in children rather than `instructions`, which
					// Placeholder announces and would talk over the picker's own
					// messages, e.g. when the last post is removed.
					<Placeholder
						label={__('Post picker', 'jcore-dynamic-archive')}
					>
						<p className="components-placeholder__instructions">
							{mode === 'term'
								? __(
										'Choose a category in the block settings.',
										'jcore-dynamic-archive'
									)
								: __(
										'Pick the posts to show in the block settings.',
										'jcore-dynamic-archive'
									)}
						</p>
					</Placeholder>
				)}
			</div>
		</>
	);
}
