<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Helpers;

class ResourceRouteHelper
{
    /**
     * Get default options for resources with all extensions
     */
    public static function withAllExtensions(): array
    {
        return [
            'extensions' => ['bulk', 'import', 'export']
        ];
    }

    /**
     * Get options for resources with only bulk functionality
     */
    public static function withBulkOnly(): array
    {
        return [
            'extensions' => ['bulk']
        ];
    }

    /**
     * Get options for resources with import/export only
     */
    public static function withDataTransfer(): array
    {
        return [
            'extensions' => ['import', 'export']
        ];
    }

    /**
     * Get options for resources with custom bulk routes
     */
    public static function withCustomBulk(array $bulkRoutes): array
    {
        return [
            'extensions' => ['bulk'],
            'bulk_routes' => $bulkRoutes
        ];
    }

    /**
     * Get options for resources with custom import routes
     */
    public static function withCustomImport(array $importRoutes): array
    {
        return [
            'extensions' => ['import'],
            'import_routes' => $importRoutes
        ];
    }

    /**
     * Get options for resources with custom export routes
     */
    public static function withCustomExport(array $exportRoutes): array
    {
        return [
            'extensions' => ['export'],
            'export_routes' => $exportRoutes
        ];
    }

    /**
     * Get options for resources with only specific extensions
     */
    public static function withOnly(array $extensions): array
    {
        return [
            'extensions' => $extensions
        ];
    }

    /**
     * Get options for resources excluding specific extensions
     */
    public static function without(array $excludeExtensions): array
    {
        $allExtensions = ['bulk', 'import', 'export'];
        $extensions = array_diff($allExtensions, $excludeExtensions);
        
        return [
            'extensions' => $extensions
        ];
    }

    /**
     * Get options for a completely custom resource configuration
     */
    public static function custom(array $config): array
    {
        return $config;
    }
}
