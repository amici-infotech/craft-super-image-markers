# Field Setup

## Create the Field

1. Go to **Settings -> Fields**.
2. Click **New field**.
3. Enter a name such as `Entry Mapper`.
4. Use a handle such as `entryMapperField`.
5. Choose **Image Markers [Super Image Markers]** as the field type.
6. Configure source restrictions if needed.
7. Save the field.

## Add the Field to a Layout

After creating the field, add it to the field layout for the element type where editors need it.

Common examples:

- Entry type for an interactive floor plan.
- Entry type for a product lookbook.
- Entry type for a venue map.
- Entry type for a staff or team photo.
- Category group for location diagrams.

## Asset Source Restrictions

Use **Allowed asset sources** to limit which asset volumes editors can choose images from.

Leave the setting blank to allow images from all asset sources.

Use source restrictions when:

- Only one image volume should be used for mapped images.
- Editors should not pick from private or system asset volumes.
- The project has separate volumes for documents, downloads, and images.

The selector still restricts choices to image assets.

## Entry Source Restrictions

Use **Allowed entry sources** to limit which entries markers can link to.

Leave the setting blank to allow entries from all entry sources.

Use source restrictions when:

- Markers should link only to products.
- Markers should link only to people or team member entries.
- Markers should link only to locations.
- Editors should not accidentally choose entries from unrelated sections.

## Recommended Field Handles

Choose a handle that describes what the mapped image represents:

- `entryMapperField`
- `imageMap`
- `floorPlan`
- `productMarkers`
- `locationMap`
- `lookbookMarkers`

The frontend API uses the field handle directly:

```twig
{% set image = entry.imageMap.image.one() %}
{% set markers = entry.imageMap.markers.all() %}
```

## Validation Behavior

The field validates that:

- The selected image ID resolves to a Craft asset.
- The selected asset is an image.
- Marker entry IDs resolve to valid entries.
- Markers are not saved without an image.

Coordinate values are normalized before saving, so invalid values are clamped into the `0` to `100` range.
