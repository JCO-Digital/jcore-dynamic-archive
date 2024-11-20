import { useEntityRecords } from "@wordpress/core-data";
import { parseArgs } from "./utils";

export default function useTaxonomyRecords(taxonomyName, queryArgs = {}) {
	const defaultQueryArgs = {
		per_page: 100,
	};

	const parsed = parseArgs(defaultQueryArgs, queryArgs);

	const { records, isResolving } = useEntityRecords(
		"taxonomy",
		taxonomyName,
		parsed,
	);

	return {
		taxonomyRecords: records,
		loading: isResolving,
	};
}
