<?php
/**
 * Super Image Markers plugin for Craft CMS 5.x.
 *
 * Registers a custom field type that lets editors map entries to percentage-based
 * markers on top of a selected image asset.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperImageMarkers;

use amici\SuperImageMarkers\fields\ImageMarkersField;
use amici\SuperImageMarkers\models\Settings;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use yii\base\Event;

/**
 * Main plugin class.
 *
 * Keeps the plugin intentionally small because all editing behavior lives in the
 * custom field type and its Control Panel asset bundle.
 *
 * @author  Amici Infotech
 * @package SuperImageMarkers
 * @since   5.0.0
 *
 * @property Settings $settings
 * @method Settings getSettings()
 */
class Plugin extends BasePlugin
{
    // Public Properties
    // =========================================================================

    /**
     * Current plugin instance.
     *
     * @var Plugin
     */
    public static Plugin $plugin;

    /**
     * Schema version used by Craft when installing/updating the plugin.
     *
     * @var string
     */
    public string $schemaVersion = '5.0.0';

    /**
     * The plugin does not need a global settings page.
     *
     * @var bool
     */
    public bool $hasCpSettings = false;

    /**
     * The plugin only registers a field type, so it does not add a CP section.
     *
     * @var bool
     */
    public bool $hasCpSection = false;

    // Public Methods
    // =========================================================================

    /**
     * Initializes the plugin and registers its Craft integrations.
     *
     * @return void Nothing is returned.
     */
    public function init(): void
    {
        parent::init();

        // Store the instance for code that follows the common Craft plugin pattern.
        self::$plugin = $this;
        $this->registerFields();
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the settings model Craft expects for plugins.
     *
     * @return ?Model The settings model instance.
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    // Private Methods
    // =========================================================================

    /**
     * Registers the Super Image Markers custom field type with Craft.
     *
     * @return void Nothing is returned.
     */
    private function registerFields(): void
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            static function(RegisterComponentTypesEvent $event): void {
                // Add our field class to Craft's field type picker.
                $event->types[] = ImageMarkersField::class;
            }
        );
    }
}
