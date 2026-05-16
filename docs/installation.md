# Installation and Setup

## Requirements

- Craft CMS 5
- PHP 8.2 or newer
- At least one asset volume for image assets
- At least one entry section if markers should link to entries

## Install From a Local Path Repository

Add the plugin to your Craft project:

```bash
composer require amici/craft-super-image-markers
```

Then install and enable it:

```bash
php craft plugin/install super-image-markers
```

You can also install it from the Control Panel at **Settings -> Plugins**.

## First Setup Checklist

1. Confirm your Craft project has an asset volume that stores images.
2. Confirm your project has the entry sections that markers should link to.
3. Go to **Settings -> Fields**.
4. Create a field with the type **Image Markers [Super Image Markers]**.
5. Choose allowed asset sources if editors should only pick from specific volumes.
6. Choose the default upload location if editors should upload new images from the field input.
7. Enable or disable uploads for this field.
8. Choose allowed entry sources if markers should only link to specific sections.
9. Add the field to the field layout where editors will use it.
10. Edit an element, select or upload an image, add markers, choose colors, sort rows, and choose entries.

## Local Development Notes

This plugin follows the same lightweight pattern as other Amici Craft 5 plugins in this workspace:

- PHP code is under `src/`.
- Control Panel templates are under `src/templates/`.
- Control Panel CSS and JavaScript are checked in under `src/resources/dist/`.
- There is no frontend build step required for the plugin assets.
- Example frontend templates are stored in the Craft project under `templates/suoer-image-markers/`.

## Updating Composer Metadata

The plugin package is `amici/craft-super-image-markers` and the Craft plugin handle is `super-image-markers`.

For local path repositories, make sure the main Craft project's `composer.json` points to the plugin folder if it is not installed from a package registry.

## After Installation

If the Control Panel field UI looks stale after changing plugin assets during local development, clear Craft's asset resources or hard refresh the browser. The plugin registers `src/resources/dist/css/input.css` and `src/resources/dist/js/input.js` through a Craft asset bundle.
