import { ToggleControl } from '@wordpress/components';

export default function ToggleWrapper({
	attributeName,
	checked,
	setAttributes,
	children,
	label,
}) {
	return (
		<div className={'jcore-toggle-component'}>
			<ToggleControl
				checked={checked}
				onChange={(checked) =>
					setAttributes({ [attributeName]: checked })
				}
				label={label}
				__nextHasNoMarginBottom
			/>
			{checked && children}
		</div>
	);
}
