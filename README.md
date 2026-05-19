# Super Image Markers for Craft CMS 5

Super Image Markers adds a custom Craft CMS field for selecting one image asset, placing draggable markers on top of it, and linking each marker to an entry.

Use it for interactive maps, floor plans, product lookbooks, team photos, diagrams, venue layouts, or any image where editors need to point to related content.

## Features

- Select a single Craft asset image from the field input.
- Use Craft's native asset selector UI, including uploads when enabled.
- Preview the selected image directly in the Control Panel.
- Add round markers and drag them anywhere on the image.
- Store marker positions as X/Y percentages so they scale with responsive images.
- Double-click a marker to select a related Craft entry.
- Review selected entries, colors, coordinates, and ordering in a Craft-style table below the image.
- Choose marker colors and reorder markers with drag handles.
- Limit selectable asset sources, upload location, upload behavior, and entry sources from the field settings.
- Use simple Twig accessors for the selected image, marker rows, coordinates, colors, and related entries.
- Call `process()` to preload the image and marker entries before rendering.

## Requirements

- Craft CMS 5
- PHP 8.2 or newer

## Installation

Run this command to your Craft project:

```bash
composer require amici/craft-super-image-markers
php craft plugin/install super-image-markers
```

You can also install it from **Settings -> Plugins** in the Craft Control Panel.

For the full setup flow, see the [Super Image Markers documentation](https://docs.amiciinfotech.com/craft-cms/super-image-markers).

## Quick Setup

1. Go to **Settings -> Fields**.
2. Create a new field.
3. Choose **Image Markers [Super Image Markers]** as the field type.
4. Optionally limit the allowed asset sources and entry sources.
5. Optionally choose the default upload location and whether uploads are allowed.
6. Add the field to an entry type, category group, global set, or any element layout that supports custom fields.
7. Edit an element, select or upload an image, add markers, choose colors, sort rows, and assign entries.

## Twig Example

```twig
{% set mapper = entry.entryMapperField.process() %}

{% if not mapper.image.isEmpty() and not mapper.markers.isEmpty() %}
    {% set image = mapper.image.one() %}

    <div class="image-map">
        <img src="{{ image.url }}" alt="{{ image.title }}">

        {% for item in mapper.markers.all() %}
            {% set relatedEntry = item.marker.entry.one() %}

            <a
                class="image-map__marker"
                href="{{ relatedEntry ? relatedEntry.url : '#' }}"
                style="left: {{ item.marker.x }}%; top: {{ item.marker.y }}%;"
            >
                {{ relatedEntry ? relatedEntry.title : 'Marker' }}
            </a>
        {% endfor %}
    </div>
{% endif %}
```

## Documentation

Read the full documentation at [docs.amiciinfotech.com/craft-cms/super-image-markers](https://docs.amiciinfotech.com/craft-cms/super-image-markers).

Local docs are split by task:

- [Documentation Home](docs/README.md)
- [Installation and Setup](docs/installation.md)
- [Core Concepts](docs/concepts.md)
- [Field Setup](docs/field-setup.md)
- [Editor Guide](docs/editor-guide.md)
- [Twig Usage](docs/twig-usage.md)
- [Data Model](docs/data-model.md)
- [Troubleshooting](docs/troubleshooting.md)

## Frontend API

Given a field handle such as `entryMapperField`:

- `entry.entryMapperField.image.one()` returns the selected image asset.
- `entry.entryMapperField.image.isEmpty()` checks whether there is a valid selected image.
- `entry.entryMapperField.markers.all()` returns all marker items in saved order.
- `entry.entryMapperField.markers.isEmpty()` checks whether any markers have been saved.
- `item.marker.x` returns the marker's horizontal percentage.
- `item.marker.y` returns the marker's vertical percentage.
- `item.marker.color` returns the marker's hex color.
- `item.marker.entryId` returns the related entry ID.
- `item.marker.entry.one()` returns the related entry.
- `entry.entryMapperField.process()` preloads the image and marker entries while keeping the same API.

## Example Templates

This plugin includes copy-ready frontend examples under `example-templates/`:

- `tooltip.twig` renders hover/focus tooltips.
- `modal.twig` renders click-to-open modals.
- `info-window.twig` renders map-style info windows with a pointer tail.

Copy them into your Craft project's `templates/` folder, adjust the field handle/global source, and route them however your project needs. The examples use Tailwind CSS from the CDN for demonstration only.

## License

Proprietary - Copyright (c) 2026 Amici Infotech
