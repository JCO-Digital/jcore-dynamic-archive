import { useEffect, useState } from '@wordpress/element';
import useTaxonomyRecords from '../useTaxonomyRecords';
import {
	Flex,
	FlexBlock,
	FlexItem,
	FormTokenField,
	Spinner,
} from '@wordpress/components';
import { sprintf, _x } from '@wordpress/i18n';
import useTaxonomy from '../useTaxonomy';

/**
 * TaxonomyPicker
 * @param {string} taxonomySlug The slug of the taxonomy to use.
 * @param {(value: string[]) => void} onChange The function to call when the value changes.
 * @param {string[]} value The current value of the taxonomy.
 * @returns {ReactElement}
 * @constructor
 */
export default function TaxonomyPicker({ taxonomySlug, onChange, value }) {
	const [taxonomyList, setTaxonomyList] = useState([]);
	const [visibleTaxonomyList, setVisibleTaxonomyList] = useState({});
	const [internalValue, setInternalValue] = useState([]);

	useEffect(() => {
		// Here we go through the currently selected IDs and map them to the "string" value id###name
		if (value && Object.keys(visibleTaxonomyList).length > 0) {
			const mappedValues = value
				.map((v) => {
					const found = visibleTaxonomyList[v];
					return found ? `${v}###${found.name}` : undefined;
				})
				.filter((v) => v !== undefined);
			setInternalValue(mappedValues);
		}
	}, [value, visibleTaxonomyList]);

	const { taxonomy, loading: loadingTaxonomy } = useTaxonomy(taxonomySlug);
	const { taxonomyRecords, loading: loadingRecords } = useTaxonomyRecords(
		taxonomySlug,
		{
			per_page: -1,
		}
	);

	useEffect(() => {
		if (taxonomyRecords) {
			const mappedRecords = taxonomyRecords.map(
				(record) => `${record.id}###${record.name}`
			);
			const mappedVisibleRecord = {};
			taxonomyRecords.forEach((term) => {
				mappedVisibleRecord[term.id.toString()] = {
					slug: term.slug,
					name: term.name,
				};
			});
			// Used for the suggestions
			setTaxonomyList(mappedRecords);
			// This is used to map the currently selected values
			// (ids) -> to the correct internal values (id###name)
			setVisibleTaxonomyList(mappedVisibleRecord);
		}
	}, [taxonomyRecords]);

	// Converts the selected values (id###name) to just the id. (filters out "empty" values)
	const handleChange = (values) => {
		const mappedValues = values
			.map((value) => value.split('###')[0])
			.filter(
				(value) =>
					value !== '' &&
					value !== undefined &&
					value !== null &&
					value !== false &&
					value !== 'undefined'
			);
		const mappedRecords = mappedValues
			.map((value) => value ?? undefined)
			.filter((value) => value !== undefined);
		onChange(mappedRecords);
	};

	return (
		<Flex>
			<FlexBlock>
				<FormTokenField
					__experimentalExpandOnFocus
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					displayTransform={(token) => {
						const [_id, name] = token.split('###');
						return name ?? '';
					}}
					suggestions={taxonomyList}
					value={internalValue}
					onChange={handleChange}
					label={`${sprintf(
						// translators: The label of the taxonomy.
						_x(
							'Show only items with selected: %s',
							'Taxonomy',
							'jcore-dynamic-archive'
						),
						taxonomy.name
					)}`}
				/>
			</FlexBlock>
			{(loadingTaxonomy || loadingRecords) && (
				<FlexItem>
					<Spinner />
				</FlexItem>
			)}
		</Flex>
	);
}
