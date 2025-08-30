<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use Throwable;

/**
 * Setup how the exception handler works.
 */
class Exceptions extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * HIDE FROM DEBUG TRACE
     * --------------------------------------------------------------------------
     *
     * Any data that you would like to hide from the debug trace.
     * In order to specify 2 levels, use "/" to separate.
     * ex. ['server', 'setup/password', 'secret_token']
     *
     * @var array<string>
     */
    public array $sensitiveDataInTrace = [];

    /**
     * --------------------------------------------------------------------------
     * LOG EXCEPTIONS?
     * --------------------------------------------------------------------------
     * If true, then exceptions will be logged through Services::Log.
     *
     * Default: true
     */
    public bool $log = true;

    /**
     * --------------------------------------------------------------------------
     * DO NOT LOG STATUS CODES
     * --------------------------------------------------------------------------
     * Any status codes here will NOT be logged if logging is turned on.
     * By default, only 404 (Page Not Found) exceptions are ignored.
     */
    public array $ignoreCodes = [404];

    /**
     * --------------------------------------------------------------------------
     * Error Views Path
     * --------------------------------------------------------------------------
     * This is the path to the directory that contains the 'cli' and 'html'
     * directories that hold the views used to generate errors.
     *
     * Default: APPPATH.'Views/errors'
     */
    public string $errorViewPath = APPPATH . 'Views/errors';

    /**
     * --------------------------------------------------------------------------
     * DEPRECATED NOTICES
     * --------------------------------------------------------------------------
     * Should deprecated notices be logged or not?
     */
    public bool $logDeprecations = true;

    /**
     * --------------------------------------------------------------------------
     * DEPRECATED NOTICES LOG LEVEL
     * --------------------------------------------------------------------------
     * If `$logDeprecations` is true, this sets the log level
     * to which the deprecation will be logged. This should be
     * one of the recognized log levels:
     *
     * - emergency
     * - alert
     * - critical
     * - error
     * - warning
     * - notice
     * - info
     * - debug
     */
    public string $deprecationLogLevel = 'warning';
}