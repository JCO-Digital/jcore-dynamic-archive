<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

use function Jcore\Ydin\register_timber_location;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register the Timber templates directory.
register_timber_location( trailingslashit( __DIR__ ) . 'templates/', 1 );

/**
 * Loads block metadata from a block directory.
 *
 * @param string $block_path The path to the block directory.
 *
 * @return array<string,mixed>
 */
function get_dynamic_archive_block_metadata( string $block_path ): array {
	$metadata_path = trailingslashit( $block_path ) . 'block.json';
	$metadata      = wp_json_file_decode(
		$metadata_path,
		array(
			'associative' => true,
		)
	);

	if ( ! is_array( $metadata ) ) {
		return array();
	}

	return $metadata;
}

/**
 * Validates externally registered attributes for the dynamic archive block.
 *
 * Extension attributes must:
 * - be namespaced (`vendorName_featureName`),
 * - include both `type` and `default`,
 * - avoid mutating core attributes unless explicitly enabled.
 *
 * @param array<string,mixed> $attributes      Candidate attributes.
 * @param array<string,mixed> $core_attributes Core block attributes from block.json.
 *
 * @return array<string,mixed>
 */
function validate_dynamic_archive_extension_attributes( array $attributes, array $core_attributes ): array {
	$allow_attribute_overrides = (bool) apply_filters(
		'jcore_dynamic_archive_allow_attribute_overrides',
		false,
		$core_attributes,
		$attributes
	);

	$validated_attributes = array();

	foreach ( $attributes as $attribute_name => $definition ) {
		if ( ! is_string( $attribute_name ) || '' === $attribute_name ) {
			continue;
		}

		$is_core_attribute = array_key_exists( $attribute_name, $core_attributes );
		if ( $is_core_attribute ) {
			if ( ! $allow_attribute_overrides && $definition !== $core_attributes[ $attribute_name ] ) {
				_doing_it_wrong(
					__FUNCTION__,
					sprintf(
						/* translators: %s: Attribute name. */
						__( 'Dynamic archive extension attributes cannot override core attribute "%s".', 'jcore-dynamic-archive' ),
						$attribute_name
					),
					'0.20.0'
				);
				$validated_attributes[ $attribute_name ] = $core_attributes[ $attribute_name ];
				continue;
			}
			$validated_attributes[ $attribute_name ] = $definition;
			continue;
		}

		if ( 1 !== preg_match( '/^[a-z][a-zA-Z0-9]*_[a-zA-Z0-9_]+$/', $attribute_name ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: %s: Attribute name. */
					__( 'Dynamic archive extension attribute "%s" must be namespaced as vendorName_featureName.', 'jcore-dynamic-archive' ),
					$attribute_name
				),
				'0.20.0'
			);
			continue;
		}

		if ( ! is_array( $definition ) || ! array_key_exists( 'type', $definition ) || ! array_key_exists( 'default', $definition ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: %s: Attribute name. */
					__( 'Dynamic archive extension attribute "%s" must define both "type" and "default".', 'jcore-dynamic-archive' ),
					$attribute_name
				),
				'0.20.0'
			);
			continue;
		}

		$validated_attributes[ $attribute_name ] = $definition;
	}

	foreach ( $core_attributes as $attribute_name => $definition ) {
		if ( ! array_key_exists( $attribute_name, $validated_attributes ) ) {
			$validated_attributes[ $attribute_name ] = $definition;
		}
	}

	return $validated_attributes;
}

/**
 * Registers the dynamic archive block with an extension-aware attribute registry.
 *
 * @return WP_Block_Type|false
 */
function register_dynamic_archive_block_with_extensions() {
	$block_path      = __DIR__ . '/build/dynamic-archive';
	$metadata        = get_dynamic_archive_block_metadata( $block_path );
	$core_attributes = array();

	if ( isset( $metadata['attributes'] ) && is_array( $metadata['attributes'] ) ) {
		$core_attributes = $metadata['attributes'];
	}

	$filtered_metadata = apply_filters( 'jcore_dynamic_archive_block_metadata', $metadata, $block_path );
	if ( ! is_array( $filtered_metadata ) ) {
		$filtered_metadata = $metadata;
	}

	$candidate_attributes = $filtered_metadata['attributes'] ?? $core_attributes;
	if ( ! is_array( $candidate_attributes ) ) {
		$candidate_attributes = $core_attributes;
	}

	$filtered_attributes = apply_filters(
		'jcore_dynamic_archive_attributes',
		$candidate_attributes,
		$core_attributes,
		$filtered_metadata
	);
	if ( ! is_array( $filtered_attributes ) ) {
		$filtered_attributes = $core_attributes;
	}

	$filtered_metadata['attributes'] = validate_dynamic_archive_extension_attributes( $filtered_attributes, $core_attributes );

	$allowed_metadata_overrides = array(
		'title',
		'category',
		'icon',
		'description',
		'keywords',
		'supports',
		'usesContext',
		'providesContext',
		'attributes',
		'example',
		'styles',
		'variations',
		'allowedBlocks',
		'parent',
		'ancestor',
		'textdomain',
		'selectors',
		'apiVersion',
		'blockHooks',
	);

	$metadata_overrides = array();
	foreach ( $allowed_metadata_overrides as $metadata_key ) {
		if ( ! array_key_exists( $metadata_key, $filtered_metadata ) ) {
			continue;
		}

		$arg_key = match ( $metadata_key ) {
			'apiVersion' => 'api_version',
			'usesContext' => 'uses_context',
			'providesContext' => 'provides_context',
			'allowedBlocks' => 'allowed_blocks',
			'blockHooks' => 'block_hooks',
			default => $metadata_key,
		};

		$metadata_overrides[ $arg_key ] = $filtered_metadata[ $metadata_key ];
	}

	return register_block_type_from_metadata( $block_path, $metadata_overrides );
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init_dynamic_archive_block(): void {
	$success   = array();
	$success[] = register_dynamic_archive_block_with_extensions();
	$success[] = register_block_type( __DIR__ . '/build/latest-posts' );
	if ( in_array( false, $success, true ) && wp_get_environment_type() !== 'production' ) {
		wp_admin_notice( 'Dynamic Archive blocks could not be registered.' );
	}
	foreach ( $success as $block ) {
		if ( ! is_a( $block, WP_Block_Type::class, true ) ) {
			continue;
		}
		if ( is_array( $block->editor_script_handles ) ) {
			foreach ( $block->editor_script_handles as $handle ) {
				wp_set_script_translations( $handle, 'jcore-dynamic-archive', untrailingslashit( DYNAMIC_ARCHIVE_PATH ) . '/languages/' );
			}
		}
	}
}
add_action( 'init', 'init_dynamic_archive_block' );

add_filter(
	'timber/twig/functions',
	static function ( array $functions ): array {
		$functions['build_param_name'] = array(
			'callable' => '\Jcore\DynamicArchive\Helpers\build_param_name',
		);
		$functions['get_parameter']    = array(
			'callable' => '\Jcore\DynamicArchive\Helpers\get_parameter',
		);

		return $functions;
	}
);
