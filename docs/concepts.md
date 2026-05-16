# Core Concepts

## Image Marker Fields

An image marker field stores the editing data for one mapped image.

Main values:

- `imageId` - the selected Craft asset ID.
- `markers` - an ordered list of marker rows.

Add the field anywhere Craft custom fields are supported, such as entry types, categories, or other element field layouts.

## Images

The field accepts a single Craft asset image. The Control Panel selector is restricted to image assets, and field validation checks that the saved asset is still a valid image.

In Twig, access the image as an asset query:

```twig
{% set image = entry.entryMapperField.image.one() %}
```

This keeps the API familiar for Craft users who already work with native Assets fields.

## Markers

A marker is a point on the selected image. It is displayed as a round draggable marker in the Control Panel.

Each marker stores:

- `uid` - stable marker identifier used by the Control Panel UI.
- `x` - horizontal position as a percentage from the left edge.
- `y` - vertical position as a percentage from the top edge.
- `entryId` - optional related entry ID.

Coordinates are saved with two decimal places and clamped between `0` and `100`.

## Percentage Coordinates

Markers are stored as percentages instead of pixels.

For example:

```json
{ "x": 25, "y": 40 }
```

This means:

- `x: 25` places the marker one quarter of the way from the left edge.
- `y: 40` places the marker 40 percent down from the top edge.

This is important because frontend images are usually responsive. The same marker position works whether the image is 320px wide or 1600px wide.

## Related Entries

Each marker can link to one Craft entry. Editors assign the entry by double-clicking a marker or using the marker table row.

In Twig, access the related entry as an entry query:

```twig
{% set relatedEntry = item.marker.entry.one() %}
```

The marker also exposes the raw ID:

```twig
{{ item.marker.entryId }}
```

## Stored Data

The field stores one JSON payload in Craft's content storage:

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

No custom database tables are required.
