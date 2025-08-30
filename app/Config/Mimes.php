<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Mimes extends BaseConfig
{
    /**
     * Map of extensions to mime types.
     *
     * @var array<string, array|string>
     */
    public array $mimes = [
        'zip' => [
            'application/x-zip',
            'application/zip',
            'application/x-zip-compressed',
            'application/s-compressed',
            'multipart/x-zip',
        ],
        'shp' => [
            'application/octet-stream',
            'application/x-esri-shape',
        ],
        'dbf' => [
            'application/octet-stream',
            'application/x-dbase',
        ],
        'shx' => [
            'application/octet-stream',
        ],
        'prj' => [
            'text/plain',
        ],
    ];

    /**
     * Determines if a filename is one of the given extensions.
     *
     * @param array|string $extensions
     */
    public static function guessExtensionFromType(string $type, ?string $proposedExtension = null): ?string
    {
        $mimes = new static();

        foreach ($mimes->mimes as $ext => $types) {
            if ((is_string($types) && $types === $type) || (is_array($types) && in_array($type, $types, true))) {
                return $ext;
            }
        }

        return null;
    }
}