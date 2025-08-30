<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Migrations extends BaseConfig
{
    /**
     * Enable/Disable Migrations.
     */
    public bool $enabled = true;

    /**
     * Path to your migrations folder.
     */
    public string $path = APPPATH . 'Database/Migrations/';

    /**
     * Whether to throw an exception when no migration is found.
     */
    public bool $throwException = true;
}
