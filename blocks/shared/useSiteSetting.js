import { useEffect, useState } from "@wordpress/element";
import { useEntityProp } from "@wordpress/core-data";

export default function useSiteSetting(settingName, initialValue) {
	const [setting, setSetting] = useState(initialValue);

	const [settingValue] = useEntityProp("root", "site", settingName);

	useEffect(() => {
		if (settingValue !== undefined) {
			setSetting(settingValue);
		}
	}, [settingValue]);

	return setting;
}
