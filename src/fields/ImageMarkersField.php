<?php
/**
 * Super Image Markers custom field type.
 *
 * Handles field settings, normalization, validation, Control Panel rendering,
 * and serialization for the image-marker JSON value.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
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

/**
 * Craft field type used to select one image and map entry markers onto it.
 *
 * @author  Amici Infotech
 * @package SuperImageMarkers
 * @since   5.0.0
 */
class ImageMarkersField extends Field
{
    // Public Properties
    // =========================================================================

    /**
     * Allowed Craft asset sources for selecting the map image.
     *
     * @var array|string|null
     */
    public array|string|null $assetSources = '*';

    /**
     * Allowed Craft entry sources for marker relationships.
     *
     * @var array|string|null
     */
    public array|string|null $entrySources = '*';

    /**
     * Default asset source used when uploading images from the field.
     *
     * @var ?string
     */
    public ?string $defaultUploadLocationSource = null;

    /**
     * Optional subpath for uploaded images.
     *
     * @var ?string
     */
    public ?string $defaultUploadLocationSubpath = null;

    /**
     * Whether the native asset selector should allow uploads.
     *
     * @var bool
     */
    public bool $allowUploads = true;

    // Static Methods
    // =========================================================================

    /**
     * Returns the label shown in Craft's field type selector.
     *
     * @return string Translated field type name.
     */
    public static function displayName(): string
    {
        return Craft::t('super-image-markers', 'Image Markers [Super Image Markers]');
    }

    /**
     * Returns the Craft icon name used for the field type.
     *
     * @return string Icon handle.
     */
    public static function icon(): string
    {
        return 'map-pin';
    }

    /**
     * Defines the database column type for stored field content.
     *
     * @return array|string|null JSON column type.
     */
    public static function dbType(): array|string|null
    {
        return Schema::TYPE_JSON;
    }

    /**
     * Defines the normalized PHP type returned by this field.
     *
     * @return string Fully qualified value object class name.
     */
    public static function phpType(): string
    {
        return sprintf('\\%s', ImageMarkersData::class);
    }

    // Public Methods
    // =========================================================================

    /**
     * Renders the field settings screen.
     *
     * @return ?string Settings HTML.
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('super-image-markers/_field/settings', [
            'field' => $this,
            'assetSourceOptions' => $this->getAssetSourceOptions(),
            'entrySourceOptions' => $this->getEntrySourceOptions(),
        ]);
    }

    /**
     * Normalizes stored/submitted data into an ImageMarkersData value object.
     *
     * @param mixed $value Raw field value from content storage or form submission.
     * @param ?ElementInterface $element Owning element, when available.
     *
     * @return mixed Normalized field value.
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof ImageMarkersData) {
            return $value;
        }

        // Craft may pass stored JSON as a string depending on content storage context.
        if (is_string($value)) {
            $value = Json::decodeIfJson($value);
        }

        if (!is_array($value)) {
            $value = [];
        }

        // Native asset selector inputs submit selected IDs as arrays.
        if (isset($value['imageId']) && is_array($value['imageId'])) {
            $value['imageId'] = $this->firstSubmittedId($value['imageId']);
        }

        // Marker rows are stored in a hidden JSON input in the field template.
        if (isset($value['markers']) && is_string($value['markers'])) {
            $value['markers'] = Json::decodeIfJson($value['markers']);
        }

        return ImageMarkersData::fromArray($value);
    }

    /**
     * Serializes the field value for Craft content storage.
     *
     * @param mixed $value Normalized or raw field value.
     * @param ?ElementInterface $element Owning element, when available.
     *
     * @return mixed JSON-safe data or null for empty values.
     */
    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        $value = $this->normalizeValue($value, $element);

        if (!$value instanceof ImageMarkersData || $this->isValueEmpty($value, $element)) {
            return null;
        }

