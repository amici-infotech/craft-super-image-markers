<?php
/**
 * Control Panel asset bundle for Super Image Markers.
 *
 * Publishes the checked-in CSS and JavaScript used by the field input.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperImageMarkers\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Registers the field input CSS and JavaScript with Craft's Control Panel.
 *
 * @author  Amici Infotech
 * @package SuperImageMarkers
 * @since   5.0.0
 */
class SuperImageMarkersAsset extends AssetBundle
{
    /**
     * Configures the asset source path and published files.
     *
     * @return void Nothing is returned.
     */
    public function init(): void
    {
        // Use the plugin alias so Craft can publish assets regardless of install path.
        $this->sourcePath = '@amici/SuperImageMarkers/resources/dist';

        // Depend on Craft's CP assets so variables, icons, and Garnish are ready.
        $this->depends = [
            CpAsset::class,
        ];

        // Field-specific styles for the image preview, markers, and marker table.
        $this->css = [
            'css/input.css',
        ];

        // Field-specific behavior for image selection, dragging, sorting, and entry modals.
        $this->js = [
            'js/input.js',
        ];

        parent::init();
    }
}
