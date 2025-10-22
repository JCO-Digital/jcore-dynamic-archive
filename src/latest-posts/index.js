/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { createBlock, registerBlockType } from '@wordpress/blocks';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * All files containing `style` keyword are bundled together. The code used
 * gets applied both to the front of your site and to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.css';

/**
 * Internal dependencies
 */
import Edit from './edit';
import variations from './variations';
import metadata from './block.json';

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType(metadata.name, {
	/**
	 * @see ./edit.js
	 */
	edit: Edit,
	icon: {
		src: (
			<svg
				xmlns="http://www.w3.org/2000/svg"
				width="24"
				height="24"
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				stroke-width="1.5"
				stroke-linecap="round"
				stroke-linejoin="round"
				class="lucide lucide-layout-list-icon lucide-layout-list"
			>
				<rect width="7" height="7" x="3" y="3" rx="1" fill="none" />
				<rect width="7" height="7" x="3" y="14" rx="1" fill="none" />
				<path d="M14 4h7" fill="none" />
				<path d="M14 9h7" fill="none" />
				<path d="M14 15h7" fill="none" />
				<path d="M14 20h7" fill="none" />
			</svg>
		),
	},
	save: () => null,
	transforms: {
		from: [
			{
				type: 'block',
				blocks: ['jcore/latest-posts'],
				isMatch: (attributes) => attributes.related,
				transform: (attributes) =>
					createBlock('jcore/latest-posts', {
						related: false,
					}),
			},
		],
	},
	variations,
});
