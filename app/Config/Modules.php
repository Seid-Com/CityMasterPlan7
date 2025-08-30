<?php

namespace Config;

use CodeIgniter\Modules\Modules as BaseModules;

/**
 * Modules Configuration file.
 *
 * NOTE: This class is required prior to Autoloader instantiation,
 * and does not extend BaseConfig.
 *
 * @immutable
 */
class Modules extends BaseModules
{
    /**
     * --------------------------------------------------------------------------
     * Enable Auto-Discovery?
     * --------------------------------------------------------------------------
     *
     * If true, then auto-discovery will happen across all elements listed in
     * $activeExplorers below. If false, no auto-discovery will happen at all,
     * giving slightly better performance.
     */
    public $enabled = true;

    /**
     * --------------------------------------------------------------------------
     * Enable Auto-Discovery Within Composer Packages?
     * --------------------------------------------------------------------------
     *
     * If true, then auto-discovery will happen across all namespaces loaded
     * by Composer, as well as the namespaces configured locally.
     */
    public $discoverInComposer = true;

    /**
     * --------------------------------------------------------------------------
     * Auto-Discovery Rules
     * --------------------------------------------------------------------------
     *
     * Lists the aliases of all discovery classes that will be active
     * and used during the current application request.
     */
    public $aliases = [
        'events',
        'filters',
        'registrars',
        'routes',
        'services',
    ];

    /**
     * Should the application auto-discover the requested resources.
     */
    public $composerPackages = [];
}