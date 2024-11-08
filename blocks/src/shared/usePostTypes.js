import { applyFilters } from "@wordpress/hooks";
import { useEffect, useState } from "@wordpress/element";
import { useEntityRecords } from "@wordpress/core-data";

/**
 * Returns a list of post-types that are viewable and not in the forbidden list.
 *
 * @hooked dynamicArchive.forbiddenPostTypes - Filters the list of forbidden post-types.
 */
export default function usePostTypes(
	queryArgs = {
		per_page: 100,
	},
) {
	const { isResolving, records } = useEntityRecords(
		"root",
		"postType",
		queryArgs,
	);
	const [postTypes, setPostTypes] = useState([]);

	const forbiddenPostTypes = applyFilters("dynamicArchive.forbiddenPostTypes", [
		"attachment",
	]);

	useEffect(() => {
		if (records) {
			setPostTypes(
				records.filter(
					(postType) =>
						postType.viewable && !forbiddenPostTypes.includes(postType.slug),
				),
			);
		}
	}, [records]);

	return {
		postTypes,
		loading: isResolving,
	};
}
