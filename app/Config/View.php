<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class View extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * View Directory Paths
     * --------------------------------------------------------------------------
     *
     * This is an array of paths that CodeIgniter will look in for your views.
     * All paths are relative to the app directory and should not include
     * leading or trailing slashes.
     */
    public array $viewPath = [APPPATH . 'Views/'];

    /**
     * --------------------------------------------------------------------------
     * View Renderer Options
     * --------------------------------------------------------------------------
     *
     * PHP Short Tags
     * If you want to use the short tag syntax, you can set this to true.
     * NOTE: If you set this to true, then the echo syntax won't work.
     */
    public bool $saveData = true;

    /**
     * --------------------------------------------------------------------------
     * Conditional Operators
     * --------------------------------------------------------------------------
     *
     * The Parser supports conditional operators. This defines the symbols
     * used for those operators.
     */
    public array $conditionalDelimiters = [
        'lDelim' => '{if ',
        'rDelim' => '}',
    ];

    /**
     * --------------------------------------------------------------------------
     * File Extensions
     * --------------------------------------------------------------------------
     *
     * The default file extension for view files. This allows you to omit
     * the extension when loading a view.
     */
    public string $defaultExtension = '.php';

    /**
     * --------------------------------------------------------------------------
     * View Decorators
     * --------------------------------------------------------------------------
     *
     * Array of decorator classes that can be applied to views.
     */
    public array $decorators = [];
}