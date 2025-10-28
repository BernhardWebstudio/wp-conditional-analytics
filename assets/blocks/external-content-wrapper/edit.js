/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from "@wordpress/i18n";

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
} from "@wordpress/block-editor";

/**
 * WordPress components for the sidebar controls
 */
import { PanelBody, TextControl, SelectControl } from "@wordpress/components";

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
 * @param {Object}   props               Properties passed to the function.
 * @param {Object}   props.attributes    Available block attributes.
 * @param {Function} props.setAttributes Function that updates individual attributes.
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const { blockingMessage, buttonText, contentIdentifier } = attributes;

	// Predefined content types that users can choose from
	const contentTypes = [
		{ label: __("General", "external-content-wrapper"), value: "general" },
		{ label: __("Analytics", "external-content-wrapper"), value: "analytics" },
		{ label: __("Marketing", "external-content-wrapper"), value: "marketing" },
		{
			label: __("Social Media", "external-content-wrapper"),
			value: "social-media",
		},
		{ label: __("Video", "external-content-wrapper"), value: "video" },
		{ label: __("Maps", "external-content-wrapper"), value: "maps" },
		{ label: __("Custom", "external-content-wrapper"), value: "custom" },
	];

	const blockProps = useBlockProps({
		className: "wp-block-conditional-analytics-external-content-wrapper",
	});

	return (
		<>
			<InspectorControls>
				<PanelBody title={__("Content Settings", "external-content-wrapper")}>
					<SelectControl
						label={__("Content Type", "external-content-wrapper")}
						value={contentIdentifier || "general"}
						options={contentTypes}
						onChange={(value) => setAttributes({ contentIdentifier: value })}
						help={__(
							"Select the type of external content. This allows users to selectively accept specific content types.",
							"external-content-wrapper",
						)}
					/>

					{contentIdentifier === "custom" && (
						<TextControl
							label={__("Custom Identifier", "external-content-wrapper")}
							value={attributes.customIdentifier || ""}
							onChange={(value) => setAttributes({ customIdentifier: value })}
							help={__(
								"Enter a unique identifier for this content type.",
								"external-content-wrapper",
							)}
						/>
					)}

					<TextControl
						label={__("Blocking Message", "external-content-wrapper")}
						value={
							blockingMessage ||
							__(
								"This content is currently blocked to protect your privacy.",
								"external-content-wrapper",
							)
						}
						onChange={(value) => setAttributes({ blockingMessage: value })}
					/>

					<TextControl
						label={__("Button Text", "external-content-wrapper")}
						value={
							buttonText ||
							__("Accept cookies and show content", "external-content-wrapper")
						}
						onChange={(value) => setAttributes({ buttonText: value })}
					/>
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>
				<div className="editor-info-banner">
					{contentIdentifier === "custom" && attributes.customIdentifier
						? // eslint-disable-next-line @wordpress/i18n-no-variables
						  __(
								`External Content (${attributes.customIdentifier}): Content will only be shown when cookies for this type are accepted`,
								"external-content-wrapper",
						  )
						: // eslint-disable-next-line @wordpress/i18n-no-variables
						  __(
								`External Content (${
									contentIdentifier || "general"
								}): Content will only be shown when cookies for this type are accepted`,
								"external-content-wrapper",
						  )}
				</div>
				<div className="editor-content">
					<InnerBlocks />
				</div>
			</div>
		</>
	);
}
