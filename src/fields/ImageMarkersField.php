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
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\Json;
use craft\models\Volume;
use craft\models\VolumeFolder;
use Throwable;
use yii\base\Model;
use yii\db\Schema;

class ImageMarkersField extends Field
{
    public array|string|null $assetSources = '*';
    public array|string|null $entrySources = '*';
    public ?string $defaultUploadLocationSource = null;
    public ?string $defaultUploadLocationSubpath = null;
    public bool $allowUploads = true;

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

        if (isset($value['imageId']) && is_array($value['imageId'])) {
            $value['imageId'] = $this->firstSubmittedId($value['imageId']);
        }

        if (isset($value['markers']) && is_string($value['markers'])) {
            $value['markers'] = Json::decodeIfJson($value['markers']);
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
                $this->addFieldError($element, Craft::t('super-image-markers', 'Select an image before adding markers.'));
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
            $this->addFieldError($element, Craft::t('super-image-markers', 'Select a valid image asset.'));
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
                $this->addFieldError($element, Craft::t('super-image-markers', 'One or more markers reference an invalid entry.'));
                return;
            }
        }
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['assetSources', 'entrySources', 'defaultUploadLocationSource', 'defaultUploadLocationSubpath'], 'safe'];
        $rules[] = [['allowUploads'], 'boolean'];

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
            'assetInput' => $this->assetInputVariables($value, $image, $element),
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

    private function assetInputVariables(ImageMarkersData $value, ?Asset $image, ?ElementInterface $element): array
    {
        $uploadSource = $this->defaultUploadLocationSource ?: $this->defaultAssetSource();
        $uploadVolume = $this->volumeBySourceKey($uploadSource);
        $uploadFs = $uploadVolume?->getFs();

        return [
            'id' => sprintf('%s-image', $this->getInputId()),
            'name' => sprintf('%s[imageId]', $this->handle),
            'jsClass' => 'Craft.AssetSelectInput',
            'elementType' => Asset::class,
            'elements' => $image ? [$image] : [],
            'sources' => $this->normalizedSources($this->assetSources),
            'condition' => null,
            'referenceElement' => $element,
            'criteria' => [
                'kind' => ['image'],
            ],
            'searchCriteria' => null,
            'sourceElementId' => !empty($element?->id) ? $element->id : null,
            'defaultPlacement' => 'end',
            'viewMode' => 'list',
            'limit' => 1,
            'storageKey' => 'field.' . ($this->id ?: 'super-image-markers'),
            'fieldId' => $this->id,
            'prevalidate' => false,
            'canUpload' => (
                $this->allowUploads &&
                $uploadVolume &&
                $uploadFs &&
                Craft::$app->getUser()->checkPermission("saveAssets:$uploadVolume->uid")
            ),
            'fsType' => $uploadFs ? $uploadFs::class : null,
            'defaultFieldLayoutId' => $uploadVolume->fieldLayoutId ?? null,
            'defaultSource' => $uploadSource,
            'defaultSourcePath' => $uploadVolume ? $this->defaultUploadSourcePath($uploadVolume, $element) : null,
            'showSourcePath' => true,
            'showFolders' => true,
            'selectionLabel' => Craft::t('super-image-markers', 'Select image'),
            'allowSelfRelations' => false,
        ];
    }

    private function defaultAssetSource(): ?string
    {
        $sources = $this->normalizedSources($this->assetSources);

        if (is_array($sources) && !empty($sources)) {
            return $sources[0];
        }

        $options = $this->getAssetSourceOptions();

        return $options[0]['value'] ?? null;
    }

    private function volumeBySourceKey(?string $sourceKey): ?Volume
    {
        if (!$sourceKey || !str_starts_with($sourceKey, 'volume:')) {
            return null;
        }

        return Craft::$app->getVolumes()->getVolumeByUid(substr($sourceKey, 7));
    }

    private function defaultUploadSourcePath(Volume $volume, ?ElementInterface $element): ?array
    {
        if (!$this->defaultUploadLocationSubpath) {
            return null;
        }

        try {
            [$subpath, $folder] = AssetsHelper::resolveSubpath($volume, $this->defaultUploadLocationSubpath, $element);
            $folder ??= Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($subpath, $volume);
        } catch (Throwable) {
            return null;
        }

        $folders = [$folder];

        while ($folder->parentId && $folder->volumeId !== null) {
            $folder = $folder->getParent();
            array_unshift($folders, $folder);
        }

        return array_map(
            static fn(VolumeFolder $folder): array => $folder->getSourcePathInfo(),
            $folders
        );
    }

    private function firstSubmittedId(array $ids): ?int
    {
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                return (int)$id;
            }
        }

        return null;
    }

    private function addFieldError(ElementInterface $element, string $message): void
    {
        if ($element instanceof Model) {
            $element->addError($this->handle, $message);
        }
    }

    private function imagePreviewUrl(Asset $asset): ?string
    {
        try {
            return $asset->getUrl() ?: Craft::$app->getAssets()->getThumbUrl($asset, 800, 800);
        } catch (Throwable) {
            return Craft::$app->getAssets()->getThumbUrl($asset, 800, 800);
        }
    }
}
