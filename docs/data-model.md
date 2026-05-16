# Data Model

## Stored JSON

Super Image Markers stores one JSON value in Craft's element content storage.

Example:

```json
{
  "imageId": 123,
  "markers": [
    {
      "uid": "marker-abc",
      "x": 42.25,
      "y": 61.5,
      "entryId": 456,
      "color": "#d92828"
    }
  ]
}
```

## Image ID

`imageId` is the selected Craft asset ID.

The normalized field value exposes the selected image as a query-like reference:

```twig
{% set image = entry.entryMapperField.image.one() %}
{% set hasImage = not entry.entryMapperField.image.isEmpty() %}
```

The reference is restricted to image assets and supports `one()`, `all()`, and `isEmpty()`.

## Marker Rows

`markers` is an ordered array.

Each marker includes:

- `uid` - stable unique ID for Control Panel editing.
- `x` - horizontal percentage.
- `y` - vertical percentage.
- `entryId` - related Craft entry ID, or `null`.
- `color` - marker color as a normalized hex string.

The normalized field value exposes markers through a collection:

```twig
{% set markers = entry.entryMapperField.markers.all() %}
{% set hasMarkers = not entry.entryMapperField.markers.isEmpty() %}
```

Each item in the collection is a marker data object. It also has a `marker` property that returns itself, so this style works:

```twig
{{ item.marker.x }}
{{ item.marker.y }}
{{ item.marker.color }}
{{ item.marker.entryId }}
{{ item.marker.entry.one().title }}
```

## Processing

By default, `image.one()` and each `marker.entry.one()` resolve references on demand. For frontend templates with multiple markers, call `process()` once before rendering:

```twig
{% set mapper = entry.entryMapperField.process() %}

{% if not mapper.image.isEmpty() and not mapper.markers.isEmpty() %}
    {% set image = mapper.image.one() %}

    {% for item in mapper.markers.all() %}
        {% set relatedEntry = item.marker.entry.one() %}
    {% endfor %}
{% endif %}
```

`process()` returns the same value-object API, but preloads the image and all marker entries in batches. Existing code such as `mapper.image.one()`, `mapper.markers.all()`, and `item.marker.entry.one()` continues to work.

If an entry is disabled, deleted, or otherwise not returned by the frontend element query, `item.marker.entry.one()` returns `null` after processing.

## Why JSON Instead of Custom Tables

The field data belongs to the element that owns the field, so JSON content storage keeps the plugin simple:

- No install migration is needed.
- Field data follows Craft's normal element content behavior.
- Project config stores the field definition.
- The selected IDs remain easy to inspect and export.
- The value object can provide a friendlier Twig API.

## Normalization

The field accepts:

- Existing `ImageMarkersData` objects.
- JSON strings.
- PHP arrays.
- Empty values.

It normalizes all valid input into an `ImageMarkersData` object.

## Serialization

When Craft saves the element, the value serializes back to:

```php
[
    'imageId' => 123,
    'markers' => [
        [
            'uid' => 'marker-abc',
            'x' => 42.25,
            'y' => 61.5,
            'entryId' => 456,
            'color' => '#d92828',
        ],
    ],
]
```

Empty values serialize to `null`.

## Validation

The field validates references before saving:

- `imageId` must be a valid image asset.
- `entryId` values must be valid entries.
- Markers cannot be saved without an image.

Coordinates are normalized to two decimal places and clamped to the `0` to `100` range.
Colors are normalized to valid `#rrggbb` strings.
