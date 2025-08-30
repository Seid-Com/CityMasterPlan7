<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Toolbar extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Toolbar
     * --------------------------------------------------------------------------
     *
     * Whether to display the debug toolbar when in development mode.
     */
    public array $collectors = [];

    /**
     * --------------------------------------------------------------------------
     * Collect Benchmarks
     * --------------------------------------------------------------------------
     *
     * Whether to collect benchmark data.
     */
    public bool $collectBenchmarks = false;

    /**
     * --------------------------------------------------------------------------
     * Collect Variables
     * --------------------------------------------------------------------------
     *
     * Whether to collect view variable data.
     */
    public bool $collectVarData = true;

    /**
     * --------------------------------------------------------------------------
     * Max History
     * --------------------------------------------------------------------------
     *
     * The maximum number of history items to keep.
     */
    public int $maxHistory = 20;

    /**
     * --------------------------------------------------------------------------
     * Restrict Access
     * --------------------------------------------------------------------------
     *
     * Whether to restrict toolbar access to specified IP addresses.
     */
    public bool $restrictTo = false;

    /**
     * --------------------------------------------------------------------------
     * Allowed IPs
     * --------------------------------------------------------------------------
     *
     * Array of IP addresses allowed to view the toolbar.
     */
    public array $allowedIPs = [];
}