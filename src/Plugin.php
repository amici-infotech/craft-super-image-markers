<?php
namespace amici\SuperImageMarkers;

use amici\SuperImageMarkers\fields\ImageMarkersField;
use amici\SuperImageMarkers\models\Settings;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use yii\base\Event;

class Plugin extends BasePlugin
{
    public static Plugin $plugin;
    public string $schemaVersion = '5.0.0';
    public bool $hasCpSettings = false;
    public bool $hasCpSection = false;

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;
        $this->registerFields();
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    private function registerFields(): void
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            static function(RegisterComponentTypesEvent $event): void {
                $event->types[] = ImageMarkersField::class;
            }
        );
    }
}
