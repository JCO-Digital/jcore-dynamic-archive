/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from "@wordpress/i18n";

import { useSelect } from "@wordpress/data";
import { useEffect, useState } from "@wordpress/element";
import { useInstanceId } from "@wordpress/compose";

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from "@wordpress/block-editor";

import ServerSideRender from "@wordpress/server-side-render";
import { InspectorControls } from "@wordpress/block-editor";
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	TextControl,
} from "@wordpress/components";

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import "./editor.scss";

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const { postType, perPage, columns, masonryGrid } = attributes;
	const instanceId = useInstanceId(Edit);
	setAttributes({ instanceId: instanceId.toString() });

	// BEGIN: Post Types
	const { _postTypes } = useSelect(
		(select) => ({ _postTypes: select("core").getPostTypes() }),
		[],
	);
	const [postTypes, setPostTypes] = useState([]);

	const forbiddenPostTypes = ["attachment"];

	useEffect(() => {
		if (_postTypes) {
			setPostTypes(
				_postTypes
					.filter(
						(postType) =>
							postType.viewable && !forbiddenPostTypes.includes(postType.slug),
					)
					.map((postType) => ({
						label: postType.name,
						value: postType.slug,
					})),
			);
		}
	}, [_postTypes]);
	// END: Post Types

	// BEGIN: Per Page
	const { _site } = useSelect(
		(select) => ({ _site: select("core").getSite() }),
		[],
	);
	const [_perPage, setPerPage] = useState(5);

	useEffect(() => {
		if (attributes.perPage) {
			return;
		}
		if (_site?.posts_per_page) {
			console.log(_site);
			setPerPage(_site.posts_per_page);
		}
	}, [_site]);
	// END: Per Page

	return (
		<>
			<InspectorControls>
				<PanelBody title={__("Settings", "block-development-examples")}>
					<SelectControl
						label={__("Post Type", "block-development-examples")}
						value={postType}
						options={postTypes}
						onChange={(value) => setAttributes({ postType: value })}
					/>
				</PanelBody>
				<PanelBody title={__("Layout", "block-development-examples")}>
					<ToggleControl
						label={__("Masonry Grid", "block-development-examples")}
						checked={masonryGrid}
						onChange={(checked) => setAttributes({ masonryGrid: checked })}
					/>
					<TextControl
						label={__("Columns", "block-development-examples")}
						value={columns || 3}
						onChange={(value) => setAttributes({ columns: parseInt(value) })}
						type="number"
						min={1}
						max={10}
					/>
					<TextControl
						label={__("Posts per Page", "block-development-examples")}
						value={perPage || _perPage}
						onChange={(value) => setAttributes({ perPage: parseInt(value) })}
						type="number"
						min={1}
						max={100}
					/>
				</PanelBody>
			</InspectorControls>
			<div {...useBlockProps()}>
				<ServerSideRender
					block="jcore/dynamic-archive"
					attributes={attributes}
				/>
			</div>
		</>
	);
}
