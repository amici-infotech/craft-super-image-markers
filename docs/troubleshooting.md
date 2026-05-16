# Troubleshooting

## The Field Type Is Not Listed

Confirm the plugin is installed and enabled:

```bash
php craft plugin/list
php craft plugin/install super-image-markers
```

Also confirm Composer has loaded the package:

```bash
composer dump-autoload
```

## I Cannot Select an Image

Check these items:

- The field's **Allowed asset sources** setting is not too restrictive.
- The current user can view the asset volume.
- The asset is an image file.
- The asset has not been deleted or moved to an unavailable volume.

The image selector is restricted to image assets by design.

## I Cannot Upload an Image

Check these items:

- **Allow uploads** is enabled in the field settings.
- **Default upload location source** points to a valid asset volume.
- The current user has permission to save assets in that volume.
- The selected volume has a working filesystem.

## I Cannot Select the Entry I Need

Check these items:

- The field's **Allowed entry sources** setting includes the correct section.
- The current user can view the entry.
- The entry has not been deleted.
- The entry exists in a site that Craft can query from the selector modal.

## Markers Are Not Saving

Make sure an image is selected before saving markers. The field will reject marker rows without an image because coordinates only make sense relative to a selected image.

If marker entries were deleted, reselect entries for those markers or remove the stale marker rows.

## Markers Appear in the Wrong Place on the Frontend

Use percentage positioning and center the marker on its coordinate:

```css
.image-map {
  position: relative;
}

.image-map__marker {
  position: absolute;
  transform: translate(-50%, -50%);
}
```

Do not convert marker values to pixels unless you also account for the rendered image size.

## The Image Renders but Markers Do Not

Check that your template loops over `.markers.all()`:

```twig
{% set mapper = entry.entryMapperField.process() %}

{% for item in mapper.markers.all() %}
    {{ item.marker.x }}
    {{ item.marker.y }}
{% endfor %}
```

Also confirm markers have been added in the Control Panel and the element has been saved.

## A Marker Has No Related Entry

Markers can exist without a selected entry while editing. In frontend templates, guard against empty related entries:

```twig
{% set mapper = entry.entryMapperField.process() %}

{% for item in mapper.markers.all() %}
{% set relatedEntry = item.marker.entry.one() %}

{% if relatedEntry %}
    <a href="{{ relatedEntry.url }}">{{ relatedEntry.title }}</a>
{% endif %}
{% endfor %}
```

If `process()` is used, disabled or unavailable entries resolve to `null` before rendering.

## Templates Are Running Too Many Queries

Call `process()` once before rendering the image map:

```twig
{% set mapper = entry.entryMapperField.process() %}
```

After processing, `mapper.image.one()` and `item.marker.entry.one()` continue to work, but the image and marker entries have already been prepared.

## Empty Checks Are Verbose

Use the query-like `isEmpty()` helpers:

```twig
{% set mapper = entry.entryMapperField.process() %}

{% if not mapper.image.isEmpty() and not mapper.markers.isEmpty() %}
    {# Render map #}
{% endif %}
```

## Composer Warns About the Version Field

Composer may warn that package versions are usually inferred from tags. This plugin currently keeps `"version": "5.0.0"` in `composer.json` to match the local plugin release convention requested for this project.
