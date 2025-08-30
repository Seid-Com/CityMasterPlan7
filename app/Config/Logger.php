<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Logger extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Error Logging Threshold
     * --------------------------------------------------------------------------
     *
     * You can enable error logging by setting a threshold over zero. The
     * threshold determines what gets logged. Any log level at or above the
     * threshold will be logged.
     *
     * Threshold options are:
     *  0 = Disables logging, Error logging TURNED OFF
     *  1 = Emergency Messages  (System is unusable)
     *  2 = Alert Messages      (Action Must Be Taken Immediately)
     *  3 = Critical Messages   (Application component unavailable, unexpected exception)
     *  4 = Runtime Errors      (Don't need immediate action, but should be monitored)
     *  5 = Warnings            (Exceptional occurrences that are not errors)
     *  6 = Notices             (Normal but significant events)
     *  7 = Info                (Interesting events, like user logging in, etc.)
     *  8 = Debug               (Detailed debug information)
     *
     * For a live site you'll usually enable Critical or higher (3) to be logged.
     * For development, you might typically enable Notices & higher (6) to be logged.
     */
    public int $threshold = 9;

    /**
     * --------------------------------------------------------------------------
     * Date Format for Logs
     * --------------------------------------------------------------------------
     *
     * Each item that is logged has an associated date. You can use PHP date
     * codes to set your own date formatting
     */
    public string $dateFormat = 'Y-m-d H:i:s';

    /**
     * --------------------------------------------------------------------------
     * Log Handlers
     * --------------------------------------------------------------------------
     *
     * The logging system supports multiple handlers that can process and store
     * log entries as needed. By default, CodeIgniter ships with two handlers:
     * file and chromephp (intended for use with the ChromeLogger extension
     * for Chrome/Chromium).
     *
     * The key in the array should be the handler's class name. The value
     * should be an array of configuration items to pass to the constructor.
     * The only required configuration item is the 'path' element, which holds
     * the location where the logs should be written to.
     */
    public array $handlers = [
        /*
         * --------------------------------------------------------------------
         * File Handler
         * --------------------------------------------------------------------
         */
        'CodeIgniter\Log\Handlers\FileHandler' => [
            /*
             * The log levels this handler will handle.
             */
            'handles' => ['critical', 'alert', 'emergency', 'debug', 'error', 'info', 'notice', 'warning'],

            /*
             * The default filename extension for log files. The default
             * creates daily log files with the date appended to the filename.
             */
            'fileExtension' => '',

            /*
             * The file system permissions to be applied to the log file.
             */
            'filePermissions' => 0644,

            /*
             * Logging Directory
             */
            'path' => WRITEPATH . 'logs/',
        ],

        /*
         * --------------------------------------------------------------------
         * ChromeLogger Handler
         * --------------------------------------------------------------------
         * Requires the use of the Chrome web browser and the ChromeLogger extension.
         * Uncomment this block to use.
         */
        // 'CodeIgniter\Log\Handlers\ChromeLoggerHandler' => [
        //     /*
        //      * The log levels this handler will handle.
        //      */
        //     'handles' => ['critical', 'alert', 'emergency', 'debug',
        //                   'error', 'info', 'notice', 'warning'],
        // ]
    ];
}