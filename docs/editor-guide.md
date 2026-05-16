# Editor Guide

## Select an Image

1. Edit an element that has a Super Image Markers field.
2. Click **Select image**.
3. Choose an image asset from the modal.
4. The image will appear in the field preview.

Only image assets should be selectable.

## Add a Marker

1. Select an image first.
2. Click **Add marker**.
3. A round marker appears in the center of the image.
4. Drag the marker to the correct position.

The marker coordinates update automatically as percentages.

## Move a Marker

Drag a marker to a new position on the image.

The marker cannot be moved outside the image bounds. Its X/Y values are clamped between `0` and `100`.

## Select an Entry for a Marker

There are two ways to assign an entry:

- Double-click the marker on the image.
- Click the marker's entry button in the table below the image.

After selecting an entry, the table row shows the entry title and stores the entry ID.

## Review the Marker Table

The table below the image shows:

- Selected entry title or a **Select entry** button.
- X percentage.
- Y percentage.
- Remove control.

Use this table for a quick audit before saving the element.

## Remove a Marker

Click the remove icon in the marker table row.

Removing a marker deletes that marker's coordinates and entry relation from the field value. It does not delete the related entry.

## Save the Element

Save the element as usual. The field stores:

- The selected image ID.
- All markers in their current order.
- Each marker's coordinates.
- Each marker's selected entry ID.

## Editing Tips

- Add the image first, then markers.
- Place markers near the visual center of the thing they describe.
- Use the table to confirm every marker has the expected entry.
- If the image changes substantially, review all marker positions before saving.
