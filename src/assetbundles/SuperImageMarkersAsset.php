<?php
namespace amici\SuperImageMarkers\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class SuperImageMarkersAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@amici/SuperImageMarkers/resources/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/input.css',
        ];

        $this->js = [
            'js/input.js',
        ];

        parent::init();
    }
}
