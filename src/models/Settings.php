<?php
/**
 * Settings model for Super Image Markers.
 *
 * The plugin currently stores all configurable behavior on the field instance,
 * but Craft still expects a settings model for plugin metadata.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */
namespace amici\SuperImageMarkers\models;

use craft\base\Model;

/**
 * Empty plugin-level settings model.
 *
 * Field-specific settings live in ImageMarkersField because each field can use
 * different asset sources, upload locations, and entry sources.
 *
 * @author  Amici Infotech
 * @package SuperImageMarkers
 * @since   5.0.0
 */
class Settings extends Model
{
}
