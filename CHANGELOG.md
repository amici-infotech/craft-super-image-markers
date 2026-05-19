# Changelog

All notable changes to this project will be documented in this file.

## 5.0.1 - 2026-05-19

### Changed
- Changed marker table X/Y coordinate cells to editable percentage inputs so editors can fine-tune marker positions without dragging.

### Fixed
- Fixed direct image uploads from the field by targeting the configured upload folder instead of treating Super Image Markers as a native Assets field.
- Fixed uploaded asset chip rendering by including the active site ID in the native asset selector criteria.

## 5.0.0 - 2026-05-16

### Added
- Initial Craft CMS 5 release.
- Added the `Image Markers [Super Image Markers]` custom field type.
- Added single-image selection using Craft's native asset selector UI, restricted to image assets.
- Added field settings for allowed asset sources, default upload location source, upload subpath, upload enablement, and allowed entry sources.
- Added native image upload support when the field and user permissions allow it.
- Added a responsive Control Panel image preview that preserves the image aspect ratio without cropping.
- Added draggable round markers on top of the selected image.
- Added percentage-based marker coordinates so marker positions stay responsive on the frontend.
- Added double-click entry selection for each marker using Craft's element selector modal.
- Added a Craft-style marker table below the image preview for entry status/title, marker color, X/Y position, sorting, editing, and removal.
- Added marker numbering and drag-and-drop table sorting.
- Added per-marker color picker support with live marker and table swatch updates.
- Added JSON-backed field storage containing `imageId` and ordered marker data.
- Added Twig-facing value objects for `field.image.one()`, `field.image.isEmpty()`, `field.markers.all()`, `field.markers.isEmpty()`, and `marker.entry.one()`.
- Added `field.process()` for preloading the selected image and marker entries while preserving the existing `.one()`/`.all()` API.
- Added validation for selected image assets and marker entry references.
- Added checked-in Control Panel CSS and JavaScript assets without a build step.
- Added Tailwind CDN frontend examples for tooltip, modal, and map-style info-window marker displays.
- Added package icons, README, license, changelog, and structured documentation under `docs/`.

### Changed
- Switched the Control Panel image selector display to Craft's list view to save space.
- Refined marker table styling to better match Craft's Control Panel UI.
- Moved entry editing to an edit icon in the marker table.
- Improved marker removal icon alignment.
- Updated documentation to cover setup, editor workflow, frontend rendering, processing, empty checks, storage, and troubleshooting.
