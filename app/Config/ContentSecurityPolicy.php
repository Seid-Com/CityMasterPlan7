<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Stores the default settings for the ContentSecurityPolicy, if you
 * choose to use it. The values here will be read in and set as defaults
 * for the site. If needed, they can be overridden on a page-by-page basis.
 *
 * Suggested reference for explanations:
 *
 * @see https://www.html5rocks.com/en/tutorials/security/content-security-policy/
 */
class ContentSecurityPolicy extends BaseConfig
{
    //-------------------------------------------------------------------------
    // Broadest Possible Policy
    //-------------------------------------------------------------------------

    /**
     * Default CSP report context
     */
    public bool $reportOnly = false;

    /**
     * Specifies a URL where a browser will send reports
     * when a content security policy is violated.
     */
    public ?string $reportURI = null;

    /**
     * Will add the given JS file to the CSP if true.
     * However, in most cases, this is not necessary.
     * The CSP is meant to be a last line of defense.
     */
    public bool $autoNonce = true;

    //-------------------------------------------------------------------------
    // Sources allowed for scripts
    //-------------------------------------------------------------------------

    /**
     * Scripts from self (current domain) are allowed.
     */
    public ?string $scriptSrc = 'self';

    /**
     * Scripts from these URLs are allowed.
     */
    public ?array $scriptSrcAllowedUrls = [
        'https://unpkg.com',
        'https://cdn.jsdelivr.net',
        'https://cdnjs.cloudflare.com',
    ];

    //-------------------------------------------------------------------------
    // Sources allowed for stylesheets
    //-------------------------------------------------------------------------

    /**
     * Stylesheets from self (current domain) are allowed.
     */
    public ?string $styleSrc = 'self';

    /**
     * Stylesheets from these URLs are allowed.
     */
    public ?array $styleSrcAllowedUrls = [
        'https://unpkg.com',
        'https://cdn.jsdelivr.net',
        'https://cdnjs.cloudflare.com',
    ];

    //-------------------------------------------------------------------------
    // Sources allowed for images
    //-------------------------------------------------------------------------

    /**
     * Images from self (current domain) are allowed.
     */
    public ?string $imageSrc = 'self';

    /**
     * Images from these URLs are allowed.
     */
    public ?array $imageSrcAllowedUrls = [
        'data:',
        'https:',
        'https://tile.openstreetmap.org',
        'https://a.tile.openstreetmap.org',
        'https://b.tile.openstreetmap.org',
        'https://c.tile.openstreetmap.org',
    ];

    //-------------------------------------------------------------------------
    // Sources allowed for AJAX, WebSocket and EventSource connections
    //-------------------------------------------------------------------------

    /**
     * Specifies the servers from which a page can load resources using scripts.
     */
    public ?string $connectSrc = 'self';

    /**
     * Allow AJAX, WebSocket and EventSource connections to these URLs.
     */
    public ?array $connectSrcAllowedUrls = null;

    //-------------------------------------------------------------------------
    // Sources allowed for fonts
    //-------------------------------------------------------------------------

    /**
     * Fonts from self (current domain) are allowed.
     */
    public ?string $fontSrc = null;

    /**
     * Fonts from these URLs are allowed.
     */
    public ?array $fontSrcAllowedUrls = [
        'https://cdnjs.cloudflare.com',
    ];

    //-------------------------------------------------------------------------
    // Sources allowed for objects, embed, and applet elements
    //-------------------------------------------------------------------------

    /**
     * Objects from self (current domain) are allowed.
     */
    public ?string $objectSrc = null;

    /**
     * Objects from these URLs are allowed.
     */
    public ?array $objectSrcAllowedUrls = null;

    //-------------------------------------------------------------------------
    // Sources allowed for audio and video elements
    //-------------------------------------------------------------------------

    /**
     * Audio/Video from self (current domain) are allowed.
     */
    public ?string $mediaSrc = null;

    /**
     * Audio/Video from these URLs are allowed.
     */
    public ?array $mediaSrcAllowedUrls = null;

    //-------------------------------------------------------------------------
    // Sources allowed for iframes
    //-------------------------------------------------------------------------

    /**
     * Iframes from self (current domain) are allowed.
     */
    public ?string $frameSrc = null;

    /**
     * Iframes from these URLs are allowed.
     */
    public ?array $frameSrcAllowedUrls = null;

    //-------------------------------------------------------------------------
    // Misc Restrictions
    //-------------------------------------------------------------------------

    /**
     * Base tag URL restrictions.
     */
    public ?array $baseURI = null;

    /**
     * Restricts the URLs that can be used as the action of HTML form elements.
     */
    public ?array $formAction = null;

    /**
     * Specifies the sources from which a page can embed frames.
     * This directive applies to nested browsing contexts loaded via elements
     * such as <frame> and <iframe>
     */
    public ?string $frameAncestors = null;

    /**
     * Restricts the URLs to which a page can initiate navigations.
     */
    public ?array $navigateTo = null;

    /**
     * Instructs a browser to activate or deactivate any heuristics used to
     * filter or block reflected cross-site scripting attacks.
     */
    public ?string $upgradeInsecureRequests = null;

    //-------------------------------------------------------------------------
    // Mapping of generated CSP directives
    //-------------------------------------------------------------------------

    /**
     * Maps the policy directives to the header names
     * that should be sent. Any changes here will need
     * to be updated in the ContentSecurityPolicy class as well.
     */
    public array $mappings = [
        'base-uri'                => 'baseURI',
        'child-src'              => 'childSrc',
        'connect-src'            => 'connectSrc',
        'default-src'            => 'defaultSrc',
        'font-src'               => 'fontSrc',
        'form-action'            => 'formAction',
        'frame-ancestors'        => 'frameAncestors',
        'frame-src'              => 'frameSrc',
        'img-src'                => 'imageSrc',
        'media-src'              => 'mediaSrc',
        'object-src'             => 'objectSrc',
        'plugin-types'           => 'pluginTypes',
        'report-uri'             => 'reportURI',
        'sandbox'                => 'sandbox',
        'script-src'             => 'scriptSrc',
        'style-src'              => 'styleSrc',
        'upgrade-insecure-requests' => 'upgradeInsecureRequests',
        'worker-src'             => 'workerSrc',
    ];
}