import { useEffect, useState } from "@wordpress/element";
import useTaxonomyRecords from "../useTaxonomyRecords";
import {
	Flex,
	FlexBlock,
	FlexItem,
	FormTokenField,
	Spinner,
} from "@wordpress/components";
import { sprintf, _x } from "@wordpress/i18n";
import useTaxonomy from "../useTaxonomy";

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

	const { taxonomy, loading: loadingTaxonomy } = useTaxonomy(taxonomySlug);
	const { taxonomyRecords, loading: loadingRecords } = useTaxonomyRecords(
		taxonomySlug,
		{
			per_page: -1,
		},
	);

	useEffect(() => {
		if (taxonomyRecords) {
			const mappedRecords = taxonomyRecords.map((record) => record.id);
			const mappedVisibleRecord = {};
			taxonomyRecords.forEach((term) => {
				mappedVisibleRecord[term.id] = {
					slug: term.slug,
					name: term.name,
				};
			});
			setTaxonomyList(mappedRecords);
			setVisibleTaxonomyList(mappedVisibleRecord);
		}
	}, [taxonomyRecords]);

	return (
		<Flex>
			<FlexBlock>
				<FormTokenField
					__experimentalExpandOnFocus
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					displayTransform={(token) => {
						if (visibleTaxonomyList[token]) {
							return visibleTaxonomyList[token].name;
						}
						return token;
					}}
					saveTransform={(token) => {
						return token + "";
					}}
					suggestions={taxonomyList}
					value={value}
					onChange={onChange}
					label={`${sprintf(
						// translators: The label of the taxonomy.
						_x(
							"Show only items with selected: %s",
							"Taxonomy",
							"jcore-dynamic-archive",
						),
						taxonomy.name,
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
