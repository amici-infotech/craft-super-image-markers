# Super Image Markers Documentation

Super Image Markers adds an image mapping field to Craft CMS. Editors select one image, place percentage-based markers on it, and connect each marker to an entry.

This documentation is split by task so setup, editing, and frontend implementation stay easy to find.

## Start Here

- [Installation and Setup](installation.md) - install the plugin, enable it, and create the field.
- [Core Concepts](concepts.md) - understand images, markers, percentage coordinates, and related entries.
- [Field Setup](field-setup.md) - configure asset and entry source restrictions.
- [Editor Guide](editor-guide.md) - select images, add markers, drag markers, and relate entries.

## Developer Reference

- [Twig Usage](twig-usage.md) - output the image and markers on the frontend.
- [Data Model](data-model.md) - JSON storage shape and PHP/Twig value objects.
- [Troubleshooting](troubleshooting.md) - common setup, editing, and rendering issues.

## Quick Mental Model

- The field stores one selected image asset ID.
- Each marker stores an `x` and `y` coordinate as a percentage of the image dimensions.
- Each marker can store one related entry ID.
- Frontend templates render the image normally and absolutely position marker links with CSS.
- Marker coordinates are percentages, not pixels, so they work with responsive images.
