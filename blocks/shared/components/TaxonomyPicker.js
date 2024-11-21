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

export default function TaxonomyPicker({ taxonomySlug, onChange, value }) {
	const [taxonomyList, setTaxonomyList] = useState([]);
	const [currentPage, setCurrentPage] = useState(1);

	const { taxonomy, loading: loadingTaxonomy } = useTaxonomy(taxonomySlug);
	const { taxonomyRecords, loading: loadingRecords } = useTaxonomyRecords(
		taxonomySlug,
		{
			per_page: 100,
			page: currentPage,
		},
	);

	useEffect(() => {
		if (taxonomyRecords) {
			const mappedRecords = taxonomyRecords.map((record) => record.slug);
			setTaxonomyList(mappedRecords);
		}
	}, [taxonomyRecords]);

	return (
		<Flex>
			<FlexBlock>
				<FormTokenField
					__experimentalExpandOnFocus
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					suggestions={taxonomyList}
					value={value}
					onChange={onChange}
					label={`${sprintf(
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
