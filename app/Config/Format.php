<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Format extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Available Response Formats
     * --------------------------------------------------------------------------
     *
     * When you set the format that a response should be in, the Response class
     * will look here to see what class handles that format.
     */
    public array $supportedResponseFormats = [
        'application/json' => 'json',
        'application/xml'  => 'xml',
        'text/xml'         => 'xml',
        'text/html'        => 'html',
        'text/plain'       => 'txt',
        'text/csv'         => 'csv',
        'application/pdf'  => 'pdf',
        'application/rtf'  => 'rtf',
    ];

    /**
     * --------------------------------------------------------------------------
     * Formatters
     * --------------------------------------------------------------------------
     *
     * Lists the class to use to format responses in the format they request.
     * For now, there are not many to choose from, but it does allow you to
     * create custom formatters very easily.
     */
    public array $formatters = [
        'application/json' => 'CodeIgniter\Format\JSONFormatter',
        'application/xml'  => 'CodeIgniter\Format\XMLFormatter',
        'text/xml'         => 'CodeIgniter\Format\XMLFormatter',
    ];

    /**
     * --------------------------------------------------------------------------
     * Formatter Configuration
     * --------------------------------------------------------------------------
     *
     * These are the settings for the individual formatters. These settings are
     * usually specific to the formatter implementation.
     */
    public array $formatterOptions = [
        'application/json' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        'application/xml'  => [],
        'text/xml'         => [],
    ];
}