        return $value->serialize();
    }

    /**
     * Determines whether the field should be considered empty.
     *
     * @param mixed $value Normalized or raw field value.
     * @param ElementInterface $element Owning element.
     *
     * @return bool True when no image and no markers are stored.
     */
    public function isValueEmpty(mixed $value, ElementInterface $element): bool
    {
        $value = $this->normalizeValue($value, $element);

        return !$value instanceof ImageMarkersData ||
            ($value->imageId === null && $value->getMarkers()->count() === 0);
    }

    /**
     * Registers element-level validation methods for this field.
     *
     * @return array Validation method names.
     */
    public function getElementValidationRules(): array
    {
        return [
            'validateImage',
            'validateMarkers',
        ];
    }

    /**
     * Validates the selected image asset.
     *
     * @param ElementInterface $element Element containing this field.
     *
     * @return void Nothing is returned.
     */
    public function validateImage(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle);

        if (!$value instanceof ImageMarkersData) {
            return;
        }

        if ($value->imageId === null) {
            // Markers only make sense when an image exists to anchor their coordinates.
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

    /**
     * Validates marker entry references.
     *
     * @param ElementInterface $element Element containing this field.
     *
     * @return void Nothing is returned.
     */
    public function validateMarkers(ElementInterface $element): void
    {
        $value = $element->getFieldValue($this->handle);

        if (!$value instanceof ImageMarkersData) {
            return;
        }

        foreach ($value->getMarkers()->all() as $marker) {
            // Empty marker relationships are allowed while editors are still placing markers.
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

    // Protected Methods
    // =========================================================================

    /**
     * Defines validation rules for the field settings model.
     *
     * @return array Yii validation rules.
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['assetSources', 'entrySources', 'defaultUploadLocationSource', 'defaultUploadLocationSubpath'], 'safe'];
        $rules[] = [['allowUploads'], 'boolean'];

        return $rules;
    }

    /**
     * Renders the Control Panel input for an element edit page.
     *
     * @param mixed $value Normalized or raw field value.
     * @param ?ElementInterface $element Owning element, when available.
     * @param bool $inline Whether Craft is rendering the input inline.
     *
     * @return string Input HTML.
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $value = $this->normalizeValue($value, $element);
        $image = $value instanceof ImageMarkersData ? $value->getImage()->one() : null;

        // Register CP assets before rendering the Twig template that instantiates the JS class.
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

    /**
     * Builds checkbox options for available asset sources.
     *
     * @return array Source options for the settings template.
     */
    public function getAssetSourceOptions(): array
    {
        $sourceOptions = [];

        foreach (Asset::sources('settings') as $source) {
            // Craft source lists include headings; only selectable sources need options.
            if (!isset($source['heading'])) {
                $sourceOptions[] = [
                    'label' => $source['label'],
                    'value' => $source['key'],
                ];
            }
        }

        return $sourceOptions;
    }

    /**
     * Builds checkbox options for available entry sources.
     *
     * @return array Source options for the settings template.
     */
    public function getEntrySourceOptions(): array
    {
        $sourceOptions = [];

        foreach (Entry::sources('settings') as $source) {
            // Craft source lists include headings; only selectable sources need options.
            if (!isset($source['heading'])) {
                $sourceOptions[] = [
                    'label' => $source['label'],
                    'value' => $source['key'],
                ];
            }
        }

        return $sourceOptions;
    }

    // Private Methods
    // =========================================================================

    /**
     * Prepares marker rows for the Control Panel table.
     *
     * @param ImageMarkersData $value Normalized field value.
     *
     * @return array Marker rows enriched with entry titles for the JS input.
     */
    private function markerInputRows(ImageMarkersData $value): array
    {
        $rows = [];

        foreach ($value->getMarkers()->all() as $marker) {
            $row = $marker->serialize();
            // Use status(null) so existing disabled/draft entries can still be displayed in the CP.
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

    /**
     * Normalizes Craft source setting values.
     *
     * @param array|string|null $sources Raw source setting value.
     *
     * @return array|string|null `*` for all sources or a filtered source array.
     */
    private function normalizedSources(array|string|null $sources): array|string|null
    {
        if ($sources === '*' || $sources === null) {
            return $sources;
        }

        $sources = array_values(array_filter((array)$sources));

        return $sources ?: '*';
    }

    /**
     * Builds variables expected by Craft's native Assets field input template.
     *
     * @param ImageMarkersData $value Normalized field value.
     * @param ?Asset $image Current selected image, if any.
     * @param ?ElementInterface $element Owning element, when available.
     *
     * @return array Asset selector variables.
     */
    private function assetInputVariables(ImageMarkersData $value, ?Asset $image, ?ElementInterface $element): array
    {
        // Resolve upload configuration separately so permission checks stay readable.
        $uploadSource = $this->defaultUploadLocationSource ?: $this->defaultAssetSource();
        $uploadVolume = $this->volumeBySourceKey($uploadSource);
        $uploadFs = $uploadVolume?->getFs();
        $uploadFolder = $uploadVolume ? $this->defaultUploadFolder($uploadVolume, $element) : null;

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
                'siteId' => $element?->siteId ?? Craft::$app->getSites()->getCurrentSite()->id,
            ],
            'searchCriteria' => null,
            'sourceElementId' => !empty($element?->id) ? $element->id : null,
            'defaultPlacement' => 'end',
            // List mode saves space compared to large thumbnails in this compound field.
            'viewMode' => 'list',
            'limit' => 1,
            'storageKey' => 'field.' . ($this->id ?: 'super-image-markers'),
            // This is not a native Assets field, so direct uploads must target a folder instead of a field ID.
            'fieldId' => null,
            'prevalidate' => false,
            'canUpload' => (
                $this->allowUploads &&
                $uploadVolume &&
                $uploadFs &&
                // Craft controls final upload permissions, but this hides the upload UI when disallowed.
                Craft::$app->getUser()->checkPermission("saveAssets:$uploadVolume->uid")
            ),
            'fsType' => $uploadFs ? $uploadFs::class : null,
            'defaultFieldLayoutId' => $uploadVolume->fieldLayoutId ?? null,
            'defaultSource' => $uploadSource,
            'defaultSourcePath' => $uploadFolder ? $this->uploadSourcePath($uploadFolder) : null,
            'uploadFolderId' => $uploadFolder?->id,
            'showSourcePath' => true,
            'showFolders' => true,
            'selectionLabel' => Craft::t('super-image-markers', 'Select image'),
            'allowSelfRelations' => false,
        ];
    }

    /**
     * Returns a sensible default asset source for uploads.
     *
     * @return ?string Source key such as `volume:uid`, or null when unavailable.
     */
    private function defaultAssetSource(): ?string
    {
        $sources = $this->normalizedSources($this->assetSources);

        if (is_array($sources) && !empty($sources)) {
            return $sources[0];
        }

        $options = $this->getAssetSourceOptions();

        return $options[0]['value'] ?? null;
    }

    /**
     * Resolves a Craft asset volume from a source key.
     *
     * @param ?string $sourceKey Source key in `volume:uid` format.
     *
     * @return ?Volume Matching volume, or null.
     */
    private function volumeBySourceKey(?string $sourceKey): ?Volume
    {
        if (!$sourceKey || !str_starts_with($sourceKey, 'volume:')) {
            return null;
        }

        return Craft::$app->getVolumes()->getVolumeByUid(substr($sourceKey, 7));
    }

    /**
     * Resolves the default upload subpath for Craft's native asset selector.
     *
     * @param Volume $volume Upload volume.
     * @param ?ElementInterface $element Owning element used for object-template subpaths.
     *
     * @return ?VolumeFolder Upload folder, or null when it cannot be resolved.
     */
    private function defaultUploadFolder(Volume $volume, ?ElementInterface $element): ?VolumeFolder
    {
        if (!$this->defaultUploadLocationSubpath) {
            return Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id);
        }

        try {
            // Let Craft resolve object-template subpaths and ensure the folder exists.
            [$subpath, $folder] = AssetsHelper::resolveSubpath($volume, $this->defaultUploadLocationSubpath, $element);
            $folder ??= Craft::$app->getAssets()->ensureFolderByFullPathAndVolume($subpath, $volume);
        } catch (Throwable) {
            return null;
        }

        return $folder;
    }

    /**
     * Builds the full source path trail for the upload folder.
     *
     * @param VolumeFolder $folder Upload folder.
     *
     * @return array Source path info array for the selector.
     */
    private function uploadSourcePath(VolumeFolder $folder): array
    {
        $folders = [$folder];

        // Craft's selector wants the full parent folder trail, not just the leaf folder.
        while ($folder->parentId && $folder->volumeId !== null) {
            $folder = $folder->getParent();
            array_unshift($folders, $folder);
        }

        return array_map(
            static fn(VolumeFolder $folder): array => $folder->getSourcePathInfo(),
            $folders
        );
    }

    /**
     * Extracts the first numeric ID from a native element selector submission.
     *
     * @param array $ids Submitted ID values.
     *
     * @return ?int First numeric ID, or null.
     */
    private function firstSubmittedId(array $ids): ?int
    {
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                return (int)$id;
            }
        }

        return null;
    }

    /**
     * Adds a validation error to the owning element when possible.
     *
     * @param ElementInterface $element Element being validated.
     * @param string $message Error message.
     *
     * @return void Nothing is returned.
     */
    private function addFieldError(ElementInterface $element, string $message): void
    {
        if ($element instanceof Model) {
            $element->addError($this->handle, $message);
        }
    }

    /**
     * Returns a usable preview URL for the selected image.
     *
     * @param Asset $asset Selected image asset.
     *
     * @return ?string Public asset URL or generated thumbnail URL.
     */
    private function imagePreviewUrl(Asset $asset): ?string
    {
        try {
            // Prefer the original URL so the marker preview is not cropped.
            return $asset->getUrl() ?: Craft::$app->getAssets()->getThumbUrl($asset, 800, 800);
        } catch (Throwable) {
            // Some volumes may not expose original URLs; thumbnail generation is the fallback.
            return Craft::$app->getAssets()->getThumbUrl($asset, 800, 800);
        }
    }
}
