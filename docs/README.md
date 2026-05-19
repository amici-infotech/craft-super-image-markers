# Super Image Markers Documentation

Super Image Markers adds an image mapping field to Craft CMS. Editors select or upload one image, place percentage-based markers on it, color and sort those markers, and connect each marker to an entry.

This documentation is split by task so setup, editing, and frontend implementation stay easy to find.

Read the full documentation at [docs.amiciinfotech.com/craft-cms/super-image-markers](https://docs.amiciinfotech.com/craft-cms/super-image-markers).

## Start Here

- [Installation and Setup](installation.md) - install the plugin, enable it, and create the field.
- [Core Concepts](concepts.md) - understand images, markers, percentage coordinates, and related entries.
- [Field Setup](field-setup.md) - configure asset sources, upload location, upload permissions, and entry source restrictions.
- [Editor Guide](editor-guide.md) - select images, add markers, drag markers, choose colors, sort rows, and relate entries.

## Developer Reference

- [Twig Usage](twig-usage.md) - output the image and markers on the frontend.
- [Data Model](data-model.md) - JSON storage shape and PHP/Twig value objects.
- [Troubleshooting](troubleshooting.md) - common setup, editing, and rendering issues.

## Quick Mental Model

- The field stores one selected image asset ID.
- Each marker stores an `x` and `y` coordinate as a percentage of the image dimensions.
- Each marker stores a hex color and can be sorted in the marker table.
- Each marker can store one related entry ID.
- Frontend templates should call `process()` when rendering marker entries so image and entry references are preloaded.
- Frontend templates render the image normally and absolutely position marker links with CSS.
- Marker coordinates are percentages, not pixels, so they work with responsive images.

## Example Templates

The plugin includes three copy-ready Tailwind CDN examples in `example-templates/`:

- `tooltip.twig` - tooltip markers.
- `modal.twig` - marker modal cards.
- `info-window.twig` - map-style info windows.

Copy these files into your Craft project's `templates/` folder and adjust routes/field handles for your project.
