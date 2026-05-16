# Core Concepts

## Image Marker Fields

An image marker field stores the editing data for one mapped image.

Main values:

- `imageId` - the selected Craft asset ID.
- `markers` - an ordered list of marker rows.

Add the field anywhere Craft custom fields are supported, such as entry types, categories, or other element field layouts.

## Images

The field accepts a single Craft asset image. The Control Panel uses Craft's native asset selector in list view, is restricted to image assets, and can optionally allow uploads to a configured asset volume.

In Twig, access the image as a query-like reference:

```twig
{% set image = entry.entryMapperField.image.one() %}
{% set hasImage = not entry.entryMapperField.image.isEmpty() %}
```

This keeps the API familiar for Craft users who already work with native Assets fields while adding `isEmpty()`.

## Markers

A marker is a point on the selected image. It is displayed as a round draggable marker in the Control Panel.

Each marker stores:

- `uid` - stable marker identifier used by the Control Panel UI.
- `x` - horizontal position as a percentage from the left edge.
- `y` - vertical position as a percentage from the top edge.
- `entryId` - optional related entry ID.
- `color` - marker color as a six-character hex value.

Coordinates are saved with two decimal places and clamped between `0` and `100`.

Markers can be reordered in the marker table. The frontend receives markers in the saved order.

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

In Twig, access the related entry as a query-like reference:

```twig
{% set relatedEntry = item.marker.entry.one() %}
{% set hasRelatedEntry = not item.marker.entry.isEmpty() %}
```

The marker also exposes the raw ID:

```twig
{{ item.marker.entryId }}
```

When rendering a page with multiple markers, call `process()` once:

```twig
{% set mapper = entry.entryMapperField.process() %}
```

`process()` prepares the image and marker entries before rendering, so `marker.entry.one()` keeps working without one query per marker.

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
      "entryId": 456,
      "color": "#d92828"
    }
  ]
}
```

No custom database tables are required.
