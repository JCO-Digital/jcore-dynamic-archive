import { useEntityRecord } from "@wordpress/core-data";

export default function useTaxonomy(taxonomyName) {
	const { record, isResolving } = useEntityRecord(
		"root",
		"taxonomy",
		taxonomyName,
	);
	return {
		taxonomy: record,
		loading: isResolving,
	};
}
