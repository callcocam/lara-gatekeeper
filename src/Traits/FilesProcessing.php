<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

trait FilesProcessing
{
    public static function makeDirectory($path)
    {
        // Check if the directory already exists.
        if (!File::isDirectory($path)) {

            // Create the directory.
            File::makeDirectory($path);
        }
    }

    public static function downloadFileFromUrl($url, $filename = null, $subfolder = null)
    {

        $headers = get_headers($url);
        if (!stripos($headers[0], "200 OK")) {
            return null;
        }

        //To add subdirectory to the filename
        $addSubFolder = '';

        if ($subfolder) {
            //Check if the directory exists
            $path =  Storage::disk(static::storageDisk())->path($subfolder);
            static::makeDirectory($path);
            $addSubFolder = $subfolder . '/';
        }
        if (!$filename) {
            // Get the current date and time.
            $dateTime = now();

            // Generate a unique filename.
            $filename = $addSubFolder . $dateTime->format('YmdHis') . '_' . basename($url);
        }


        // Download the file from the URL.
        Storage::disk(static::storageDisk())->put($filename, file_get_contents($url));

        // Get the file url to download
        return [
            'name' =>  $filename,
            'size' => Storage::disk(static::storageDisk())->size($filename),
            'type' => Storage::disk(static::storageDisk())->mimeType($filename),
            'url' => Storage::disk(static::storageDisk())->url($filename)
        ];
    }

    /**
     * Get the disk that cover photos should be stored on.
     *
     * @return string
     */
    protected static function storageDisk()
    {
        return  config('filesystems.default', 's3');
    }
}
