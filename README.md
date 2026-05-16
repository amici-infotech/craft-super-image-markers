# Super Image Markers for Craft CMS 5

Super Image Markers adds a custom Craft CMS field for selecting one image asset, placing draggable markers on top of it, and linking each marker to an entry.

Use it for interactive maps, floor plans, product lookbooks, team photos, diagrams, venue layouts, or any image where editors need to point to related content.

## Features

- Select a single Craft asset image from the field input.
- Preview the selected image directly in the Control Panel.
- Add round markers and drag them anywhere on the image.
- Store marker positions as X/Y percentages so they scale with responsive images.
- Double-click a marker to select a related Craft entry.
- Review selected entries and coordinates in a Craft-style table below the image.
- Limit selectable asset sources and entry sources from the field settings.
- Use simple Twig accessors for the selected image, marker rows, coordinates, and related entries.

## Requirements

- Craft CMS 5
- PHP 8.2 or newer

## Installation

For local development, add the plugin to your Craft project:

```bash
composer require amici/craft-super-image-markers
php craft plugin/install super-image-markers
```

You can also install it from **Settings -> Plugins** in the Craft Control Panel.

## Quick Setup

1. Go to **Settings -> Fields**.
2. Create a new field.
3. Choose **Image Markers [Super Image Markers]** as the field type.
4. Optionally limit the allowed asset sources and entry sources.
5. Add the field to an entry type, category group, or any element layout that supports custom fields.
6. Edit an element, select an image, add markers, and assign entries.

## Twig Example

```twig
{% set mapper = entry.entryMapperField %}
{% set image = mapper.image.one() %}

{% if image %}
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
- `entry.entryMapperField.markers.all()` returns all marker items in saved order.
- `item.marker.x` returns the marker's horizontal percentage.
- `item.marker.y` returns the marker's vertical percentage.
- `item.marker.entryId` returns the related entry ID.
- `item.marker.entry.one()` returns the related entry.

## License

Proprietary - Copyright (c) 2026 Amici Infotech
