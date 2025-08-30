<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Routing configuration
 */
class Routing extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Default Namespace
     * --------------------------------------------------------------------------
     *
     * This value is used when no namespace has been specified for a controller.
     * When a namespace is specified, this value is ignored.
     *
     * NOTE: If you set this to an empty string, you will need to specify the full
     * class name to the controller when defining routes, i.e. \App\Controllers\Home
     */
    public string $defaultNamespace = 'App\Controllers';

    /**
     * --------------------------------------------------------------------------
     * Default Controller
     * --------------------------------------------------------------------------
     *
     * This value is used when no other controller is specified in the route.
     * When the user visits "/" (the root), this controller will handle the request.
     */
    public string $defaultController = 'Home';

    /**
     * --------------------------------------------------------------------------
     * Default Method
     * --------------------------------------------------------------------------
     *
     * This value is used when no other method is specified in the route.
     * It's the method that will be called on the controller when the user
     * visits the route.
     */
    public string $defaultMethod = 'index';

    /**
     * --------------------------------------------------------------------------
     * Translate URI Dashes
     * --------------------------------------------------------------------------
     *
     * This determines whether dashes in URLs are translated to underscores when
     * looking for a method. If true, dashes in the URL will be translated to
     * underscores when looking for a method.
     */
    public bool $translateURIDashes = false;

    /**
     * --------------------------------------------------------------------------
     * Override HTTP Method
     * --------------------------------------------------------------------------
     *
     * When set to true, this setting allows you to override the request method
     * by using a hidden form field called '_method'. This is useful for
     * simulating PUT, DELETE, and other HTTP methods in browsers that don't
     * support them natively.
     */
    public bool $overrideMethod = true;

    /**
     * --------------------------------------------------------------------------
     * Auto Route (Legacy)
     * --------------------------------------------------------------------------
     *
     * If you want to use the legacy auto-routing feature, set this to true.
     * It will allow URLs to be automatically routed to the controller and method
     * that matches the URI segments. This should not be used for new projects.
     */
    public bool $autoRoute = false;
    public bool $autoRouteLegacy = false;

    /**
     * --------------------------------------------------------------------------
     * Auto Route (Improved)
     * --------------------------------------------------------------------------
     *
     * The improved auto-routing feature allows automatic routing to controllers
     * and methods. It's more secure than the legacy auto-routing, but still
     * allows automatic URL routing.
     */
    public bool $autoRouteImproved = false;

    /**
     * --------------------------------------------------------------------------
     * 404 Override
     * --------------------------------------------------------------------------
     *
     * This setting allows you to override the default 404 page with a custom
     * controller and method. The value should be either a string defining
     * the controller/method to use, or a callable to use as the 404 override.
     */
    public ?string $override404 = null;

    /**
     * --------------------------------------------------------------------------
     * Priority Detect
     * --------------------------------------------------------------------------
     *
     * When set to true, this setting allows CodeIgniter to detect the request
     * method from HTTP headers. This is useful when working with APIs that
     * use different HTTP methods.
     */
    public bool $prioritize = true;
    public bool $prioritizeDetected = true;

    /**
     * --------------------------------------------------------------------------
     * Route Files
     * --------------------------------------------------------------------------
     *
     * List of route files to be loaded by the router.
     */
    public array $routeFiles = [
        APPPATH . 'Config/Routes.php',
    ];
}