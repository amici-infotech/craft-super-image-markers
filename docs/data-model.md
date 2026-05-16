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
      "entryId": 456
    }
  ]
}
```

## Image ID

`imageId` is the selected Craft asset ID.

The normalized field value exposes the selected image as an asset query:

```twig
{% set image = entry.entryMapperField.image.one() %}
```

The query is restricted to image assets.

## Marker Rows

`markers` is an ordered array.

Each marker includes:

- `uid` - stable unique ID for Control Panel editing.
- `x` - horizontal percentage.
- `y` - vertical percentage.
- `entryId` - related Craft entry ID, or `null`.

The normalized field value exposes markers through a collection:

```twig
{% set markers = entry.entryMapperField.markers.all() %}
```

Each item in the collection is a marker data object. It also has a `marker` property that returns itself, so this style works:

```twig
{{ item.marker.x }}
{{ item.marker.y }}
{{ item.marker.entryId }}
{{ item.marker.entry.one().title }}
```

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
