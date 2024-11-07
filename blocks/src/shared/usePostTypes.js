import { applyFilters } from "@wordpress/hooks";
import { useSelect } from "@wordpress/data";
import { useEffect, useState } from "@wordpress/element";

/**
 * Returns a list of post-types that are viewable and not in the forbidden list.
 *
 * @hooked dynamicArchive.forbiddenPostTypes - Filters the list of forbidden post-types.
 * @returns {*[]}
 */
export default function usePostTypes() {
	const { _postTypes } = useSelect(
		(select) => ({ _postTypes: select("core").getPostTypes({ per_page: -1 }) }),
		[],
	);
	const [postTypes, setPostTypes] = useState([]);

	const forbiddenPostTypes = applyFilters("dynamicArchive.forbiddenPostTypes", [
		"attachment",
	]);

	useEffect(() => {
		if (_postTypes) {
			setPostTypes(
				_postTypes.filter(
					(postType) =>
						postType.viewable && !forbiddenPostTypes.includes(postType.slug),
				),
			);
		}
	}, [_postTypes]);

	const data = useEntityRecords("postType");

	return postTypes;
}
