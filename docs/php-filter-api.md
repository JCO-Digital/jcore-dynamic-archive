# PHP Filter API

This document lists extension filters for `jcore/dynamic-archive`.

## Block registration filters

### `jcore_dynamic_archive_block_metadata`

Modify block metadata before registration.

```php
apply_filters( 'jcore_dynamic_archive_block_metadata', array $metadata, string $block_path ): array;
```

### `jcore_dynamic_archive_attributes`

Add or modify dynamic archive block attributes.

```php
apply_filters(
	'jcore_dynamic_archive_attributes',
	array $attributes,
	array $core_attributes,
	array $metadata
): array;
```

### `jcore_dynamic_archive_allow_attribute_overrides`

Opt-in switch for overriding core attributes (default `false`).

```php
apply_filters(
	'jcore_dynamic_archive_allow_attribute_overrides',
	bool $allow,
	array $core_attributes,
	array $candidate_attributes
): bool;
```

## Query and request filters

### `jcore_dynamic_archive_args`

Mutate final `WP_Query` args.

```php
apply_filters( 'jcore_dynamic_archive_args', array $args, array $attributes ): array;
```

### `jcore_dynamic_archive_inherit_sticky`

Control sticky handling when `inherit` is enabled.

```php
apply_filters( 'jcore_dynamic_archive_inherit_sticky', bool $allow ): bool;
```

### `jcore_dynamic_archive_tax_query`

Mutate taxonomy query result.

```php
apply_filters(
	'jcore_dynamic_archive_tax_query',
	array $args,
	array $attributes,
	array $active_filters
): array;
```

### `jcore_dynamic_archive_tax_query_relation`

Set tax query relation for forced-term fallback.

```php
apply_filters(
	'jcore_dynamic_archive_tax_query_relation',
	string $relation,
	array $args,
	array $attributes,
	array $active_filters
): string;
```

### `jcore_dynamic_archive_param_name`

Override URL parameter naming strategy.

```php
apply_filters(
	'jcore_dynamic_archive_param_name',
	string $name,
	string $base_name,
	int|string $instance_id,
	array $attributes
): string;
```

### `jcore_dynamic_archive_taxonomy_param_field_type`

Switch taxonomy URL value type (`id` / `slug`).

```php
apply_filters(
	'jcore_dynamic_archive_taxonomy_param_field_type',
	string $field_type,
	array $attributes
): string;
```

## Interactivity and render filters

### `jcore_dynamic_archive_interactivity_context`

Mutate Interactivity API context.

```php
apply_filters(
	'jcore_dynamic_archive_interactivity_context',
	array $interactivity_context,
	array $attributes
): array;
```

### `jcore_dynamic_archive_interactivity_state`

Mutate global Interactivity API state (`wp_interactivity_state` payload).

```php
apply_filters( 'jcore_dynamic_archive_interactivity_state', array $state ): array;
```

### `jcore_dynamic_archive_render_context`

Mutate final Twig render context before template compilation.

```php
apply_filters(
	'jcore_dynamic_archive_render_context',
	array $context,
	array $attributes,
	array $args,
	\Timber\PostQuery $timber_posts
): array;
```

## Taxonomy filter data filters

### `jcore_dynamic_archive_taxonomies_inherit`

Provide taxonomy/filter UI overrides while `inherit` is enabled.

```php
apply_filters( 'jcore_dynamic_archive_taxonomies_inherit', array $overrides, array $attributes ): array;
```

### `jcore_dynamic_archive_taxonomies_filter`

Mutate taxonomy filter structures passed to Twig.

```php
apply_filters(
	'jcore_dynamic_archive_taxonomies_filter',
	array $taxonomies,
	array $attributes,
	array $active_filters
): array;
```
