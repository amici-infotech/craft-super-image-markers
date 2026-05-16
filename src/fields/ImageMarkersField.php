<?php
namespace amici\SuperImageMarkers\fields;

use amici\SuperImageMarkers\assetbundles\SuperImageMarkersAsset;
use amici\SuperImageMarkers\fields\data\ImageMarkersData;
use amici\SuperImageMarkers\fields\data\MarkerData;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\Json;
use Throwable;
use yii\db\Schema;

class ImageMarkersField extends Field
{
    public array|string|null $assetSources = '*';
    public array|string|null $entrySources = '*';

    public static function displayName(): string
    {
        return Craft::t('super-image-markers', 'Image Markers [Super Image Markers]');
    }

    public static function icon(): string
    {
        return 'map-pin';
    }

    public static function dbType(): array|string|null
    {
        return Schema::TYPE_JSON;
    }

    public static function phpType(): string
    {
        return sprintf('\\%s', ImageMarkersData::class);
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('super-image-markers/_field/settings', [
            'field' => $this,
            'assetSourceOptions' => $this->getAssetSourceOptions(),
            'entrySourceOptions' => $this->getEntrySourceOptions(),
        ]);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof ImageMarkersData) {
            return $value;
        }

        if (is_string($value)) {
            $value = Json::decodeIfJson($value);
        }

        if (!is_array($value)) {
            $value = [];
        }

        return ImageMarkersData::fromArray($value);
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = $this->normalizeValue($value, $element);

        if (!$value instanceof ImageMarkersData || $this->isValueEmpty($value, $element)) {
            return null;
        }

        return $value->serialize();
    }

    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        $value = $this->normalizeValue($value, $element);

        return !$value instanceof ImageMarkersData ||
            ($value->imageId === null && $value->getMarkers()->count() === 0);
    }

    public function getElementValidationRules(): array
    {
        return [
            'validateImage',
            'validateMarkers',
        ];
    }

    public function validateImage(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle);

        if (!$value instanceof ImageMarkersData) {
            return;
        }

        if ($value->imageId === null) {
            if ($value->getMarkers()->count() > 0) {
                $element->addError($this->handle, Craft::t('super-image-markers', 'Select an image before adding markers.'));
            }

            return;
        }

        $asset = Asset::find()
            ->id($value->imageId)
            ->kind('image')
            ->status(null)
            ->site('*')
            ->one();

        if (!$asset instanceof Asset) {
            $element->addError($this->handle, Craft::t('super-image-markers', 'Select a valid image asset.'));
        }
    }

    public function validateMarkers(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle);

        if (!$value instanceof ImageMarkersData) {
            return;
        }

        foreach ($value->getMarkers()->all() as $marker) {
            if (!$marker instanceof MarkerData || $marker->entryId === null) {
                continue;
            }

            $entry = Entry::find()
                ->id($marker->entryId)
                ->status(null)
                ->site('*')
                ->unique()
                ->one();

            if (!$entry instanceof Entry) {
                $element->addError($this->handle, Craft::t('super-image-markers', 'One or more markers reference an invalid entry.'));
                return;
            }
        }
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['assetSources', 'entrySources'], 'safe'];

        return $rules;
    }

    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $value = $this->normalizeValue($value, $element);
        $image = $value instanceof ImageMarkersData ? $value->getImage()->one() : null;

        Craft::$app->getView()->registerAssetBundle(SuperImageMarkersAsset::class);

        return Craft::$app->getView()->renderTemplate('super-image-markers/_field/input', [
            'field' => $this,
            'value' => $value,
            'id' => $this->getInputId(),
            'name' => $this->handle,
            'image' => $image,
            'imageUrl' => $image instanceof Asset ? $this->imagePreviewUrl($image) : null,
            'markers' => $this->markerInputRows($value),
            'assetSources' => $this->normalizedSources($this->assetSources),
            'entrySources' => $this->normalizedSources($this->entrySources),
        ]);
    }

    public function getAssetSourceOptions(): array
    {
        $sourceOptions = [];

        foreach (Asset::sources('settings') as $source) {
            if (!isset($source['heading'])) {
                $sourceOptions[] = [
                    'label' => $source['label'],
                    'value' => $source['key'],
                ];
            }
        }

        return $sourceOptions;
    }

    public function getEntrySourceOptions(): array
    {
        $sourceOptions = [];

        foreach (Entry::sources('settings') as $source) {
            if (!isset($source['heading'])) {
                $sourceOptions[] = [
                    'label' => $source['label'],
                    'value' => $source['key'],
                ];
            }
        }

        return $sourceOptions;
    }

    private function markerInputRows(ImageMarkersData $value): array
    {
        $rows = [];

        foreach ($value->getMarkers()->all() as $marker) {
            $row = $marker->serialize();
            $entry = $marker->entryId ? Entry::find()
                ->id($marker->entryId)
                ->status(null)
                ->site('*')
                ->unique()
                ->one() : null;

            $row['entryTitle'] = $entry instanceof Entry ? (string)$entry : null;
            $rows[] = $row;
        }

        return $rows;
    }

    private function normalizedSources(array|string|null $sources): array|string|null
    {
        if ($sources === '*' || $sources === null) {
            return $sources;
        }

        $sources = array_values(array_filter((array)$sources));

        return $sources ?: '*';
    }

    private function imagePreviewUrl(Asset $asset): ?string
    {
        try {
            return $asset->getUrl() ?: $asset->getThumbUrl(800);
        } catch (Throwable) {
            return $asset->getThumbUrl(800);
        }
    }
}
