import { useEntityRecords } from '@wordpress/core-data';

export default function useTaxonomies(postType) {
	const queryArgs = {
		per_page: -1,
	};

	if (postType) {
		queryArgs.type = postType;
	}
	const { records: taxonomies, isResolving } = useEntityRecords(
		'root',
		'taxonomy',
		queryArgs
	);

	return {
		taxonomies,
		loading: isResolving,
	};
}
