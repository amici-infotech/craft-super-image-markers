# Changelog

All notable changes to this project will be documented in this file.

## 5.0.0 - 2026-05-16

### Added
- Initial Craft CMS 5 release.
- Added the `Image Markers [Super Image Markers]` custom field type.
- Added single-image selection for Craft assets, restricted to image assets.
- Added a Control Panel image preview with draggable round markers.
- Added percentage-based marker coordinates so marker positions stay responsive on the frontend.
- Added double-click entry selection for each marker using Craft's element selector modal.
- Added a Craft-style marker table below the image preview for entry, X, Y, and remove controls.
- Added JSON-backed field storage containing `imageId` and ordered marker data.
- Added Twig-facing value objects for `field.image.one()`, `field.markers.all()`, and `marker.entry.one()`.
- Added field settings for limiting allowed asset sources and entry sources.
- Added validation for selected image assets and marker entry references.
- Added checked-in Control Panel CSS and JavaScript assets without a build step.
- Added package icons, README, license, changelog, and structured documentation under `docs/`.
