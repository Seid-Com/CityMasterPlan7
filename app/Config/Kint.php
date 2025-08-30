<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use Kint\Renderer\AbstractRenderer;

/**
 * --------------------------------------------------------------------------
 * Kint
 * --------------------------------------------------------------------------
 *
 * We use Kint's `RichRenderer` and `CLIRenderer`. This area contains options
 * that you can set to customize how Kint works for you.
 *
 * @see https://kint-php.github.io/kint/ for details on these settings.
 */
class Kint extends BaseConfig
{
    /*
    |--------------------------------------------------------------------------
    | Global Settings
    |--------------------------------------------------------------------------
    */

    public array $plugins;

    public int $maxDepth = 6;

    public bool $displayCalledFrom = true;

    public bool $expanded = false;

    /*
    |--------------------------------------------------------------------------
    | RichRenderer Settings
    |--------------------------------------------------------------------------
    */

    public string $richTheme = 'aante-light.css';

    public bool $richFolder = false;

    public int $richSort = 0;

    public array $richObjectPlugins;

    public array $richTabPlugins;

    /*
    |--------------------------------------------------------------------------
    | CLI Settings
    |--------------------------------------------------------------------------
    */

    public bool $cliColors = true;

    public bool $cliForceUTF8 = false;

    public bool $cliDetectWidth = true;

    public int $cliMinWidth = 40;

    public function __construct()
    {
        $this->plugins = [
            'Kint\\Parser\\BlacklistPlugin',
            'Kint\\Parser\\ClassMethodsPlugin',
            'Kint\\Parser\\ClassStaticsPlugin',
            'Kint\\Parser\\ClosurePlugin',
            'Kint\\Parser\\ColorPlugin',
            'Kint\\Parser\\DateTimePlugin',
            'Kint\\Parser\\EnumPlugin',
            'Kint\\Parser\\FsPathPlugin',
            'Kint\\Parser\\IteratorPlugin',
            'Kint\\Parser\\JsonPlugin',
            'Kint\\Parser\\MicrotimePlugin',
            'Kint\\Parser\\SimpleXMLElementPlugin',
            'Kint\\Parser\\StreamPlugin',
            'Kint\\Parser\\TablePlugin',
            'Kint\\Parser\\ThrowablePlugin',
            'Kint\\Parser\\TimestampPlugin',
            'Kint\\Parser\\TracePlugin',
            'Kint\\Parser\\XmlPlugin',
        ];

        $this->richObjectPlugins = [
            'Kint\\Renderer\\Rich\\ClosurePlugin',
            'Kint\\Renderer\\Rich\\ColorPlugin',
            'Kint\\Renderer\\Rich\\DateTimePlugin',
            'Kint\\Renderer\\Rich\\EnumPlugin',
            'Kint\\Renderer\\Rich\\FsPathPlugin',
            'Kint\\Renderer\\Rich\\SimpleXMLElementPlugin',
            'Kint\\Renderer\\Rich\\TablePlugin',
            'Kint\\Renderer\\Rich\\ThrowablePlugin',
            'Kint\\Renderer\\Rich\\TracePlugin',
        ];

        $this->richTabPlugins = [
            'Kint\\Renderer\\Rich\\ArrayLimitPlugin',
            'Kint\\Renderer\\Rich\\BinaryPlugin',
            'Kint\\Renderer\\Rich\\BlacklistPlugin',
            'Kint\\Renderer\\Rich\\CalledFromPlugin',
            'Kint\\Renderer\\Rich\\ClassMethodsPlugin',
            'Kint\\Renderer\\Rich\\ClassStaticsPlugin',
            'Kint\\Renderer\\Rich\\MethodDefinitionPlugin',
            'Kint\\Renderer\\Rich\\MicrotimePlugin',
            'Kint\\Renderer\\Rich\\SimpleXMLElementPlugin',
            'Kint\\Renderer\\Rich\\SourcePlugin',
            'Kint\\Renderer\\Rich\\TimestampPlugin',
            'Kint\\Renderer\\Rich\\TracePlugin',
        ];

        parent::__construct();
    }
}